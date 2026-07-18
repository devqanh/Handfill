@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::gift-card.my_cards.title'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="bb-customer-card-list">
            <div class="bb-customer-card">
                <div class="bb-customer-card-header d-flex justify-content-between align-items-center">
                    <h4 class="bb-customer-card-title mb-0">
                        <x-core::icon name="ti ti-cards" class="me-2" />
                        {{ trans('plugins/e-wallet::gift-card.my_cards.title') }}
                    </h4>
                    @if(gift_card_purchase_enabled())
                        <a href="{{ route('customer.e-wallet.gift-card.purchase') }}" class="btn btn-primary btn-sm">
                            <x-core::icon name="ti ti-plus" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.purchase.buy_gift_card') }}
                        </a>
                    @endif
                </div>
                <div class="bb-customer-card-body">
                    <div class="d-flex justify-content-end mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="showRedeemedToggle"
                                   {{ $showRedeemed ? 'checked' : '' }}
                                   onchange="window.location.href='{{ route('customer.e-wallet.gift-card.my-cards') }}?show_redeemed=' + (this.checked ? '1' : '0')">
                            <label class="form-check-label" for="showRedeemedToggle">
                                {{ trans('plugins/e-wallet::gift-card.my_cards.show_redeemed') }}
                            </label>
                        </div>
                    </div>

                    @if($purchasedCards->isEmpty())
                        <div class="text-center py-5">
                            <x-core::icon name="ti ti-gift-off" style="font-size: 3rem; color: var(--bs-secondary);" />
                            <p class="text-muted mt-3 mb-4">{{ trans('plugins/e-wallet::gift-card.my_cards.no_cards') }}</p>
                            @if(gift_card_purchase_enabled())
                                <a href="{{ route('customer.e-wallet.gift-card.purchase') }}" class="btn btn-primary">
                                    <x-core::icon name="ti ti-gift" class="me-1" />
                                    {{ trans('plugins/e-wallet::gift-card.purchase.buy_gift_card') }}
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach($purchasedCards as $card)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="gift-card-item {{ $card->balance <= 0 ? 'used' : '' }}">
                                        <div class="gift-card-header">
                                            <div class="gift-card-icon">
                                                <x-core::icon name="ti ti-gift" />
                                            </div>
                                            <div class="gift-card-status">
                                                {!! $card->status->toHtml() !!}
                                            </div>
                                        </div>
                                        <div class="gift-card-value">
                                            {{ $card->formatted_initial_value }}
                                        </div>
                                        <div class="gift-card-code">
                                            <code>{{ $card->code }}</code>
                                            <button type="button"
                                                    class="btn btn-sm btn-link copy-code"
                                                    data-code="{{ $card->code }}"
                                                    title="{{ __('Copy') }}">
                                                <x-core::icon name="ti ti-copy" />
                                            </button>
                                        </div>
                                        @if($card->balance > 0 && $card->balance < $card->initial_value)
                                            <div class="gift-card-balance">
                                                {{ trans('plugins/e-wallet::gift-card.table.balance') }}:
                                                <strong class="text-success">{{ $card->formatted_balance }}</strong>
                                            </div>
                                        @endif
                                        @if($card->recipient_name || $card->recipient_email)
                                            <div class="gift-card-recipient">
                                                <x-core::icon name="ti ti-user" class="me-1" />
                                                @if($card->recipient_name)
                                                    {{ $card->recipient_name }}
                                                @endif
                                                @if($card->recipient_email)
                                                    <small class="d-block text-muted">{{ $card->recipient_email }}</small>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="gift-card-footer">
                                            <small class="text-muted">
                                                {{ $card->created_at->format('M d, Y') }}
                                            </small>
                                            @if($card->recipient_email && $card->status->getValue() === \Botble\EWallet\Enums\GiftCardStatusEnum::ACTIVE)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary resend-email"
                                                        data-id="{{ $card->id }}"
                                                        title="{{ trans('plugins/e-wallet::gift-card.my_cards.resend_email') }}">
                                                    <x-core::icon name="ti ti-mail-forward" class="me-1" />
                                                    {{ trans('plugins/e-wallet::gift-card.email.resend') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {!! $purchasedCards->appends(['show_redeemed' => $showRedeemed ? '1' : '0'])->links() !!}
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('customer.e-wallet.index') }}" class="btn btn-outline-secondary">
                            <x-core::icon name="ti ti-arrow-left" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.back_to_wallet') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
.gift-card-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 20px;
    color: white;
    position: relative;
    overflow: hidden;
    min-height: 200px;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.gift-card-item::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.gift-card-item.used {
    background: linear-gradient(135deg, #868e96 0%, #495057 100%);
    opacity: 0.8;
}
.gift-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}
.gift-card-icon {
    font-size: 1.5rem;
    opacity: 0.9;
}
.gift-card-status .badge {
    font-size: 0.7rem;
}
.gift-card-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 10px;
}
.gift-card-code {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}
.gift-card-code code {
    background: rgba(255,255,255,0.2);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.9rem;
    color: white;
}
.gift-card-code .copy-code {
    color: white;
    opacity: 0.8;
    padding: 2px 6px;
}
.gift-card-code .copy-code:hover {
    opacity: 1;
}
.gift-card-balance {
    font-size: 0.85rem;
    margin-bottom: 10px;
    background: rgba(255,255,255,0.15);
    padding: 6px 10px;
    border-radius: 6px;
    display: inline-block;
}
.gift-card-balance .text-success {
    color: #90EE90 !important;
}
.gift-card-recipient {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 10px;
}
.gift-card-recipient .text-muted {
    color: rgba(255,255,255,0.7) !important;
}
.gift-card-footer {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 10px;
    border-top: 1px solid rgba(255,255,255,0.2);
}
.gift-card-footer .text-muted {
    color: rgba(255,255,255,0.7) !important;
}
.gift-card-footer .btn-outline-primary {
    color: white;
    border-color: rgba(255,255,255,0.5);
    font-size: 0.75rem;
    padding: 4px 10px;
}
.gift-card-footer .btn-outline-primary:hover {
    background: rgba(255,255,255,0.2);
    border-color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    document.querySelectorAll('.copy-code').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.dataset.code;
            const el = this;
            copyToClipboard(code).then(function() {
                el.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                setTimeout(function() {
                    el.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
                }, 2000);
            });
        });
    });

    document.querySelectorAll('.resend-email').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const originalHtml = this.innerHTML;
            const el = this;

            el.disabled = true;
            el.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('{{ url("customer/e-wallet/gift-card/my-cards") }}/' + id + '/resend', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) {
                    alert(data.message);
                } else {
                    alert(data.message);
                }
                el.disabled = false;
                el.innerHTML = originalHtml;
            })
            .catch(function() {
                alert('{{ trans("plugins/e-wallet::gift-card.errors.check_failed") }}');
                el.disabled = false;
                el.innerHTML = originalHtml;
            });
        });
    });
});
</script>
@endsection
