<?php

namespace Botble\HandmadeWorkflow\Http\Controllers\Fronts;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\HandmadeWorkflow\Enums\ProductionStatusEnum;
use Botble\HandmadeWorkflow\Models\OrderQuote;
use Botble\HandmadeWorkflow\Services\MilestonePaymentService;
use Botble\HandmadeWorkflow\Services\ProductionWorkflow;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CustomerQuoteController extends Controller
{
    public function __construct(
        protected MilestonePaymentService $payments,
        protected ProductionWorkflow $workflow
    ) {
    }

    /**
     * Step 1 → 2. The customer approves the quote; milestone 1 leaves their wallet.
     */
    public function acceptQuote(int $orderId, BaseHttpResponse $response)
    {
        return $this->settle(
            $orderId,
            $response,
            ProductionStatusEnum::PENDING_APPROVAL,
            ProductionStatusEnum::DEPOSITED,
            MilestonePaymentService::MILESTONE_DEPOSIT,
            'quote_accepted',
            fn (OrderQuote $quote) => $quote->forceFill(['accepted_at' => now()])->save()
        );
    }

    /**
     * Step 6 → 7. The customer approves the finished item; milestone 2 is charged.
     */
    public function confirmProduct(int $orderId, BaseHttpResponse $response)
    {
        return $this->settle(
            $orderId,
            $response,
            ProductionStatusEnum::AWAITING_CONFIRMATION,
            ProductionStatusEnum::CONFIRMED,
            MilestonePaymentService::MILESTONE_FINAL,
            'product_confirmed'
        );
    }

    protected function settle(
        int $orderId,
        BaseHttpResponse $response,
        string $expectedStatus,
        string $nextStatus,
        string $milestone,
        string $successKey,
        ?callable $beforeCharge = null
    ) {
        $order = Order::query()
            ->where('id', $orderId)
            ->where('user_id', Auth::guard('customer')->id())
            ->firstOrFail();

        if ($this->workflow->currentStatus($order) !== $expectedStatus) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.errors.wrong_step'));
        }

        $quote = OrderQuote::query()->where('order_id', $order->getKey())->first();

        if (! $quote || ! $quote->isQuoted()) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.errors.no_quote'));
        }

        try {
            DB::transaction(function () use ($order, $quote, $milestone, $nextStatus, $beforeCharge): void {
                if ($beforeCharge) {
                    $beforeCharge($quote);
                }

                $this->payments->charge($order, $quote, $milestone);
                $this->workflow->transition($order, $nextStatus);
            });
        } catch (InsufficientBalanceException $e) {
            // Decision #4: never let the balance go negative — stop and ask for a top-up.
            return $response
                ->setError()
                ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.errors.insufficient_balance', [
                    'shortfall' => format_price($this->payments->shortfall($order, $quote, $milestone)),
                ]));
        } catch (Throwable $e) {
            Log::error('Handmade milestone payment failed', ['order' => $order->getKey(), 'exception' => $e]);

            return $response
                ->setError()
                ->setMessage(trans('plugins/handmade-workflow::handmade-workflow.errors.payment_failed'));
        }

        return $response
            ->setNextUrl(route('customer.orders.view', $order->getKey()))
            ->setMessage(trans("plugins/handmade-workflow::handmade-workflow.{$successKey}"));
    }
}
