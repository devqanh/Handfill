<div class="wallet-balance-info d-flex align-items-center gap-3 mb-3 py-3 px-3 rounded" style="background: #f8fafc; border: 1px solid #e5e7eb;">
    <span class="d-flex align-items-center justify-content-center rounded" style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); flex-shrink: 0;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"></path>
            <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4"></path>
        </svg>
    </span>
    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center">
            <span style="color: #6b7280; font-size: 0.875rem;">{{ trans('plugins/e-wallet::e-wallet.checkout.wallet_balance') }}</span>
            <span style="font-weight: 600; font-size: 1.125rem; color: #10b981;">{{ $formattedBalance }}</span>
        </div>
        <small style="color: #9ca3af; font-size: 0.75rem;">{{ trans('plugins/e-wallet::e-wallet.checkout.can_use_for_payment') }}</small>
    </div>
</div>
