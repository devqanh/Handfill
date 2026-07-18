@php
    use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;
@endphp

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

        @if ($locked)
            {{-- Once the deposit is paid the numbers are what the customer agreed to. --}}
            <table class="table table-sm mb-2">
                <tbody>
                    <tr>
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.product_cost') }}</td>
                        <td class="text-end">{{ format_price($quote->product_cost) }}</td>
                    </tr>
                    <tr>
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_cost') }}</td>
                        <td class="text-end">{{ format_price($quote->shipping_cost) }}</td>
                    </tr>
                    <tr>
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.fulfill_fee') }}</td>
                        <td class="text-end">{{ format_price($quote->fulfill_fee) }}</td>
                    </tr>
                    <tr>
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.packing_fee') }}</td>
                        <td class="text-end">{{ format_price($quote->packing_fee) }}</td>
                    </tr>
                    <tr class="fw-bold border-top">
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.total') }}</td>
                        <td class="text-end">{{ format_price($quote->total) }}</td>
                    </tr>
                </tbody>
            </table>

            <ul class="list-unstyled mb-0 small">
                <li>
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.deposit') }}:
                    <strong>{{ format_price($quote->deposit_amount) }}</strong>
                    @if ($quote->isDepositPaid())
                        <span class="badge bg-success">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.paid') }}</span>
                    @else
                        <span class="badge bg-warning">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.unpaid') }}</span>
                    @endif
                </li>
                <li>
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.final') }}:
                    <strong>{{ format_price($quote->final_amount) }}</strong>
                    @if ($quote->isFinalPaid())
                        <span class="badge bg-success">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.paid') }}</span>
                    @else
                        <span class="badge bg-warning">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.unpaid') }}</span>
                    @endif
                </li>
            </ul>
        @else
            @if ($quote->isQuoted())
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
                    <div>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.resend_hint') }}</div>
                </div>
            @endif

            <x-core::form :url="route('handmade-workflow.quote', $order->getKey())" method="POST" class="mb-0">
                <div class="mb-2">
                    <label class="form-label" for="product_cost">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.product_cost') }}
                    </label>
                    <input type="number" step="0.01" min="0" class="form-control" id="product_cost"
                        name="product_cost" value="{{ old('product_cost', $quote->product_cost) }}" required>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="shipping_cost">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_cost') }}
                    </label>
                    <input type="number" step="0.01" min="0" class="form-control" id="shipping_cost"
                        name="shipping_cost" value="{{ old('shipping_cost', $quote->shipping_cost) }}" required>
                    <small class="text-muted">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_help') }}
                    </small>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="fulfill_fee">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.fulfill_fee') }}
                    </label>
                    <input type="number" step="0.01" min="0" class="form-control" id="fulfill_fee"
                        name="fulfill_fee" value="{{ old('fulfill_fee', $quote->fulfill_fee) }}" required>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="packing_fee">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.packing_fee') }}
                    </label>
                    <input type="number" step="0.01" min="0" class="form-control" id="packing_fee"
                        name="packing_fee" value="{{ old('packing_fee', $quote->packing_fee) }}" required>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="expected_delivery_date">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.delivery_date') }}
                    </label>
                    <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date"
                        value="{{ old('expected_delivery_date', $quote->expected_delivery_date?->format('Y-m-d')) }}">
                </div>

                <div class="mb-2">
                    <label class="form-label" for="quote_note">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.note') }}
                    </label>
                    <textarea class="form-control" id="quote_note" name="note" rows="2">{{ old('note', $quote->note) }}</textarea>
                </div>

                <x-core::button type="submit" color="primary" icon="ti ti-device-floppy">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.save') }}
                </x-core::button>
            </x-core::form>
        @endif
    </x-core::card.body>
</x-core::card>
