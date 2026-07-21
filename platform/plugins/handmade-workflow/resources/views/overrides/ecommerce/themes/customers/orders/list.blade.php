{{--
    COPY of plugins/ecommerce::themes.customers.orders.list, overridden by the
    handmade-workflow plugin (registered via View::prependNamespace) to add the
    order-type and production-status badges. The ecommerce plugin has no per-order
    hook in this view, so a copy is the only extension point.

    If the ecommerce plugin updates its own list view, re-sync this file.
--}}
@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/ecommerce::customer-dashboard.orders'))

@section('content')
    {!! apply_filters('ecommerce_customer_orders_before_content', '') !!}

    <div class="bb-customer-content-wrapper">
        @if($orders->isNotEmpty())
            @php
                // One query for the whole page rather than one per card.
                $hwQuotes = \Botble\HandmadeWorkflow\Models\OrderQuote::query()
                    ->whereIn('order_id', $orders->pluck('id'))
                    ->get()
                    ->keyBy('order_id');
            @endphp

            <div class="customer-list-order">
                <div class="bb-customer-card-list order-cards">
                @foreach ($orders as $order)
                    <div class="bb-customer-card order-card">
                        <div class="bb-customer-card-header">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div class="flex-grow-1">
                                    <h3 class="bb-customer-card-title mb-2">
                                        {{ trans('plugins/ecommerce::customer-dashboard.order_code', ['code' => $order->code]) }}
                                    </h3>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @php
                                            $isCustomOrder = \Botble\HandmadeWorkflow\Services\CustomOrderService::isCustomOrder($order);
                                            $productionStatus = $order->production_status
                                                ? \Botble\HandmadeWorkflow\Enums\ProductionStatusEnum::of($order->production_status)
                                                : null;
                                        @endphp

                                        <span class="badge rounded-pill bg-{{ $isCustomOrder ? 'info' : 'secondary' }}-subtle text-{{ $isCustomOrder ? 'info' : 'secondary' }}">
                                            <x-core::icon :name="$isCustomOrder ? 'ti ti-brush' : 'ti ti-shopping-bag'" />
                                            {{ $isCustomOrder
                                                ? trans('plugins/handmade-workflow::handmade-workflow.order_type_custom')
                                                : trans('plugins/handmade-workflow::handmade-workflow.order_type_catalog') }}
                                        </span>

                                        <div class="bb-customer-card-status">
                                            {!! BaseHelper::clean($productionStatus ? $productionStatus->toHtml() : $order->status->toHtml()) !!}
                                        </div>

                                        <span class="text-muted" style="font-size: 0.75rem;">•</span>
                                        <span class="text-muted" style="font-size: 0.75rem;">
                                            {{ $order->created_at->translatedFormat('M d, Y') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bb-customer-card-body">
                            <div class="bb-customer-card-info">
                                @php $hwQuote = $hwQuotes->get($order->getKey()); @endphp

                                <div class="row g-3">
                                    <div class="col-6 col-sm-3">
                                        <div class="info-item">
                                            <span class="label">{{ trans('plugins/ecommerce::customer-dashboard.total_amount') }}</span>
                                            <span class="value">{{ $order->amount_format }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="info-item">
                                            <span class="label">{{ trans('plugins/ecommerce::customer-dashboard.items') }}</span>
                                            <span class="value">{{ $order->products_count }}</span>
                                        </div>
                                    </div>

                                    {{-- A handmade order is paid in two wallet milestones, not through a
                                         payment channel, so the channel cell would always read "N/A".
                                         Show what has actually been taken and what is left instead. --}}
                                    @if ($hwQuote?->isQuoted())
                                        <div class="col-6 col-sm-3">
                                            <div class="info-item">
                                                <span class="label">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.amount_paid') }}</span>
                                                <span class="value text-success">{{ format_price($hwQuote->paid_amount) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-6 col-sm-3">
                                            <div class="info-item">
                                                <span class="label">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.amount_outstanding') }}</span>
                                                <span @class(['value', 'text-primary' => $hwQuote->outstanding_amount > 0])>
                                                    {{ format_price($hwQuote->outstanding_amount) }}
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-12 col-sm-6">
                                            <div class="info-item">
                                                <span class="label">{{ trans('plugins/ecommerce::customer-dashboard.payment') }}</span>
                                                <span class="value">
                                                    @if ($isCustomOrder)
                                                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.awaiting_quote') }}
                                                    @elseif(is_plugin_active('payment') && $order->payment->id && $order->payment->payment_channel->displayName())
                                                        {{ $order->payment->payment_channel->displayName() }}
                                                    @else
                                                        {{ trans('plugins/ecommerce::customer-dashboard.n_a') }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if ($hwQuote?->isQuoted() && $hwQuote->outstanding_amount > 0)
                                    <p class="text-muted small mb-0 mt-3">
                                        <x-core::icon name="ti ti-info-circle" class="me-1" />
                                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.next_payment', [
                                            'amount' => format_price(
                                                $hwQuote->isDepositPaid() ? $hwQuote->final_amount : $hwQuote->deposit_amount
                                            ),
                                            'milestone' => trans('plugins/handmade-workflow::handmade-workflow.quote.'
                                                . ($hwQuote->isDepositPaid() ? 'final' : 'deposit')),
                                        ]) }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="bb-customer-card-footer">
                            <a
                                class="btn btn-primary btn-sm"
                                href="{{ route('customer.orders.view', $order->id) }}"
                            >
                                <x-core::icon name="ti ti-eye" />
                                <span>{{ trans('plugins/ecommerce::customer-dashboard.view_details') }}</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

                @if($orders->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {!! $orders->links() !!}
                    </div>
                @endif
            </div>
        @else
            @include(EcommerceHelper::viewPath('customers.partials.empty-state'), [
                'title' => trans('plugins/ecommerce::customer-dashboard.no_orders_yet'),
                'subtitle' => trans('plugins/ecommerce::customer-dashboard.not_placed_orders_yet'),
                'actionUrl' => route('public.products'),
                'actionLabel' => trans('plugins/ecommerce::customer-dashboard.start_shopping_now'),
            ])
        @endif
    </div>

    {!! apply_filters('ecommerce_customer_orders_after_content', '') !!}
@stop
