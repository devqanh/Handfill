@php
    $appliedGiftCard = app(\Botble\EWallet\Services\GiftCardCheckoutService::class)->getApplied();
    $giftCardDiscount = (float) session('gift_card_discount', 0);
    $giftCardCurrency = isset($appliedGiftCard['currency_code'])
        ? get_all_currencies()->firstWhere('title', $appliedGiftCard['currency_code'])
        : null;
@endphp

<div class="gift-card-section mb-3" id="gift-card-section">
    @if($appliedGiftCard && $giftCardDiscount > 0)
        @php
            $remainingBalance = ($appliedGiftCard['balance'] / 100) - $giftCardDiscount;
        @endphp
        <div class="d-flex align-items-center gap-3 py-3 px-3 rounded" style="background: #f8fafc; border: 1px solid #e5e7eb;">
            <span class="d-flex align-items-center justify-content-center rounded" style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 8m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"></path>
                    <path d="M12 8v-2a2 2 0 0 1 2 -2h0a2 2 0 0 1 2 2v2"></path>
                    <path d="M12 8v-2a2 2 0 0 0 -2 -2h0a2 2 0 0 0 -2 2v2"></path>
                    <path d="M12 8v12"></path>
                </svg>
            </span>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span style="color: #6b7280; font-size: 0.875rem;">{{ trans('plugins/e-wallet::gift-card.checkout.applied') }}</span>
                        <code style="color: #10b981; font-size: 0.875rem; margin-left: 8px;">{{ $appliedGiftCard['code'] }}</code>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-weight: 600; font-size: 1.125rem; color: #10b981;">-{{ $appliedGiftCard['formatted_discount'] ?? format_price($giftCardDiscount, $giftCardCurrency) }}</span>
                        <button type="button" class="btn btn-sm p-1" id="remove-gift-card" style="color: #ef4444; border: 1px solid #fecaca; background: #fef2f2; border-radius: 4px; line-height: 1;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6l-12 12"></path>
                                <path d="M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                @if($remainingBalance > 0)
                    <small style="color: #9ca3af; font-size: 0.75rem;">{{ trans('plugins/e-wallet::gift-card.checkout.remaining_balance', ['balance' => format_price($remainingBalance, $giftCardCurrency)]) }}</small>
                @endif
            </div>
        </div>
    @else
        <div class="d-flex align-items-center gap-3 py-3 px-3 rounded" style="background: #f8fafc; border: 1px solid #e5e7eb;">
            <span class="d-flex align-items-center justify-content-center rounded" style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 8m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"></path>
                    <path d="M12 8v-2a2 2 0 0 1 2 -2h0a2 2 0 0 1 2 2v2"></path>
                    <path d="M12 8v-2a2 2 0 0 0 -2 -2h0a2 2 0 0 0 -2 2v2"></path>
                    <path d="M12 8v12"></path>
                </svg>
            </span>
            <div class="flex-grow-1">
                <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 4px;">{{ trans('plugins/e-wallet::gift-card.checkout.title') }}</div>
                <div id="apply-gift-card-form" class="d-flex gap-2">
                    <input type="text"
                           class="form-control form-control-sm text-uppercase"
                           name="gift_card_code"
                           id="gift-card-code"
                           placeholder="{{ trans('plugins/e-wallet::gift-card.checkout.placeholder') }}"
                           maxlength="50"
                           style="font-size: 0.875rem;">
                    <button type="button" class="btn btn-sm" id="apply-gift-card-btn" style="background: #10b981; color: white; font-size: 0.875rem; white-space: nowrap;">
                        {{ trans('plugins/e-wallet::gift-card.checkout.apply') }}
                    </button>
                </div>
                <div id="gift-card-error" class="mt-1" style="display: none; color: #ef4444; font-size: 0.75rem;"></div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const applyBtn = document.getElementById('apply-gift-card-btn');
    const codeInput = document.getElementById('gift-card-code');
    const removeBtn = document.getElementById('remove-gift-card');

    if (applyBtn && codeInput) {
        const applyGiftCard = function() {
            const code = codeInput.value.trim();
            const errorDiv = document.getElementById('gift-card-error');

            if (!code) return;

            applyBtn.disabled = true;
            applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            errorDiv.style.display = 'none';

            fetch('{{ route("public.gift-card.checkout.apply") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ gift_card_code: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                    applyBtn.disabled = false;
                    applyBtn.textContent = '{{ trans("plugins/e-wallet::gift-card.checkout.apply") }}';
                } else {
                    window.location.reload();
                }
            })
            .catch(() => {
                errorDiv.textContent = '{{ trans("plugins/e-wallet::gift-card.errors.check_failed") }}';
                errorDiv.style.display = 'block';
                applyBtn.disabled = false;
                applyBtn.textContent = '{{ trans("plugins/e-wallet::gift-card.checkout.apply") }}';
            });
        };

        applyBtn.addEventListener('click', applyGiftCard);
        codeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyGiftCard();
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            fetch('{{ route("public.gift-card.checkout.remove") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(() => window.location.reload());
        });
    }
});
</script>
