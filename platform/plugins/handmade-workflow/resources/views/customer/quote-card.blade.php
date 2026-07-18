@php
    use Botble\HandmadeWorkflow\Services\MilestonePaymentService;
@endphp

<div class="card border-0 mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h5 class="card-title h6 mb-0">
                <x-core::icon name="ti ti-receipt-2" class="me-1" />
                {{ trans('plugins/handmade-workflow::handmade-workflow.quote.customer_title') }}
            </h5>
            {!! \Botble\HandmadeWorkflow\Enums\CustomerGroupEnum::of($customerGroup)->toHtml() !!}
        </div>

        @if ($action === 'accept-quote')
            <div class="alert alert-primary d-flex align-items-start gap-2">
                <x-core::icon name="ti ti-file-invoice" class="flex-shrink-0 mt-1" />
                <div>
                    <strong class="d-block">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.sent_banner') }}</strong>
                    <span class="small">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.sent_banner_help') }}</span>
                </div>
            </div>
        @endif

        {{-- Itemised exactly as staff priced it, so this page matches the admin view. --}}
        <table class="table table-sm">
            <tbody>
                @foreach ($order->products as $product)
                    <tr>
                        <td>
                            {{ $product->product_name }}
                            <span class="text-muted d-block small">
                                {{ format_price($product->price) }} × {{ $product->qty }}
                            </span>
                        </td>
                        <td class="text-end align-middle">{{ format_price($product->price * $product->qty) }}</td>
                    </tr>
                @endforeach
                <tr class="border-top">
                    <td class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.product_cost') }}</td>
                    <td class="text-end">{{ format_price($quote->product_cost) }}</td>
                </tr>
                @if ($quote->shipping_cost > 0)
                    <tr>
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_cost') }}</td>
                        <td class="text-end">{{ format_price($quote->shipping_cost) }}</td>
                    </tr>
                @endif
                @if ($quote->fulfill_fee > 0)
                    <tr>
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.fulfill_fee') }}</td>
                        <td class="text-end">{{ format_price($quote->fulfill_fee) }}</td>
                    </tr>
                @endif
                @if ($quote->packing_fee > 0)
                    <tr>
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.packing_fee') }}</td>
                        <td class="text-end">{{ format_price($quote->packing_fee) }}</td>
                    </tr>
                @endif
                <tr class="fw-bold border-top">
                    <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.total') }}</td>
                    <td class="text-end">{{ format_price($quote->total) }}</td>
                </tr>
            </tbody>
        </table>

        @if ($quote->expected_delivery_date)
            <p class="mb-2">
                <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.delivery_date') }}:</span>
                <strong>{{ $quote->expected_delivery_date->format('d/m/Y') }}</strong>
            </p>
        @endif

        @if ($quote->note)
            <div class="alert alert-light">{{ $quote->note }}</div>
        @endif

        <div class="alert alert-info">
            <div>
                {{ trans('plugins/handmade-workflow::handmade-workflow.quote.deposit') }}:
                <strong>{{ format_price($quote->deposit_amount) }}</strong>
                <small class="d-block text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.deposit_help') }}</small>
            </div>
            <div class="mt-2">
                {{ trans('plugins/handmade-workflow::handmade-workflow.quote.final') }}:
                <strong>{{ format_price($quote->final_amount) }}</strong>
                <small class="d-block text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.final_help') }}</small>
            </div>
        </div>

        @if ($action)
            @php
                $milestone = $action === 'accept-quote'
                    ? MilestonePaymentService::MILESTONE_DEPOSIT
                    : MilestonePaymentService::MILESTONE_FINAL;
                $due = $milestone === MilestonePaymentService::MILESTONE_DEPOSIT
                    ? $quote->deposit_amount
                    : $quote->final_amount;
            @endphp

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <span>
                    <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.amount_due') }}:</span>
                    <strong>{{ format_price($due) }}</strong>
                </span>
                <span>
                    <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.wallet_balance') }}:</span>
                    <strong>{{ format_price($balance) }}</strong>
                </span>
            </div>

            @if ($balance >= $due)
                {{-- Money leaves the wallet on submit, so always confirm first. --}}
                <x-core::form
                    :url="route('customer.handmade-orders.' . $action, $order->getKey())"
                    method="POST"
                    class="mb-0"
                    id="hw-pay-form"
                >
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hw-pay-modal">
                        <x-core::icon name="ti ti-check" class="me-1" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.' . str_replace('-', '_', $action)) }}
                    </button>
                </x-core::form>

                <div class="modal fade" id="hw-pay-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.confirm_payment_title') }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-2">
                                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.confirm_payment_body', [
                                        'amount' => format_price($due),
                                    ]) }}
                                </p>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.wallet_balance') }}</span>
                                    <span>{{ format_price($balance) }} → <strong>{{ format_price($balance - $due) }}</strong></span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    {{ trans('core/base::tables.cancel') }}
                                </button>
                                <button type="button" class="btn btn-primary" id="hw-pay-confirm">
                                    <x-core::icon name="ti ti-check" class="me-1" />
                                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.confirm_payment_submit') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    document.getElementById('hw-pay-confirm').addEventListener('click', function () {
                        this.disabled = true
                        document.getElementById('hw-pay-form').submit()
                    })
                </script>
            @else
                <div class="alert alert-warning">
                    <p class="mb-2">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.errors.insufficient_balance', [
                            'shortfall' => format_price($due - $balance),
                        ]) }}
                    </p>
                    <a href="{{ route('customer.e-wallet.topup.create') }}" class="btn btn-warning btn-sm">
                        <x-core::icon name="ti ti-wallet" class="me-1" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.top_up_now') }}
                    </a>
                </div>
            @endif

            {{-- Negotiation: the customer can send the quote back with a note instead
                 of accepting it. No money moves; staff re-price and send again. --}}
            @if ($action === 'accept-quote')
                <div class="border-top mt-3 pt-3">
                    <p class="mb-2">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.request_changes_intro') }}</p>

                    <x-core::form :url="route('customer.handmade-orders.request-changes', $order->getKey())" method="POST" class="mb-0">
                        <textarea
                            name="feedback"
                            class="form-control mb-2"
                            rows="3"
                            required
                            placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.quote.request_changes_placeholder') }}"
                        ></textarea>

                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <x-core::icon name="ti ti-message-2" class="me-1" />
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.request_changes') }}
                        </button>
                    </x-core::form>
                </div>
            @endif
        @endif
    </div>
</div>
