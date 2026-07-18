@php
    use Botble\HandmadeWorkflow\Services\MilestonePaymentService;
@endphp

<div class="card border-0 mb-4">
    <div class="card-body">
        <h5 class="card-title h6 mb-3">
            <x-core::icon name="ti ti-receipt-2" class="me-1" />
            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.customer_title') }}
        </h5>

        <table class="table table-sm">
            <tbody>
                <tr>
                    <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.product_cost') }}</td>
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
                <x-core::form :url="route('customer.handmade-orders.' . $action, $order->getKey())" method="POST" class="mb-0">
                    <button type="submit" class="btn btn-primary">
                        <x-core::icon name="ti ti-check" class="me-1" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.' . str_replace('-', '_', $action)) }}
                    </button>
                </x-core::form>
            @else
                <div class="alert alert-warning mb-0">
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
        @endif
    </div>
</div>
