<?php

namespace Botble\HandmadeWorkflow\Services;

use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderHistory;
use Botble\HandmadeWorkflow\Enums\ProductionStatusEnum;
use Botble\HandmadeWorkflow\Events\ProductionStatusChanged;
use Botble\HandmadeWorkflow\Models\OrderQuote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionWorkflow
{
    public const HISTORY_ACTION = 'production_status_changed';

    /**
     * Allowed moves. The line is strictly sequential; CANCELED is reachable from any
     * step that has not shipped or finished yet (admin decides refunds case by case).
     *
     * @return array<string, array<int, string>>
     */
    public function transitions(): array
    {
        $flow = ProductionStatusEnum::flow();
        $transitions = [];

        foreach ($flow as $index => $status) {
            $next = $flow[$index + 1] ?? null;

            $transitions[$status] = array_values(array_filter([
                $next,
                $this->isCancelable($status) ? ProductionStatusEnum::CANCELED : null,
            ]));
        }

        $transitions[ProductionStatusEnum::CANCELED] = [];

        return $transitions;
    }

    /**
     * An order can still be canceled until it has been handed to the carrier.
     */
    public function isCancelable(?string $status): bool
    {
        return ! in_array($status, [
            ProductionStatusEnum::SHIPPING,
            ProductionStatusEnum::COMPLETED,
            ProductionStatusEnum::CANCELED,
        ], true);
    }

    /**
     * An order with no production status yet starts at the first step.
     */
    public function currentStatus(Order $order): string
    {
        return $order->production_status ?: ProductionStatusEnum::PENDING_APPROVAL;
    }

    /**
     * @return array<int, string>
     */
    public function allowedNextStatuses(Order $order): array
    {
        return $this->transitions()[$this->currentStatus($order)] ?? [];
    }

    public function canTransition(Order $order, string $to): bool
    {
        return in_array($to, $this->allowedNextStatuses($order), true);
    }

    /**
     * Move an order to the next production step, keeping the core order status,
     * the order history and the workflow event in sync.
     *
     * @throws \InvalidArgumentException when the move is not allowed.
     */
    public function transition(Order $order, string $to, ?string $note = null): Order
    {
        if (! ProductionStatusEnum::isValid($to)) {
            throw new \InvalidArgumentException("Unknown production status [{$to}].");
        }

        $from = $this->currentStatus($order);

        if (! $this->canTransition($order, $to)) {
            throw new \InvalidArgumentException(
                trans('plugins/handmade-workflow::handmade-workflow.errors.invalid_transition', [
                    'from' => ProductionStatusEnum::of($from)->label(),
                    'to' => ProductionStatusEnum::of($to)->label(),
                ])
            );
        }

        $this->assertMilestonePaid($order, $to);

        DB::transaction(function () use ($order, $from, $to, $note): void {
            $target = ProductionStatusEnum::of($to);

            $order->production_status = $to;
            $order->production_status_updated_at = now();
            // Keep the core status aligned so cancel/return/invoice logic keeps working.
            $order->status = $target->toOrderStatus();

            if ($to === ProductionStatusEnum::COMPLETED && ! $order->completed_at) {
                $order->completed_at = now();
            }

            $order->save();

            OrderHistory::query()->create([
                'action' => self::HISTORY_ACTION,
                'description' => trans('plugins/handmade-workflow::handmade-workflow.history.changed', [
                    'from' => ProductionStatusEnum::of($from)->label(),
                    'to' => $target->label(),
                ]),
                'order_id' => $order->getKey(),
                'user_id' => Auth::check() ? Auth::id() : 0,
                'extras' => json_encode([
                    'from' => $from,
                    'to' => $to,
                    'note' => $note,
                ]),
            ]);
        });

        ProductionStatusChanged::dispatch($order->refresh(), $from, $to, $note);

        return $order;
    }

    /**
     * The two paid steps cannot be entered until the matching wallet milestone
     * has actually been charged — otherwise an admin could advance an unpaid order.
     *
     * @throws \InvalidArgumentException
     */
    protected function assertMilestonePaid(Order $order, string $to): void
    {
        $milestone = match ($to) {
            ProductionStatusEnum::DEPOSITED => MilestonePaymentService::MILESTONE_DEPOSIT,
            ProductionStatusEnum::CONFIRMED => MilestonePaymentService::MILESTONE_FINAL,
            default => null,
        };

        if (! $milestone) {
            return;
        }

        $quote = OrderQuote::query()->where('order_id', $order->getKey())->first();

        if (! $quote || ! app(MilestonePaymentService::class)->isPaid($quote, $milestone)) {
            throw new \InvalidArgumentException(
                trans("plugins/handmade-workflow::handmade-workflow.errors.{$milestone}_not_paid")
            );
        }
    }
}
