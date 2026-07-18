{{--
    COPY of plugins/ecommerce::orders.edit.order-products, overridden by the
    handmade-workflow plugin (View::prependNamespace) so staff can price each
    handmade line right here in the main panel instead of in a cramped sidebar.

    Only two things are added: an editable unit-price cell and the quote footer
    below the table. Re-sync if the ecommerce plugin changes this view.
--}}
@php
    $hwWorkflow = app(\Botble\HandmadeWorkflow\Services\ProductionWorkflow::class);
    $hwQuotes = app(\Botble\HandmadeWorkflow\Services\QuoteService::class);

    // Editable while pricing and while a sent quote is still awaiting the customer,
    // so staff can re-quote after feedback. Locked once the deposit is charged.
    $hwEditable = ($isInAdmin ?? false) && in_array(
        $hwWorkflow->currentStatus($order),
        [
            \Botble\HandmadeWorkflow\Enums\ProductionStatusEnum::PENDING_APPROVAL,
            \Botble\HandmadeWorkflow\Enums\ProductionStatusEnum::QUOTED,
        ],
        true
    );

    $hwQuote = $hwQuotes->withDefaults($order);
@endphp

<x-core::table :hover="false" :striped="false" class="order-products-table">
    <x-core::table.body>
        @foreach ($order->products as $orderProduct)
            @php
                $product = $orderProduct->product->original_product;
            @endphp

            <x-core::table.body.row>
                <x-core::table.body.cell style="width: 80px">
                    <img
                        src="{{ RvMedia::getImageUrl($orderProduct->product_image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                        alt="{{ $orderProduct->product_name }}"
                    >
                </x-core::table.body.cell>
                <x-core::table.body.cell style="width: 45%" class="text-start">
                    <div class="d-flex align-items-center flex-wrap">
                        @if ($editProductRoute && $product->getKey() && $product->original_product->getKey())
                            <a
                                href="{{ route($editProductRoute, $product->original_product->getKey()) }}"
                                title="{{ $orderProduct->product_name }}"
                                target="_blank"
                                class="me-2"
                            >
                                {{ $orderProduct->product_name }}
                            </a>
                        @else
                            <span class="me-2">{{ $orderProduct->product_name }}</span>
                        @endif

                        @if ($sku = Arr::get($orderProduct->options, 'sku') ?: ($product && $product->sku ? $product->sku : null))
                            <p class="mb-0">({{ trans('plugins/ecommerce::order.sku') }}: <strong>{{ $sku }}</strong>)</p>
                        @endif
                    </div>

                    @if ($attributes = Arr::get($orderProduct->options, 'attributes'))
                        <div>
                            <small>{{ $attributes }}</small>
                        </div>
                    @endif

                    @if($isInAdmin)
                        @if (!empty($orderProduct->product_options) && is_array($orderProduct->product_options))
                            {!! render_product_options_html($orderProduct->product_options, $orderProduct->product?->front_sale_price ?? $orderProduct->price) !!}
                        @endif
                    @endif

                    @include(
                        EcommerceHelper::viewPath('includes.cart-item-options-extras'),
                        ['options' => $orderProduct->options]
                    )

                    @php
                        $bundleReference = Arr::get($orderProduct->options, 'extras.upsale_reference_product');
                        $bundleReferenceProduct = $bundleReference ? \Botble\Ecommerce\Models\Product::query()->where('slug', $bundleReference)->first() : null;
                    @endphp
                    @if ($bundleReference)
                        @include('plugins/ecommerce::themes.includes.cart-bundle-badge', [
                            'reference' => $bundleReference,
                            'referenceProduct' => $bundleReferenceProduct,
                        ])
                    @endif

                    {!! apply_filters(ECOMMERCE_ORDER_DETAIL_EXTRA_HTML, null, $orderProduct, $order) !!}
                    {!! apply_filters('ecommerce_order_product_item_extra_info', null, $orderProduct, $order) !!}

                    @if (! EcommerceHelper::isDisabledPhysicalProduct() && $order->shipment->id)
                        <ul class="list-unstyled ms-1 small">
                            <li>
                                <span class="bull">↳</span>
                                <span class="black">{{ trans('plugins/ecommerce::order.shipping') }}</span>
                                @if($isInAdmin)
                                    <a
                                        class="text-underline bold-light"
                                        href="{{ route('ecommerce.shipments.edit', $order->shipment->id) }}"
                                        title="{{ $order->shipping_method_name }}"
                                        target="_blank"
                                    >{{ $order->shipping_method_name }}</a>
                                @else
                                    <span class="text-underline bold-light">{{ $order->shipping_method_name }}</span>
                                @endif
                            </li>

                            {!! apply_filters('ecommerce_order_product_item_extra_info_after', '', $orderProduct, $order) !!}
                        </ul>
                    @endif
                </x-core::table.body.cell>
                <x-core::table.body.cell style="width: 170px">
                    @if ($hwEditable)
                        <input
                            type="number"
                            step="1"
                            min="0"
                            class="form-control form-control-sm hw-item-price"
                            data-hw-item-id="{{ $orderProduct->getKey() }}"
                            data-hw-qty="{{ (int) $orderProduct->qty }}"
                            value="{{ (float) $orderProduct->price }}"
                            aria-label="{{ trans('plugins/handmade-workflow::handmade-workflow.quote.unit_price') }}"
                        >
                    @else
                        {{ format_price($orderProduct->price) }}
                    @endif
                </x-core::table.body.cell>
                <x-core::table.body.cell>
                    x
                </x-core::table.body.cell>
                <x-core::table.body.cell style="width: 90px">
                    @if ($hwEditable)
                        {{-- Editable so staff can fix a quantity the customer got wrong --}}
                        <input
                            type="number"
                            step="1"
                            min="1"
                            class="form-control form-control-sm hw-item-qty"
                            data-hw-item-id="{{ $orderProduct->getKey() }}"
                            value="{{ (int) $orderProduct->qty }}"
                            aria-label="{{ trans('plugins/handmade-workflow::handmade-workflow.item_qty') }}"
                        >
                    @else
                        {{ $orderProduct->qty }}
                    @endif
                </x-core::table.body.cell>
                <x-core::table.body.cell class="hw-line-total">
                    {{ format_price($orderProduct->price * $orderProduct->qty) }}
                </x-core::table.body.cell>
            </x-core::table.body.row>
        @endforeach
    </x-core::table.body>
