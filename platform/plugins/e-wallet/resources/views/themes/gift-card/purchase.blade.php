@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::gift-card.purchase.title'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="bb-customer-card-list">
            <div class="bb-customer-card">
                <div class="bb-customer-card-header">
                    <h4 class="bb-customer-card-title mb-0">
                        <x-core::icon name="ti ti-gift" class="me-2" />
                        {{ trans('plugins/e-wallet::gift-card.purchase.title') }}
                    </h4>
                </div>
                <div class="bb-customer-card-body">
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <x-core::icon name="ti ti-wallet" class="me-2" />
                                <strong>{{ trans('plugins/e-wallet::gift-card.purchase.wallet_balance') }}:</strong>
                                <span class="fs-5 fw-bold {{ $wallet->balance > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $wallet->formatted_balance }}
                                </span>
                            </div>
                            @if(app(\Botble\EWallet\Helpers\WalletHelper::class)->isTopUpEnabled())
                                <a href="{{ route('customer.e-wallet.topup.create') }}" class="btn btn-primary btn-sm">
                                    <x-core::icon name="ti ti-plus" class="me-1" />
                                    {{ trans('plugins/e-wallet::e-wallet.topup.title') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <p class="text-muted mb-4">{{ trans('plugins/e-wallet::gift-card.purchase.description') }}</p>

                    <form action="{{ route('customer.e-wallet.gift-card.purchase.store') }}" method="POST" id="purchase-form">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                {{ trans('plugins/e-wallet::gift-card.purchase.select_value') }}
                            </label>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach($predefinedValues as $value)
                                    <button type="button"
                                            class="btn btn-outline-primary value-btn"
                                            data-value="{{ $value / 100 }}">
                                        {{ format_price($value / 100) }}
                                    </button>
                                @endforeach
                                <button type="button" class="btn btn-outline-secondary value-btn" data-value="custom">
                                    {{ trans('plugins/e-wallet::gift-card.purchase.custom_amount') }}
                                </button>
                            </div>
                            <div id="custom-value-wrapper" style="display: none;">
                                <input type="number"
                                       class="form-control"
                                       id="custom-value-input"
                                       min="{{ $minValue / 100 }}"
                                       max="{{ $maxValue / 100 }}"
                                       step="0.01"
                                       placeholder="{{ trans('plugins/e-wallet::gift-card.purchase.enter_amount') }}">
                                <small class="text-muted">
                                    {{ trans('plugins/e-wallet::gift-card.purchase.value_range', [
                                        'min' => format_price($minValue / 100),
                                        'max' => format_price($maxValue / 100)
                                    ]) }}
                                </small>
                            </div>
                            <input type="hidden" name="value" id="selected-value" required>
                            @error('value')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div id="balance-warning" class="alert alert-warning mt-3" style="display: none;">
                                <x-core::icon name="ti ti-alert-triangle" class="me-1" />
                                {{ trans('plugins/e-wallet::gift-card.purchase.insufficient_balance_warning') }}
                                @if(app(\Botble\EWallet\Helpers\WalletHelper::class)->isTopUpEnabled())
                                    <a href="{{ route('customer.e-wallet.topup.create') }}" class="alert-link">
                                        {{ trans('plugins/e-wallet::gift-card.purchase.topup_now') }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-4">
                            <h5 class="mb-3">
                                <x-core::icon name="ti ti-mail" class="me-1" />
                                {{ trans('plugins/e-wallet::gift-card.purchase.recipient_info') }}
                                <small class="text-muted">({{ __('Optional') }})</small>
                            </h5>
                            <p class="text-muted small mb-3">{{ trans('plugins/e-wallet::gift-card.purchase.recipient_help') }}</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ trans('plugins/e-wallet::gift-card.purchase.recipient_name') }}</label>
                                    <input type="text"
                                           class="form-control @error('recipient_name') is-invalid @enderror"
                                           name="recipient_name"
                                           value="{{ old('recipient_name') }}"
                                           placeholder="{{ trans('plugins/e-wallet::gift-card.purchase.recipient_name_placeholder') }}">
                                    @error('recipient_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ trans('plugins/e-wallet::gift-card.purchase.recipient_email') }}</label>
                                    <input type="email"
                                           class="form-control @error('recipient_email') is-invalid @enderror"
                                           name="recipient_email"
                                           value="{{ old('recipient_email') }}"
                                           placeholder="{{ trans('plugins/e-wallet::gift-card.purchase.recipient_email_placeholder') }}">
                                    <small class="text-muted">{{ trans('plugins/e-wallet::gift-card.purchase.email_help') }}</small>
                                    @error('recipient_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ trans('plugins/e-wallet::gift-card.purchase.gift_message') }}</label>
                                    <textarea class="form-control @error('gift_message') is-invalid @enderror"
                                              name="gift_message"
                                              rows="3"
                                              maxlength="500"
                                              placeholder="{{ trans('plugins/e-wallet::gift-card.purchase.message_placeholder') }}">{{ old('gift_message') }}</textarea>
                                    <small class="text-muted">{{ trans('plugins/e-wallet::gift-card.purchase.message_max') }}</small>
                                    @error('gift_message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" id="purchase-btn" disabled>
                                <x-core::icon name="ti ti-wallet" class="me-1" />
                                {{ trans('plugins/e-wallet::gift-card.purchase.pay_with_wallet') }}
                            </button>
                            <a href="{{ route('customer.e-wallet.index') }}" class="btn btn-outline-secondary">
                                <x-core::icon name="ti ti-arrow-left" class="me-1" />
                                {{ trans('plugins/e-wallet::gift-card.back_to_wallet') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const valueBtns = document.querySelectorAll('.value-btn');
    const customWrapper = document.getElementById('custom-value-wrapper');
    const customValueInput = document.getElementById('custom-value-input');
    const selectedValue = document.getElementById('selected-value');
    const purchaseBtn = document.getElementById('purchase-btn');
    const balanceWarning = document.getElementById('balance-warning');
    const walletBalance = {{ $wallet->balance }};
    const minValue = {{ $minValue / 100 }};
    const maxValue = {{ $maxValue / 100 }};

    function checkBalance(valueCents) {
        if (valueCents > walletBalance) {
            balanceWarning.style.display = 'block';
            purchaseBtn.disabled = true;
            return false;
        }
        balanceWarning.style.display = 'none';
        return true;
    }

    valueBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            valueBtns.forEach(function(b) {
                b.classList.remove('active', 'btn-primary', 'btn-success');
                b.classList.add('btn-outline-primary', 'btn-outline-secondary');
            });
            this.classList.add('active', 'btn-success');
            this.classList.remove('btn-outline-primary', 'btn-outline-secondary');

            const value = this.dataset.value;
            if (value === 'custom') {
                customWrapper.style.display = 'block';
                customValueInput.focus();
                selectedValue.value = '';
                purchaseBtn.disabled = true;
                balanceWarning.style.display = 'none';
            } else {
                customWrapper.style.display = 'none';
                selectedValue.value = value;
                customValueInput.value = '';
                const valueCents = parseFloat(value) * 100;
                if (checkBalance(valueCents)) {
                    purchaseBtn.disabled = false;
                }
            }
        });
    });

    customValueInput.addEventListener('input', function() {
        const value = parseFloat(this.value);
        if (value && value >= minValue && value <= maxValue) {
            selectedValue.value = value;
            const valueCents = value * 100;
            if (checkBalance(valueCents)) {
                purchaseBtn.disabled = false;
            }
        } else {
            selectedValue.value = '';
            purchaseBtn.disabled = true;
            balanceWarning.style.display = 'none';
        }
    });
});
</script>
@endsection
