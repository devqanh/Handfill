@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::gift-card.purchase.success_title'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="bb-customer-card-list">
            <div class="bb-customer-card">
                <div class="bb-customer-card-header bg-success text-white">
                    <h4 class="bb-customer-card-title mb-0">
                        <x-core::icon name="ti ti-circle-check" class="me-2" />
                        {{ trans('plugins/e-wallet::gift-card.purchase.success_title') }}
                    </h4>
                </div>
                <div class="bb-customer-card-body text-center py-5">
                    <div class="mb-4">
                        <x-core::icon name="ti ti-gift" style="font-size: 4rem; color: var(--bs-success);" />
                    </div>

                    <h3 class="mb-3">{{ trans('plugins/e-wallet::gift-card.purchase.congratulations') }}</h3>
                    <p class="text-muted mb-4">{{ trans('plugins/e-wallet::gift-card.purchase.success_message') }}</p>

                    <div class="card bg-light mx-auto mb-4" style="max-width: 400px;">
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted">{{ trans('plugins/e-wallet::gift-card.purchase.card_value') }}</small>
                                <div class="fs-3 fw-bold text-success">{{ $giftCard->formatted_initial_value }}</div>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">{{ trans('plugins/e-wallet::gift-card.code') }}</small>
                                <div class="input-group">
                                    <input type="text"
                                           class="form-control form-control-lg text-center fw-bold font-monospace"
                                           id="gift-card-code"
                                           value="{{ $giftCard->code }}"
                                           readonly>
                                    <button class="btn btn-outline-primary" type="button" id="copy-code-btn">
                                        <x-core::icon name="ti ti-copy" />
                                    </button>
                                </div>
                            </div>

                            @if($giftCard->expires_at)
                                <small class="text-muted">
                                    {{ trans('plugins/e-wallet::gift-card.expires') }}: {{ $giftCard->expires_at->format('M d, Y') }}
                                </small>
                            @endif
                        </div>
                    </div>

                    @if($giftCard->recipient_email)
                        <div class="alert alert-info mx-auto" style="max-width: 400px;">
                            <x-core::icon name="ti ti-mail" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.purchase.email_sent_to', ['email' => $giftCard->recipient_email]) }}
                        </div>
                    @endif

                    @if($giftCard->recipient_name)
                        <p class="text-muted">
                            <strong>{{ trans('plugins/e-wallet::gift-card.purchase.recipient') }}:</strong>
                            {{ $giftCard->recipient_name }}
                        </p>
                    @endif

                    @if($giftCard->gift_message)
                        <div class="card mx-auto mb-4" style="max-width: 400px;">
                            <div class="card-body">
                                <small class="text-muted">{{ trans('plugins/e-wallet::gift-card.purchase.your_message') }}</small>
                                <p class="mb-0 fst-italic">"{{ $giftCard->gift_message }}"</p>
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="{{ route('customer.e-wallet.gift-card.purchase') }}" class="btn btn-primary">
                            <x-core::icon name="ti ti-gift" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.purchase.buy_another') }}
                        </a>
                        <a href="{{ route('customer.e-wallet.gift-card.my-cards') }}" class="btn btn-outline-primary">
                            <x-core::icon name="ti ti-cards" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.my_cards.title') }}
                        </a>
                        <a href="{{ route('customer.e-wallet.index') }}" class="btn btn-outline-secondary">
                            <x-core::icon name="ti ti-wallet" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.back_to_wallet') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.getElementById('copy-code-btn');
    const codeInput = document.getElementById('gift-card-code');

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
        } catch (err) {}
        document.body.removeChild(textArea);
        return Promise.resolve();
    }

    function showSuccess() {
        copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        setTimeout(function() {
            copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
        }, 2000);
    }

    if (copyBtn && codeInput) {
        copyBtn.addEventListener('click', function() {
            copyToClipboard(codeInput.value).then(showSuccess);
        });
    }
});
</script>
@endsection
