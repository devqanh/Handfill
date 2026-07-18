@php
    use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;

    $currency = get_application_currency();
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
            {{-- After the deposit is charged these numbers are what the customer agreed to. --}}
            <table class="table table-sm mb-3">
                <tbody>
                    @foreach ($order->products as $product)
                        <tr>
                            <td>
                                {{ $product->product_name }}
                                <span class="text-muted">× {{ $product->qty }}</span>
                            </td>
                            <td class="text-end">{{ format_price($product->price * $product->qty) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_cost') }}</td>
                        <td class="text-end">{{ format_price($quote->shipping_cost) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.fulfill_fee') }}</td>
                        <td class="text-end">{{ format_price($quote->fulfill_fee) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.packing_fee') }}</td>
                        <td class="text-end">{{ format_price($quote->packing_fee) }}</td>
                    </tr>
                    <tr class="fw-bold border-top">
                        <td>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.total') }}</td>
                        <td class="text-end">{{ format_price($quote->total) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="d-flex flex-column gap-1 small">
                <div class="d-flex justify-content-between">
                    <span>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.deposit') }}</span>
                    <span>
                        <strong>{{ format_price($quote->deposit_amount) }}</strong>
                        <span class="badge bg-{{ $quote->isDepositPaid() ? 'success' : 'warning' }}">
                            {{ $quote->isDepositPaid()
                                ? trans('plugins/handmade-workflow::handmade-workflow.quote.paid')
                                : trans('plugins/handmade-workflow::handmade-workflow.quote.unpaid') }}
                        </span>
                    </span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.final') }}</span>
                    <span>
                        <strong>{{ format_price($quote->final_amount) }}</strong>
                        <span class="badge bg-{{ $quote->isFinalPaid() ? 'success' : 'warning' }}">
                            {{ $quote->isFinalPaid()
                                ? trans('plugins/handmade-workflow::handmade-workflow.quote.paid')
                                : trans('plugins/handmade-workflow::handmade-workflow.quote.unpaid') }}
                        </span>
                    </span>
                </div>
            </div>
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

            <x-core::form
                :url="route('handmade-workflow.quote', $order->getKey())"
                method="POST"
                class="mb-0"
                id="hw-quote-form"
            >
                <label class="form-label fw-semibold">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.items_heading') }}
                </label>

                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ trans('plugins/handmade-workflow::handmade-workflow.item_name') }}</th>
                                <th class="text-center" style="width: 70px;">
                                    {{ trans('plugins/handmade-workflow::handmade-workflow.item_qty') }}
                                </th>
                                <th style="width: 150px;">
                                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.unit_price') }}
                                </th>
                                <th class="text-end" style="width: 120px;">
                                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.line_total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->products as $index => $product)
                                <tr>
                                    <td>
                                        {{ $product->product_name }}
                                        @php $handmade = data_get($product->options, 'handmade'); @endphp
                                        @if (! empty($handmade['note']))
                                            <div class="text-muted small">{{ $handmade['note'] }}</div>
                                        @endif
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $product->getKey() }}">
                                    </td>
                                    <td class="text-center" data-hw-qty="{{ (int) $product->qty }}">
                                        {{ $product->qty }}
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control form-control-sm hw-item-price"
                                            name="items[{{ $index }}][price]"
                                            value="{{ old("items.$index.price", (float) $product->price) }}"
                                            required
                                        >
                                    </td>
                                    <td class="text-end hw-line-total">—</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small" for="shipping_cost">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_cost') }}
                        </label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm hw-fee"
                            id="shipping_cost" name="shipping_cost"
                            value="{{ old('shipping_cost', $quote->shipping_cost) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small" for="fulfill_fee">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.fulfill_fee') }}
                        </label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm hw-fee"
                            id="fulfill_fee" name="fulfill_fee"
                            value="{{ old('fulfill_fee', $quote->fulfill_fee) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small" for="packing_fee">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.packing_fee') }}
                        </label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm hw-fee"
                            id="packing_fee" name="packing_fee"
                            value="{{ old('packing_fee', $quote->packing_fee) }}" required>
                    </div>
                </div>

                <small class="text-muted d-block mb-3">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_help') }}
                </small>

                {{-- Live summary so staff sees the deposit before sending --}}
                <div class="bg-light rounded p-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.product_cost') }}</span>
                        <strong id="hw-sum-products">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                        <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.total') }}</span>
                        <strong id="hw-sum-total">—</strong>
                    </div>
                    <div class="d-flex justify-content-between text-primary">
                        <span>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.deposit') }}</span>
                        <strong id="hw-sum-deposit">—</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.final') }}</span>
                        <strong id="hw-sum-final">—</strong>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small" for="expected_delivery_date">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.delivery_date') }}
                        </label>
                        <input type="date" class="form-control form-control-sm" id="expected_delivery_date"
                            name="expected_delivery_date"
                            value="{{ old('expected_delivery_date', $quote->expected_delivery_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small" for="quote_note">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.note') }}
                        </label>
                        <input type="text" class="form-control form-control-sm" id="quote_note" name="note"
                            value="{{ old('note', $quote->note) }}">
                    </div>
                </div>

                <x-core::button type="submit" color="primary" icon="ti ti-send">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.save') }}
                </x-core::button>
            </x-core::form>

            <script>
                (function () {
                    const form = document.getElementById('hw-quote-form')
                    if (!form) return

                    const decimals = {{ (int) ($currency->decimals ?? 0) }}
                    const fmt = (n) => n.toLocaleString('vi-VN', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals,
                    }) + ' {{ $currency->symbol ?: $currency->title }}'

                    const num = (el) => parseFloat(el?.value || 0) || 0

                    function recalc() {
                        let products = 0

                        form.querySelectorAll('tbody tr').forEach((row) => {
                            const price = num(row.querySelector('.hw-item-price'))
                            const qty = parseInt(row.querySelector('[data-hw-qty]')?.dataset.hwQty || 0, 10)
                            const line = price * qty

                            products += line
                            row.querySelector('.hw-line-total').textContent = fmt(line)
                        })

                        const shipping = num(form.querySelector('#shipping_cost'))
                        const fulfill = num(form.querySelector('#fulfill_fee'))
                        const packing = num(form.querySelector('#packing_fee'))

                        const total = products + shipping + fulfill + packing
                        // Mirrors the server: deposit = half the product cost + shipping,
                        // final = the remainder, so the two always add up to the total.
                        const deposit = Math.round((products / 2) * 100) / 100 + shipping
                        const final = Math.round((total - deposit) * 100) / 100

                        form.querySelector('#hw-sum-products').textContent = fmt(products)
                        form.querySelector('#hw-sum-total').textContent = fmt(total)
                        form.querySelector('#hw-sum-deposit').textContent = fmt(deposit)
                        form.querySelector('#hw-sum-final').textContent = fmt(final)
                    }

                    form.addEventListener('input', (e) => {
                        if (e.target.matches('.hw-item-price, .hw-fee')) recalc()
                    })

                    recalc()
                })()
            </script>
        @endif
    </x-core::card.body>
</x-core::card>
