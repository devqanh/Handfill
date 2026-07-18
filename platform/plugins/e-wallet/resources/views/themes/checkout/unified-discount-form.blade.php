@php
    $appliedCouponCode = session('applied_coupon_code');
    $appliedGiftCard = app(\Botble\EWallet\Services\GiftCardCheckoutService::class)->getApplied();
    $giftCardDiscount = (float) session('gift_card_discount', 0);
    $giftCardCurrency = isset($appliedGiftCard['currency_code'])
        ? get_all_currencies()->firstWhere('title', $appliedGiftCard['currency_code'])
        : null;
    $bothApplied = $appliedCouponCode && $appliedGiftCard && $giftCardDiscount > 0;
    $hasGiftCard = $appliedGiftCard && $giftCardDiscount > 0;
@endphp

@if (empty($isMobile))
    {{-- Desktop: Unified Discount Form --}}
    <div id="unified-discount-section">
        @if (!empty($discounts) && $discounts->isNotEmpty())
            <div class="checkout__coupon-section">
                <div class="checkout__coupon-heading">
                    <img width="32" height="32" src="{{ asset('vendor/core/plugins/ecommerce/images/coupon-code.gif') }}" alt="coupon code icon">
                    {{ trans('plugins/ecommerce::discount.coupon_codes_count', ['count' => $discounts->count()]) }}
                </div>

                <div class="checkout__coupon-list">
                    @foreach ($discounts as $discount)
                        <div @class(['checkout__coupon-item', 'active' => $appliedCouponCode && $appliedCouponCode === $discount->code])>
                            <div class="checkout__coupon-item-icon"></div>
                            <div class="checkout__coupon-item-content">
                                {!! apply_filters('checkout_discount_item_before', null, $discount) !!}

                                <div class="checkout__coupon-item-title">
                                    @if ($discount->type_option !== 'shipping')
                                        <h4>{{ $discount->type_option == 'percentage' ? $discount->value . '%' : format_price($discount->value) }}</h4>
                                    @endif

                                    @if($discount->quantity > 0)
                                        <span class="checkout__coupon-item-count">
                                            ({{ trans('plugins/ecommerce::discount.left_quantity', ['left' => $discount->left_quantity]) }})
                                        </span>
                                    @endif
                                </div>
                                <div class="checkout__coupon-item-description">
                                    {!! BaseHelper::clean($discount->description ?: get_discount_description($discount)) !!}
                                </div>
                                <div class="checkout__coupon-item-code">
                                    <span>{{ $discount->code }}</span>
                                    @if (!$appliedCouponCode || $appliedCouponCode !== $discount->code)
                                        <button type="button" data-bb-toggle="apply-unified-code" data-discount-code="{{ $discount->code }}">
                                            {{ trans('plugins/ecommerce::discount.apply') }}
                                        </button>
                                    @else
                                        <button type="button" class="unified-remove-btn" data-remove-type="coupon">
                                            {{ trans('plugins/ecommerce::discount.remove') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($appliedCouponCode || $hasGiftCard)
            <div class="unified-applied-states mb-3">
                @if ($appliedCouponCode)
                    <div class="d-flex align-items-center gap-3 py-2 px-3 rounded mb-2" style="background: #eff6ff; border: 1px solid #bfdbfe;">
                        <span class="d-flex align-items-center justify-content-center rounded" style="width: 32px; height: 32px; background: rgba(59, 130, 246, 0.1); flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 5v2"/><path d="M15.5 11h2.5l-4 7"/><path d="M9 5v2"/><path d="M3 18l4-7h2.5"/><path d="M9 11l-1 2"/><path d="M15 11l-1 2"/>
                            </svg>
                        </span>
                        <div class="flex-grow-1 d-flex justify-content-between align-items-center">
                            <div>
                                <span style="color: #1e40af; font-size: 0.8rem; font-weight: 500;">{{ trans('plugins/e-wallet::gift-card.checkout.coupon_label') }}</span>
                                <code style="color: #3b82f6; font-size: 0.8rem; margin-left: 6px;">{{ $appliedCouponCode }}</code>
                            </div>
                            <button type="button" class="btn btn-sm p-1 unified-remove-btn" data-remove-type="coupon" style="color: #ef4444; border: 1px solid #fecaca; background: #fef2f2; border-radius: 4px; line-height: 1;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if ($hasGiftCard)
                    @php
                        $remainingBalance = ($appliedGiftCard['balance'] / 100) - $giftCardDiscount;
                    @endphp
                    <div class="d-flex align-items-center gap-3 py-2 px-3 rounded mb-2" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                        <span class="d-flex align-items-center justify-content-center rounded" style="width: 32px; height: 32px; background: rgba(16, 185, 129, 0.1); flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 8m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/>
                                <path d="M12 8v-2a2 2 0 0 1 2 -2h0a2 2 0 0 1 2 2v2"/>
                                <path d="M12 8v-2a2 2 0 0 0 -2 -2h0a2 2 0 0 0 -2 2v2"/>
                                <path d="M12 8v12"/>
                            </svg>
                        </span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span style="color: #166534; font-size: 0.8rem; font-weight: 500;">{{ trans('plugins/e-wallet::gift-card.checkout.gift_card_label') }}</span>
                                    <code style="color: #10b981; font-size: 0.8rem; margin-left: 6px;">{{ $appliedGiftCard['code'] }}</code>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-weight: 600; font-size: 0.95rem; color: #10b981;">-{{ $appliedGiftCard['formatted_discount'] ?? format_price($giftCardDiscount, $giftCardCurrency) }}</span>
                                    <button type="button" class="btn btn-sm p-1 unified-remove-btn" data-remove-type="gift_card" style="color: #ef4444; border: 1px solid #fecaca; background: #fef2f2; border-radius: 4px; line-height: 1;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            @if($remainingBalance > 0)
                                <small style="color: #9ca3af; font-size: 0.7rem;">{{ trans('plugins/e-wallet::gift-card.checkout.remaining_balance', ['balance' => format_price($remainingBalance, $giftCardCurrency)]) }}</small>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if (!$bothApplied)
            <div class="unified-discount-input">
                <div class="d-flex gap-2">
                    <input
                        type="text"
                        class="form-control form-control-sm text-uppercase"
                        name="discount_code"
                        id="unified-discount-code"
                        placeholder="{{ trans('plugins/e-wallet::gift-card.checkout.unified_placeholder') }}"
                        maxlength="255"
                        style="font-size: 0.875rem;"
                    >
                    <button type="button" class="btn btn-sm unified-apply-btn" id="unified-apply-btn" style="background: #3b82f6; color: white; font-size: 0.875rem; white-space: nowrap;">
                        {{ trans('plugins/e-wallet::gift-card.checkout.unified_apply') }}
                    </button>
                </div>
                <div id="unified-discount-error" class="mt-1" style="display: none; color: #ef4444; font-size: 0.75rem;"></div>
            </div>
        @endif
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var unifiedSection = document.getElementById('unified-discount-section');
        if (!unifiedSection) return;

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) return;
        csrfToken = csrfToken.content;

        var applyUrl = @json(route('public.gift-card.unified-discount.apply'));
        var removeCouponUrl = @json(route('public.gift-card.unified-discount.remove-coupon'));
        var removeGiftCardUrl = @json(route('public.gift-card.unified-discount.remove-gift-card'));

        function refreshCheckoutSections() {
            fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) { return response.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');

                var newOrderInfo = doc.querySelector('.checkout-order-info');
                var oldOrderInfo = document.querySelector('.checkout-order-info');
                if (newOrderInfo && oldOrderInfo) {
                    oldOrderInfo.innerHTML = newOrderInfo.innerHTML;
                }

                var newCartWrapper = doc.querySelector('.cart-item-wrapper');
                var oldCartWrapper = document.querySelector('.cart-item-wrapper');
                if (newCartWrapper && oldCartWrapper) {
                    oldCartWrapper.innerHTML = newCartWrapper.innerHTML;
                }

                var newUnified = doc.getElementById('unified-discount-section');
                var oldUnified = document.getElementById('unified-discount-section');
                if (newUnified && oldUnified) {
                    oldUnified.outerHTML = newUnified.outerHTML;
                    initUnifiedDiscountHandlers();
                }

                var newCouponSection = doc.querySelector('.checkout__coupon-section');
                var oldCouponSection = document.querySelector('.checkout__coupon-section');
                if (newCouponSection && oldCouponSection) {
                    oldCouponSection.outerHTML = newCouponSection.outerHTML;
                } else if (!newCouponSection && oldCouponSection) {
                    oldCouponSection.remove();
                }
            })
            .catch(function() {
                window.location.reload();
            });
        }

        function initUnifiedDiscountHandlers() {
            var applyBtn = document.getElementById('unified-apply-btn');
            var codeInput = document.getElementById('unified-discount-code');

            if (applyBtn && codeInput) {
                var applyDiscount = function() {
                    var code = codeInput.value.trim();
                    var errorDiv = document.getElementById('unified-discount-error');

                    if (!code) return;

                    applyBtn.disabled = true;
                    applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    if (errorDiv) errorDiv.style.display = 'none';

                    fetch(applyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ discount_code: code })
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.error) {
                            if (errorDiv) {
                                errorDiv.textContent = data.message;
                                errorDiv.style.display = 'block';
                            }
                            applyBtn.disabled = false;
                            applyBtn.textContent = @json(trans('plugins/e-wallet::gift-card.checkout.unified_apply'));
                        } else {
                            var eventName = (data.data && data.data.type === 'gift_card') ? 'discount:applied' : 'coupon:applied';
                            document.dispatchEvent(new CustomEvent(eventName, { detail: data }));
                            refreshCheckoutSections();
                        }
                    })
                    .catch(function() {
                        if (errorDiv) {
                            errorDiv.textContent = @json(trans('plugins/e-wallet::gift-card.errors.check_failed'));
                            errorDiv.style.display = 'block';
                        }
                        applyBtn.disabled = false;
                        applyBtn.textContent = @json(trans('plugins/e-wallet::gift-card.checkout.unified_apply'));
                    });
                };

                applyBtn.addEventListener('click', applyDiscount);
                codeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyDiscount();
                    }
                });
            }

            document.querySelectorAll('[data-bb-toggle="apply-unified-code"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var code = this.getAttribute('data-discount-code');
                    if (!code) return;

                    var codeInput = document.getElementById('unified-discount-code');
                    if (codeInput) {
                        codeInput.value = code;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                    fetch(applyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ discount_code: code })
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.error) {
                            var errorDiv = document.getElementById('unified-discount-error');
                            if (errorDiv) {
                                errorDiv.textContent = data.message;
                                errorDiv.style.display = 'block';
                            }
                            btn.disabled = false;
                            btn.textContent = @json(trans('plugins/ecommerce::discount.apply'));
                        } else {
                            document.dispatchEvent(new CustomEvent('coupon:applied', { detail: data }));
                            refreshCheckoutSections();
                        }
                    })
                    .catch(function() {
                        btn.disabled = false;
                        btn.textContent = @json(trans('plugins/ecommerce::discount.apply'));
                    });
                });
            });

            document.querySelectorAll('.unified-remove-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var removeType = this.getAttribute('data-remove-type');
                    var url = removeType === 'gift_card' ? removeGiftCardUrl : removeCouponUrl;

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></span>';

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        var eventName = removeType === 'gift_card' ? 'discount:removed' : 'coupon:removed';
                        document.dispatchEvent(new CustomEvent(eventName, { detail: data }));
                        refreshCheckoutSections();
                    })
                    .catch(function() {
                        window.location.reload();
                    });
                });
            });
        }

        initUnifiedDiscountHandlers();
    });
    </script>