</x-core::table>

@if ($hwEditable)
    @php $hwCurrency = get_application_currency(); @endphp

    {{-- Config travels through data attributes: Blade directives inside <script> can
         swallow the following newline and silently break the whole script. --}}
    <x-core::card.body
        class="border-top bg-body-tertiary"
        id="hw-quote-editor"
        data-hw-url="{{ route('handmade-workflow.quote', $order->getKey()) }}"
        data-hw-decimals="{{ (int) ($hwCurrency->decimals ?? 0) }}"
        data-hw-symbol="{{ $hwCurrency->symbol ?: $hwCurrency->title }}"
    >
        <h4 class="h6 mb-3">
            <x-core::icon name="ti ti-receipt-2" class="me-1" />
            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.title') }}
        </h4>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="row g-2">
                    <div class="col-sm-4">
                        <label class="form-label small mb-1" for="hw_shipping_cost">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_cost') }}
                        </label>
                        <input type="number" step="1" min="0" class="form-control form-control-sm hw-fee"
                            id="hw_shipping_cost" value="{{ (float) $hwQuote->shipping_cost }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small mb-1" for="hw_fulfill_fee">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.fulfill_fee') }}
                        </label>
                        <input type="number" step="1" min="0" class="form-control form-control-sm hw-fee"
                            id="hw_fulfill_fee" value="{{ (float) $hwQuote->fulfill_fee }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small mb-1" for="hw_packing_fee">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.packing_fee') }}
                        </label>
                        <input type="number" step="1" min="0" class="form-control form-control-sm hw-fee"
                            id="hw_packing_fee" value="{{ (float) $hwQuote->packing_fee }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small mb-1" for="hw_deposit_percent">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.deposit_percent') }}
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="1" min="0" max="100" class="form-control hw-fee"
                                id="hw_deposit_percent" value="{{ (int) ($hwQuote->deposit_percent ?: 50) }}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <label class="form-label small mb-1" for="hw_delivery_date">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.delivery_date') }}
                        </label>
                        <input type="date" class="form-control form-control-sm" id="hw_delivery_date"
                            value="{{ $hwQuote->expected_delivery_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small mb-1" for="hw_quote_note">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.quote.note') }}
                        </label>
                        <input type="text" class="form-control form-control-sm" id="hw_quote_note"
                            value="{{ $hwQuote->note }}">
                    </div>
                </div>

                <small class="text-muted d-block mt-2">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.quote.shipping_help') }}
                </small>
            </div>

            {{-- Only the two milestone figures: the order totals panel below already
                 shows subtotal and grand total, no point repeating them. --}}
            <div class="col-lg-5">
                <div class="bg-white border rounded p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.total') }}</span>
                        <strong id="hw-sum-total">—</strong>
                    </div>
                    <div class="d-flex justify-content-between text-primary mb-1">
                        <span>{{ trans('plugins/handmade-workflow::handmade-workflow.quote.deposit') }}</span>
                        <strong id="hw-sum-deposit">—</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.quote.final') }}</span>
                        <strong id="hw-sum-final">—</strong>
                    </div>

                    <button type="button" class="btn btn-primary w-100 mt-3" id="hw-send-quote">
                        <x-core::icon name="ti ti-send" class="me-1" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.quote.save') }}
                    </button>
                </div>
            </div>
        </div>
    </x-core::card.body>

    <script>
        (function () {
            const root = document.getElementById('hw-quote-editor')
            if (!root) return

            const decimals = parseInt(root.dataset.hwDecimals || 0, 10)
            const symbol = root.dataset.hwSymbol || ''
            const fmt = (n) => n.toLocaleString('vi-VN', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            }) + symbol

            const num = (el) => parseFloat(el?.value || 0) || 0
            const priceInputs = () => document.querySelectorAll('.hw-item-price')

            function recalc() {
                let products = 0

                priceInputs().forEach((input) => {
                    const row = input.closest('tr')
                    const qtyInput = row?.querySelector('.hw-item-qty')
                    const qty = qtyInput
                        ? (parseInt(qtyInput.value, 10) || 0)
                        : parseInt(input.dataset.hwQty || 0, 10)
                    const line = num(input) * qty
                    products += line

                    const cell = row?.querySelector('.hw-line-total')
                    if (cell) cell.textContent = fmt(line)
                })

                const shipping = num(document.getElementById('hw_shipping_cost'))
                const total = products + shipping
                    + num(document.getElementById('hw_fulfill_fee'))
                    + num(document.getElementById('hw_packing_fee'))

                // Same formula as the server: deposit = a share of the products + shipping.
                const percent = num(document.getElementById('hw_deposit_percent'))
                const deposit = Math.round(products * (percent / 100) * 100) / 100 + shipping

                document.getElementById('hw-sum-total').textContent = fmt(total)
                document.getElementById('hw-sum-deposit').textContent = fmt(deposit)
                document.getElementById('hw-sum-final').textContent = fmt(Math.round((total - deposit) * 100) / 100)
            }

            document.addEventListener('input', (e) => {
                if (e.target.matches('.hw-item-price, .hw-item-qty, .hw-fee')) recalc()
            })

            document.getElementById('hw-send-quote').addEventListener('click', function () {
                const button = this
                const payload = {
                    items: Array.from(priceInputs()).map((input) => ({
                        id: input.dataset.hwItemId,
                        price: num(input),
                        qty: parseInt(input.closest('tr')?.querySelector('.hw-item-qty')?.value || 0, 10) || null,
                    })),
                    shipping_cost: num(document.getElementById('hw_shipping_cost')),
                    fulfill_fee: num(document.getElementById('hw_fulfill_fee')),
                    packing_fee: num(document.getElementById('hw_packing_fee')),
                    deposit_percent: num(document.getElementById('hw_deposit_percent')),
                    expected_delivery_date: document.getElementById('hw_delivery_date').value || null,
                    note: document.getElementById('hw_quote_note').value || null,
                }

                $httpClient.make()
                    .withButtonLoading(button)
                    .post(root.dataset.hwUrl, payload)
                    .then(({ data }) => {
                        Botble.showSuccess(data.message)
                        window.location.reload()
                    })
            })

            recalc()
        })()
    </script>
@endif
