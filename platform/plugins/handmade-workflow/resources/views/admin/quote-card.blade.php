@php
    use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;
@endphp

{{-- Read-only summary. Editing lives in the order products panel (main column). --}}
<x-core::card class="mt-3">
    <x-core::card.header>
        <x-core::card.title>
            <x-core::icon name="ti ti-receipt-2" />
            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.title') }}
        </x-core::card.title>
    </x-core::card.header>

    <x-core::card.body>
        <p class="mb-3">
            <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.customer_group') }}:</span>
            {!! CustomerGroupEnum::of($customerGroup)->toHtml() !!}
        </p>

        @if (! $quote->isQuoted())
            <div class="alert alert-info py-2 px-3 small mb-0">
                <x-core::icon name="ti ti-info-circle" class="me-1" />
                {{ trans('plugins/handmade-workflow::handmade-workflow.quote.edit_in_main_panel') }}
            </div>
        @else
            @unless ($locked)
                <div class="alert alert-warning py-2 px-3 small">
                    <div class="fw-semibold">
                        <x-core::icon name="ti ti-hourglass" class="me-1" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.awaiting_customer') }}
                    </div>
                    <div>
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.sent_at', [
                            'time' => $quote->quoted_at?->format('d/m/Y H:i'),
                            'times' => $timesQuoted,
                        ]) }}
                    </div>
                </div>
            @endunless

            <table class="table table-sm mb-3">
                <tbody>
                    <tr>
                        <td class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.product_cost') }}</td>
                        <td class="text-end">{{ format_price($quote->product_cost) }}</td>
                    </tr>
                    @foreach ([
                        'shipping_cost' => $quote->shipping_cost,
                        'fulfill_fee' => $quote->fulfill_fee,
                        'packing_fee' => $quote->packing_fee,
                    ] as $key => $value)
                        @continue(! $value)
                        <tr>
                            <td class="text-muted">{{ trans("plugins/handmade-workflow::handmade-workflow.quote.$key") }}</td>
                            <td class="text-end">{{ format_price($value) }}</td>
                        </tr>
                    @endforeach
                    <tr class="fw-bold border-top">
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.total') }}</td>
                        <td class="text-end">{{ format_price($quote->total) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="d-flex flex-column gap-2 small">
                @foreach ([
                    ['deposit', $quote->deposit_amount, $quote->isDepositPaid()],
                    ['final', $quote->final_amount, $quote->isFinalPaid()],
                ] as [$key, $amount, $paid])
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ trans("plugins/handmade-workflow::handmade-workflow.quote.$key") }}</span>
                        <span>
                            <strong>{{ format_price($amount) }}</strong>
                            <span class="badge bg-{{ $paid ? 'success' : 'warning' }}">
                                {{ $paid
                                    ? trans('plugins/handmade-workflow::handmade-workflow.quote.paid')
                                    : trans('plugins/handmade-workflow::handmade-workflow.quote.unpaid') }}
                            </span>
                        </span>
                    </div>
                @endforeach
            </div>

            @if ($quote->expected_delivery_date)
                <p class="text-muted small mt-3 mb-0">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.delivery_date') }}:
                    <strong>{{ $quote->expected_delivery_date->format('d/m/Y') }}</strong>
                </p>
            @endif
        @endif
    </x-core::card.body>
</x-core::card>
