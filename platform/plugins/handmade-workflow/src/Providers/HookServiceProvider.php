<?php

namespace Botble\HandmadeWorkflow\Providers;

use Botble\Base\Facades\DashboardMenu;
use Botble\Ecommerce\Enums\OrderHistoryActionEnum;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderHistory;
use Illuminate\Support\Arr;
use Botble\HandmadeWorkflow\Enums\ProductionStatusEnum;
use Botble\HandmadeWorkflow\Models\OrderQuote;
use Botble\HandmadeWorkflow\Services\CustomOrderService;
use Botble\HandmadeWorkflow\Services\MilestonePaymentService;
use Botble\HandmadeWorkflow\Services\ProductionWorkflow;
use Botble\HandmadeWorkflow\Services\QuoteService;
use Illuminate\Support\ServiceProvider;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // `ec_order_histories.action` is cast to OrderHistoryActionEnum, so our action
        // must be a member of that enum or it would be persisted as NULL.
        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class === OrderHistoryActionEnum::class) {
                $values['PRODUCTION_STATUS_CHANGED'] = ProductionWorkflow::HISTORY_ACTION;
                $values['HANDMADE_QUOTE_SENT'] = QuoteService::HISTORY_ACTION;
            }

            return $values;
        }, 20, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($label, $class) {
            if ($class === OrderHistoryActionEnum::class && $label === ProductionWorkflow::HISTORY_ACTION) {
                return trans('plugins/handmade-workflow::handmade-workflow.production_status');
            }

            return $label;
        }, 20, 2);

        // Every new order enters the workflow at step 1, so the column is never left blank
        // (keeps filtering and reporting honest). saveQuietly avoids re-firing model events.
        Order::created(function (Order $order): void {
            if (! $order->production_status) {
                $order->forceFill([
                    'production_status' => ProductionStatusEnum::PENDING_APPROVAL,
                    'production_status_updated_at' => now(),
                ])->saveQuietly();
            }
        });

        // Admin: production status card on the order detail sidebar.
        // Admin: one single sidebar card — workflow state, what to do next, the
        // payment milestones and the cancel action, all in one place.
        add_filter('ecommerce_order_detail_sidebar_bottom', function (?string $html, Order $order): string {
            $workflow = app(ProductionWorkflow::class);
            $quotes = app(QuoteService::class);
            $quote = $quotes->quoteFor($order);

            return $html . view('plugins/handmade-workflow::admin.status-card', [
                'order' => $order,
                'current' => $workflow->currentStatus($order),
                'allowed' => $workflow->allowedNextStatuses($order),
                'quote' => $quote->exists ? $quote : null,
                'customerGroup' => $quotes->customerGroup($order),
                'isCustomOrder' => CustomOrderService::isCustomOrder($order),
            ])->render();
        }, 20, 2);

        // NOTE: reference photos are rendered by the cart-item-options-extras override,
        // which the admin products table includes too — so there is deliberately no
        // ECOMMERCE_ORDER_DETAIL_EXTRA_HTML hook here. Adding one duplicates every photo.

        // Customer: production progress timeline + their own submitted photos.
        add_filter('ecommerce_customer_order_view_before_actions', function (?string $html, Order $order): string {
            $workflow = app(ProductionWorkflow::class);
            $current = $workflow->currentStatus($order);

            // Quote first: when it is the customer's turn to act, that is what they
            // came for — progress is context, not the task.
            $html .= $this->renderCustomerQuote($order, $current);

            // Reference photos are rendered per line in the products list
            // (cart-item-options-extras override), so no separate card here.
            return $html . view('plugins/handmade-workflow::customer.timeline', [
                'order' => $order,
                'current' => $current,
                'currentIndex' => ProductionStatusEnum::stepIndex($current),
                'isCanceled' => $current === ProductionStatusEnum::CANCELED,
                'stepTimes' => $this->stepTimestamps($order),
            ])->render();
        }, 20, 2);

        // Admin order list: show the real production step. The table selects a fixed
        // column list that excludes production_status, so it is looked up per row and
        // memoised for the page rather than changing the core query.
        add_filter(BASE_FILTER_GET_LIST_DATA, function ($data, $model = null) {
            if (! $model instanceof Order || ! method_exists($data, 'editColumn')) {
                return $data;
            }

            $cache = [];

            return $data->editColumn('status', function ($item) use (&$cache) {
                $status = $cache[$item->id] ??= Order::query()
                    ->whereKey($item->id)
                    ->value('production_status');

                if (! $status || ! ProductionStatusEnum::isValid($status)) {
                    return $item->status->toHtml();
                }

                return ProductionStatusEnum::of($status)->toHtml();
            });
        }, 20, 2);

        // Customer account sidebar entry point for the made-to-order form.
        DashboardMenu::for('customer')->beforeRetrieving(function (): void {
            DashboardMenu::make()->registerItem([
                'id' => 'cms-customer-custom-orders',
                'priority' => 31,
                'name' => trans('plugins/handmade-workflow::handmade-workflow.custom_order.menu'),
                'url' => fn () => route('customer.custom-orders.create'),
                'icon' => 'ti ti-brush',
            ]);
        });
    }

    /**
     * When each production step was reached, read back from the order history so the
     * timeline can date every completed step instead of only the current one.
     *
     * @return array<string, \Illuminate\Support\Carbon>
     */
    protected function stepTimestamps(Order $order): array
    {
        $times = [];

        $histories = OrderHistory::query()
            ->where('order_id', $order->getKey())
            ->where('action', ProductionWorkflow::HISTORY_ACTION)
            ->orderBy('id')
            ->get();

        foreach ($histories as $history) {
            if ($to = Arr::get($history->extras, 'to')) {
                $times[$to] = $history->created_at;
            }
        }

        // The first step is never "moved to", so date it from when the order was placed.
        $times[ProductionStatusEnum::PENDING_APPROVAL] ??= $order->created_at;

        return $times;
    }

    /**
     * The quote the customer sees, plus the approve button when it is their turn to act.
     */
    protected function renderCustomerQuote(Order $order, string $current): string
    {
        $quote = OrderQuote::query()->where('order_id', $order->getKey())->first();

        if (! $quote || ! $quote->isQuoted()) {
            return '';
        }

        $action = match ($current) {
            ProductionStatusEnum::QUOTED => 'accept-quote',
            ProductionStatusEnum::AWAITING_CONFIRMATION => 'confirm-product',
            default => null,
        };

        return view('plugins/handmade-workflow::customer.quote-card', [
            'order' => $order,
            'quote' => $quote,
            'action' => $action,
            'balance' => app(MilestonePaymentService::class)->balanceOf($order),
            'customerGroup' => app(QuoteService::class)->customerGroup($order),
        ])->render();
    }
}
