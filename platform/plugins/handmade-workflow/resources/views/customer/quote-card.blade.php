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

        {{-- The two milestones with what has actually been taken. Before this the card
             showed the same two figures whether or not they had been paid, so a
             customer who had already paid the deposit could not tell from the page. --}}
        <div class="border rounded p-3 mb-3">
            <h6 class="small text-uppercase text-muted mb-3">
                {{ trans('plugins/handmade-workflow::handmade-workflow.quote.payment_schedule') }}
            </h6>

            @foreach ($quote->milestones() as $milestone)
                <div class="d-flex justify-content-between align-items-start gap-3 pb-3 @if (! $loop->first) pt-3 border-top @endif">
                    <div>
                        <x-core::icon
                            :name="$milestone['paid'] ? 'ti ti-circle-check-filled' : 'ti ti-circle-dashed'"
                            :class="($milestone['paid'] ? 'text-success' : 'text-muted') . ' me-1'"
                        />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.' . $milestone['key']) }}
                        <small class="d-block text-muted mt-1">
                            @if ($milestone['paid'])
                                {{ trans('plugins/handmade-workflow::handmade-workflow.quote.paid_at', [
                                    'time' => $milestone['paid_at']?->translatedFormat('H:i d/m/Y'),
                                ]) }}
                            @else
                                {{ trans('plugins/handmade-workflow::handmade-workflow.quote.' . $milestone['key'] . '_help') }}
                            @endif
                        </small>
                    </div>
                    <div class="text-end text-nowrap">
                        <strong class="d-block">{{ format_price($milestone['amount']) }}</strong>
                        <span class="badge rounded-pill bg-{{ $milestone['paid'] ? 'success' : 'secondary' }}-subtle text-{{ $milestone['paid'] ? 'success' : 'secondary' }}">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.' . ($milestone['paid'] ? 'paid' : 'unpaid')) }}
                        </span>
                    </div>
                </div>
            @endforeach

            <div class="border-top pt-3">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.amount_paid') }}</span>
                    <strong class="text-success">{{ format_price($quote->paid_amount) }}</strong>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.amount_outstanding') }}</span>
                    <strong @class(['text-primary' => $quote->outstanding_amount > 0])>
                        {{ format_price($quote->outstanding_amount) }}
                    </strong>
                </div>
            </div>

            @if ($quote->outstanding_amount <= 0)
                <p class="text-success small mb-0 mt-3">
                    <x-core::icon name="ti ti-circle-check" class="me-1" />
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.settled') }}
                </p>
            @endif
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
