<x-core::card class="mb-3">
    <x-core::card.header>
        <x-core::card.title class="d-flex align-items-center gap-2">
            <x-core::icon name="ti ti-gift-card" class="text-success" />
            {{ trans('plugins/e-wallet::gift-card.admin.order_gift_card') }}
        </x-core::card.title>
    </x-core::card.header>

    <x-core::card.body>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">{{ trans('plugins/e-wallet::gift-card.admin.code_used') }}:</span>
            <code class="badge bg-success-lt">{{ $giftCardCode }}</code>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">{{ trans('plugins/e-wallet::gift-card.admin.discount_applied') }}:</span>
            <strong class="text-success">-{{ format_price($giftCardDiscount / 100, $giftCardCurrency) }}</strong>
        </div>
        <div class="mt-3">
            <a href="{{ route('e-wallet.gift-cards.show', $giftCardId) }}" class="btn btn-sm btn-outline-success w-100">
                <x-core::icon name="ti ti-eye" class="me-1" />
                {{ trans('plugins/e-wallet::gift-card.admin.view_gift_card') }}
            </a>
        </div>
    </x-core::card.body>
</x-core::card>
