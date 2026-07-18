<x-plugins-payment::payment-method
    :name="E_WALLET_PAYMENT_METHOD_NAME"
    :paymentName="get_payment_setting('name', E_WALLET_PAYMENT_METHOD_NAME, trans('plugins/e-wallet::e-wallet.checkout.pay_with_wallet'))"
    :description="get_payment_setting('description', E_WALLET_PAYMENT_METHOD_NAME, trans('plugins/e-wallet::e-wallet.checkout.wallet_payment_description'))"
>
    @if ($isLoggedIn ?? false)
        <div class="d-flex align-items-center gap-2 mt-2 py-2 px-3 rounded" style="background: rgba(16, 185, 129, 0.06); border: 1px solid rgba(16, 185, 129, 0.2);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"></path>
                <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4"></path>
            </svg>
            <span class="flex-grow-1" style="font-size: 0.8125rem; color: #6b7280;">{{ trans('plugins/e-wallet::e-wallet.checkout.current_balance') }}</span>
            <span style="font-weight: 600; font-size: 0.9375rem; color: #10b981;">{{ $formattedBalance }}</span>
        </div>

        @if (!$canPay)
            <div class="d-flex align-items-center gap-2 mt-2 py-2 px-3 rounded" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2);">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4"></path>
                    <path d="M12 17h.01"></path>
                    <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z"></path>
                </svg>
                <span style="font-size: 0.8125rem; color: #b45309;">{{ trans('plugins/e-wallet::e-wallet.checkout.insufficient_balance') }}</span>
            </div>
        @endif
    @else
        <div class="d-flex align-items-center gap-2 mt-2 py-2 px-3 rounded" style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2);">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
            </svg>
            <span style="font-size: 0.8125rem; color: #1d4ed8;">
                <a href="{{ route('customer.login') }}" class="text-decoration-underline" style="color: #1d4ed8;">{{ trans('plugins/e-wallet::e-wallet.checkout.login_to_use_wallet') }}</a>
            </span>
        </div>
    @endif
</x-plugins-payment::payment-method>