@else
    {{-- Mobile: Unified Discount Bottom Sheet (reuses ecommerce coupon sheet structure) --}}
    <div
        class="offcanvas offcanvas-bottom mobile-coupon-sheet"
        id="mobile-coupon-sheet"
        tabindex="-1"
        aria-labelledby="mobile-coupon-sheet-label"
        data-coupon-apply-url="{{ route('public.coupon.apply') }}"
    >
        <div class="mobile-coupon-sheet__drag-handle">
            <span></span>
        </div>
        <div class="offcanvas-header border-0 pb-0">
            <h5 class="offcanvas-title" id="mobile-coupon-sheet-label">
                <img width="24" height="24" src="{{ asset('vendor/core/plugins/ecommerce/images/coupon-code.gif') }}" alt="coupon code icon" class="me-2">
                {{ trans('plugins/e-wallet::gift-card.checkout.unified_placeholder') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ trans('plugins/ecommerce::discount.close') }}"></button>
        </div>
        <div class="offcanvas-body pt-2">
            {{-- Applied gift card state --}}
            @if ($hasGiftCard)
                @php
                    $remainingBalance = ($appliedGiftCard['balance'] / 100) - $giftCardDiscount;
                @endphp
                <div class="d-flex align-items-center gap-3 py-2 px-3 rounded mb-3" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                    <span class="d-flex align-items-center justify-content-center rounded" style="width: 32px; height: 32px; background: rgba(16, 185, 129, 0.1); flex-shrink: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 8m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/>
                            <path d="M12 8v-2a2 2 0 0 1 2 -2h0a2 2 0 0 1 2 2v2"/>
                            <path d="M12 8v-2a2 2 0 0 0 -2 -2h0a2 2 0 0 0 -2 2v2"/>
                            <path d="M12 8v12"/>
                        </svg>
                    </span>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span style="color: #166534; font-size: 0.8rem; font-weight: 500;">{{ trans('plugins/e-wallet::gift-card.checkout.gift_card_label') }}</span>
                                <code style="color: #10b981; font-size: 0.8rem; margin-left: 6px;">{{ $appliedGiftCard['code'] }}</code>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span style="font-weight: 600; font-size: 0.95rem; color: #10b981;">-{{ $appliedGiftCard['formatted_discount'] ?? format_price($giftCardDiscount, $giftCardCurrency) }}</span>
                                <button type="button" class="btn btn-sm p-1 unified-mobile-remove-btn" data-remove-type="gift_card" style="color: #ef4444; border: 1px solid #fecaca; background: #fef2f2; border-radius: 4px; line-height: 1;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @if($remainingBalance > 0)
                            <small style="color: #9ca3af; font-size: 0.7rem;">{{ trans('plugins/e-wallet::gift-card.checkout.remaining_balance', ['balance' => format_price($remainingBalance, $giftCardCurrency)]) }}</small>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Unified manual input (coupon or gift card) --}}
            <div class="mobile-coupon-entry mb-3">
                @if (!$appliedCouponCode && !$bothApplied)
                    <div class="mobile-coupon-entry__card">
                        <div class="mobile-coupon-entry__icon">
                            <x-core::icon name="ti ti-ticket" />
                        </div>
                        <div class="mobile-coupon-entry__input">
                            <input
                                type="text"
                                class="form-control unified-mobile-input"
                                placeholder="{{ trans('plugins/e-wallet::gift-card.checkout.unified_placeholder') }}"
                                autocomplete="off"
                            >
                        </div>
                        <button
                            type="button"
                            class="btn btn-primary unified-mobile-apply-btn"
                        >
                            {{ trans('plugins/e-wallet::gift-card.checkout.unified_apply') }}
                        </button>
                    </div>
                    <div class="unified-mobile-error mt-1" style="display: none; color: #ef4444; font-size: 0.75rem; padding-left: 52px;"></div>
                @else
                    <div class="mobile-coupon-entry__applied">
                        <div class="mobile-coupon-entry__icon mobile-coupon-entry__icon--success">
                            <x-core::icon name="ti ti-check" />
                        </div>
                        <div class="mobile-coupon-entry__code">
                            <span class="badge">{{ $appliedCouponCode }}</span>
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm remove-coupon-code"
                            data-url="{{ route('public.coupon.remove') }}"
                        >
                            {{ trans('plugins/ecommerce::discount.remove') }}
                        </button>
                    </div>
                @endif
            </div>

            {{-- Coupon list --}}
            @if (!empty($discounts) && $discounts->isNotEmpty())
                <div class="mobile-coupon-divider mb-3">
                    <span>{{ trans('plugins/ecommerce::discount.or_select_coupon') }}</span>
                </div>

                <div class="mobile-coupon-list">
                    @foreach ($discounts as $discount)
                        <div
                            @class([
                                'mobile-coupon-item',
                                'border',
                                'rounded',
                                'mb-3',
                                'p-3',
                                'position-relative',
                                'active' => $appliedCouponCode && $appliedCouponCode === $discount->code,
                            ])
                            data-discount-code="{{ $discount->code }}"
                        >
                            @if ($appliedCouponCode && $appliedCouponCode === $discount->code)
                                <div class="position-absolute top-0 end-0 mt-2 me-2">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                        <x-core::icon name="ti ti-check" style="width: 14px; height: 14px;" />
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex align-items-start gap-3">
                                <div class="mobile-coupon-icon bg-primary bg-opacity-10 rounded p-2 flex-shrink-0">
                                    <x-core::icon name="ti ti-discount-2" class="text-primary" />
                                </div>
                                <div class="flex-grow-1">
                                    <div class="mobile-coupon-value mb-1">
                                        @if ($discount->type_option !== 'shipping')
                                            <h6 class="mb-0 fw-bold">
                                                {{ $discount->type_option == 'percentage' ? $discount->value . '%' : format_price($discount->value) }}
                                            </h6>
                                        @else
                                            <h6 class="mb-0 fw-bold">{{ trans('plugins/ecommerce::discount.free_shipping') }}</h6>
                                        @endif

                                        @if($discount->quantity > 0)
                                            <small>
                                                ({{ trans('plugins/ecommerce::discount.left_quantity', ['left' => $discount->left_quantity]) }})
                                            </small>
                                        @endif
                                    </div>

                                    <div class="mobile-coupon-description mb-2">
                                        <small>
                                            {!! BaseHelper::clean($discount->description ?: get_discount_description($discount)) !!}
                                        </small>
                                    </div>

                                    <div class="mobile-coupon-code d-flex align-items-center justify-content-between">
                                        <span class="badge">{{ $discount->code }}</span>
                                        @if (!$appliedCouponCode || $appliedCouponCode !== $discount->code)
                                            <button type="button" class="btn" data-bb-toggle="apply-coupon-code" data-discount-code="{{ $discount->code }}">
                                                {{ trans('plugins/ecommerce::discount.apply') }}
                                            </button>
                                        @else
                                            <button type="button" class="btn remove-coupon-code" data-url="{{ route('public.coupon.remove') }}">
                                                {{ trans('plugins/ecommerce::discount.remove') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="offcanvas-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="offcanvas">
                {{ trans('plugins/ecommerce::discount.close') }}
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sheet = document.getElementById('mobile-coupon-sheet');
        if (!sheet) return;

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) return;
        csrfToken = csrfToken.content;

        var unifiedApplyUrl = @json(route('public.gift-card.unified-discount.apply'));
        var removeGiftCardUrl = @json(route('public.gift-card.unified-discount.remove-gift-card'));

        function refreshMobileSheet() {
            fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) { return response.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');

                var newBody = doc.querySelector('#mobile-coupon-sheet .offcanvas-body');
                var oldBody = document.querySelector('#mobile-coupon-sheet .offcanvas-body');
                if (newBody && oldBody) {
                    oldBody.innerHTML = newBody.innerHTML;
                }

                var newOrderInfo = doc.querySelector('.checkout-order-info');
                var oldOrderInfo = document.querySelector('.checkout-order-info');
                if (newOrderInfo && oldOrderInfo) {
                    oldOrderInfo.innerHTML = newOrderInfo.innerHTML;
                }

                var newCartWrapper = doc.querySelector('.cart-item-wrapper');
                var oldCartWrapper = document.querySelector('.cart-item-wrapper');
                if (newCartWrapper && oldCartWrapper) {
                    oldCartWrapper.innerHTML = newCartWrapper.innerHTML;
                }

                var newBtn = doc.querySelector('.mobile-checkout-footer__coupon-btn');
                var oldBtn = document.querySelector('.mobile-checkout-footer__coupon-btn');
                if (newBtn && oldBtn) {
                    oldBtn.outerHTML = newBtn.outerHTML;
                }
            })
            .catch(function() {
                window.location.reload();
            });
        }

        $(document).on('click', '.unified-mobile-apply-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var input = btn.closest('.mobile-coupon-entry__card').find('.unified-mobile-input');
            var code = input.val().trim();
            var errorDiv = btn.closest('.mobile-coupon-entry').parent().find('.unified-mobile-error');

            if (!code) {
                input.focus();
                return;
            }

            var originalText = btn.text();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            if (errorDiv.length) errorDiv.hide();

            $.ajax({
                url: unifiedApplyUrl,
                type: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                data: JSON.stringify({ discount_code: code }),
                success: function(data) {
                    if (data.error) {
                        if (errorDiv.length) {
                            errorDiv.text(data.message).show();
                        }
                        btn.prop('disabled', false).text(originalText);
                    } else {
                        var eventName = (data.data && data.data.type === 'gift_card') ? 'discount:applied' : 'coupon:applied';
                        document.dispatchEvent(new CustomEvent(eventName, { detail: data }));
                        refreshMobileSheet();
                    }
                },
                error: function() {
                    if (errorDiv.length) {
                        errorDiv.text(@json(trans('plugins/e-wallet::gift-card.errors.check_failed'))).show();
                    }
                    btn.prop('disabled', false).text(originalText);
                }
            });
        });

        $(document).on('keypress', '.unified-mobile-input', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                $(this).closest('.mobile-coupon-entry__card').find('.unified-mobile-apply-btn').trigger('click');
            }
        });

        $(document).on('click', '.unified-mobile-remove-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            var removeType = btn.data('remove-type');

            if (removeType !== 'gift_card') return;

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" style="width: 14px; height: 14px;"></span>');

            $.ajax({
                url: removeGiftCardUrl,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                success: function(data) {
                    document.dispatchEvent(new CustomEvent('discount:removed', { detail: data }));
                    refreshMobileSheet();
                },
                error: function() {
                    window.location.reload();
                }
            });
        });
    });
    </script>
@endif

<div class="clearfix"></div>
