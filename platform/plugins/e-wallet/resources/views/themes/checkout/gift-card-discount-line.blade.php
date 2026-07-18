<div class="checkout-summary-row gift-card-discount d-flex justify-content-between py-2">
    <span>
        {{ trans('plugins/e-wallet::gift-card.checkout.discount_label') }}
        <code class="ms-1">{{ $giftCard['code'] }}</code>
    </span>
    <span class="text-success fw-bold">-{{ $giftCard['formatted_discount'] }}</span>
</div>
