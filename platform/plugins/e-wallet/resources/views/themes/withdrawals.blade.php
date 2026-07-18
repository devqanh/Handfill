@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::withdrawal.title'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="bb-customer-card-list wallet-cards">
                    <div class="bb-customer-card wallet-balance-section mb-4">
                        <div class="bb-customer-card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="info-item">
                                        <span class="label">{{ trans('plugins/e-wallet::withdrawal.available_balance') }}</span>
                                        <span class="value {{ $wallet->balance >= 0 ? 'text-success' : 'text-danger' }}">{{ $wallet->formatted_balance }}</span>
                                    </div>
                                </div>
                                <div class="wallet-icon-wrapper">
                                    <x-core::icon name="ti ti-wallet" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(get_wallet_setting('enable_withdrawal', true))
                    @php
                        $enabledMethods = collect($payoutMethods)->filter(fn($method) => $method['is_enabled']);
                    @endphp

                    <div class="bb-customer-card-list account-settings-cards mb-4">
                        <div class="bb-customer-card">
                            <div class="bb-customer-card-header">
                                <h4 class="bb-customer-card-title mb-0">
                                    <x-core::icon name="ti ti-cash" class="me-2" />
                                    {{ trans('plugins/e-wallet::withdrawal.request') }}
                                </h4>
                            </div>
                            <div class="bb-customer-card-body">
                                @if($enabledMethods->isNotEmpty())
                                    <form method="POST" action="{{ route('customer.e-wallet.withdrawals.store') }}" id="withdrawal-form">
                                        @csrf

                                        <div class="mb-4">
                                            <label class="form-label fw-semibold">{{ trans('plugins/e-wallet::withdrawal.enter_amount') }}</label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text">{{ get_application_currency()->symbol }}</span>
                                                <input type="number"
                                                       class="form-control @error('amount') is-invalid @enderror"
                                                       id="amount"
                                                       name="amount"
                                                       min="{{ $minimumAmount }}"
                                                       max="{{ min($maximumAmount, $wallet->balance / 100) }}"
                                                       step="0.01"
                                                       value="{{ old('amount') }}"
                                                       placeholder="{{ trans('plugins/e-wallet::withdrawal.amount_placeholder') }}"
                                                       required>
                                            </div>
                                            @error('amount')
                                                <div class="text-danger small mt-2">
                                                    <x-core::icon name="ti ti-alert-circle" class="me-1" />
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                            <div class="text-muted small mt-2">
                                                <x-core::icon name="ti ti-info-circle" class="me-1" />
                                                {{ trans('plugins/e-wallet::withdrawal.amount_range', [
                                                    'min' => format_price($minimumAmount),
                                                    'max' => format_price($maximumAmount)
                                                ]) }}
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label fw-semibold">{{ trans('plugins/e-wallet::withdrawal.select_payment_method') }}</label>
                                            <div class="payment-method-cards">
                                                @foreach($enabledMethods as $method)
                                                    <div class="payment-method-card" data-method="{{ $method['key'] }}">
                                                        <input type="radio"
                                                               id="payment_{{ $method['key'] }}"
                                                               name="payment_channel"
                                                               value="{{ $method['key'] }}"
                                                               class="payment-method-input"
                                                               {{ old('payment_channel') === $method['key'] ? 'checked' : '' }}
                                                               required>
                                                        <label for="payment_{{ $method['key'] }}" class="payment-method-label">
                                                            <div class="payment-method-icon">
                                                                @if($method['key'] === 'paypal')
                                                                    <x-core::icon name="ti ti-brand-paypal" />
                                                                @elseif($method['key'] === 'bank_transfer')
                                                                    <x-core::icon name="ti ti-building-bank" />
                                                                @else
                                                                    <x-core::icon name="ti ti-dots" />
                                                                @endif
                                                            </div>
                                                            <div class="payment-method-info">
                                                                <h6 class="payment-method-title">{{ $method['label'] }}</h6>
                                                            </div>
                                                            <div class="payment-method-check">
                                                                <x-core::icon name="ti ti-check" />
                                                            </div>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="payment-details-wrapper mb-4" id="bank-info-wrapper" style="display: none;">
                                            <label for="bank_info" class="form-label fw-semibold">{{ trans('plugins/e-wallet::withdrawal.bank_information') }}</label>
                                            <textarea class="form-control"
                                                      id="bank_info"
                                                      name="bank_info"
                                                      rows="3"
                                                      placeholder="{{ trans('plugins/e-wallet::withdrawal.bank_info_placeholder') }}">{{ old('bank_info') }}</textarea>
                                        </div>

                                        <div class="payment-details-wrapper mb-4" id="paypal-wrapper" style="display: none;">
                                            <label for="paypal_id" class="form-label fw-semibold">{{ trans('plugins/e-wallet::withdrawal.paypal_id') }}</label>
                                            <input type="email"
                                                   class="form-control"
                                                   id="paypal_id"
                                                   name="paypal_id"
                                                   value="{{ old('paypal_id') }}"
                                                   placeholder="{{ trans('plugins/e-wallet::withdrawal.paypal_id_placeholder') }}">
                                        </div>

                                        <div class="payment-details-wrapper mb-4" id="other-wrapper" style="display: none;">
                                            <label for="payment_details" class="form-label fw-semibold">{{ trans('plugins/e-wallet::withdrawal.payment_details') }}</label>
                                            <textarea class="form-control"
                                                      id="payment_details"
                                                      name="payment_details"
                                                      rows="3"
                                                      placeholder="{{ trans('plugins/e-wallet::withdrawal.payment_details_placeholder') }}">{{ old('payment_details') }}</textarea>
                                        </div>

                                        <div class="withdrawal-summary mb-4" id="withdrawal-summary" style="display: none;">
                                            <div class="summary-row">
                                                <span class="summary-label">{{ trans('plugins/e-wallet::withdrawal.withdrawal_amount') }}</span>
                                                <span class="summary-value amount text-danger" id="summary-amount">-{{ get_application_currency()->symbol }}0.00</span>
                                            </div>
                                            <div class="summary-row">
                                                <span class="summary-label">{{ trans('plugins/e-wallet::withdrawal.remaining_balance') }}</span>
                                                <span class="summary-value" id="summary-remaining">{{ $wallet->formatted_balance }}</span>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg" id="submit-withdrawal">
                                                <x-core::icon name="ti ti-send" class="me-2" />
                                                {{ trans('plugins/e-wallet::withdrawal.submit_request') }}
                                            </button>
                                            <a href="{{ route('customer.e-wallet.index') }}" class="btn btn-outline-secondary">
                                                <x-core::icon name="ti ti-arrow-left" class="me-2" />
                                                {{ trans('plugins/e-wallet::withdrawal.back_to_wallet') }}
                                            </a>
                                        </div>
                                    </form>
                                @else
                                    <div class="alert alert-warning d-flex align-items-center mb-0">
                                        <x-core::icon name="ti ti-alert-triangle" class="me-2" />
                                        {{ trans('plugins/e-wallet::withdrawal.no_payment_methods_available') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bb-customer-card-list transaction-cards">
                    <div class="bb-customer-card">
                        <div class="bb-customer-card-header">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <h4 class="bb-customer-card-title mb-0">
                                    <x-core::icon name="ti ti-history" class="me-2" />
                                    {{ trans('plugins/e-wallet::withdrawal.history') }}
                                </h4>
                                @if($withdrawals->total() > 0)
                                    <span class="badge bg-secondary">{{ $withdrawals->total() }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="bb-customer-card-body p-0">
                            @if($withdrawals->count() > 0)
                                <div class="transaction-list withdrawal-list">
                                    @foreach($withdrawals as $withdrawal)
                                        <div class="transaction-item withdrawal-item">
                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                <div class="withdrawal-status-icon">
                                                    @php
                                                        $statusValue = $withdrawal->status->getValue();
                                                        $iconConfig = match($statusValue) {
                                                            'pending' => ['icon' => 'ti ti-clock', 'class' => 'pending'],
                                                            'processing' => ['icon' => 'ti ti-loader', 'class' => 'processing'],
                                                            'completed' => ['icon' => 'ti ti-circle-check', 'class' => 'completed'],
                                                            'rejected' => ['icon' => 'ti ti-circle-x', 'class' => 'rejected'],
                                                            'cancelled' => ['icon' => 'ti ti-ban', 'class' => 'cancelled'],
                                                            default => ['icon' => 'ti ti-minus', 'class' => 'default'],
                                                        };
                                                    @endphp
                                                    <div class="status-icon-wrapper {{ $iconConfig['class'] }}">
                                                        <x-core::icon name="{{ $iconConfig['icon'] }}" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                            <span class="fw-semibold">{{ trans('plugins/e-wallet::withdrawal.withdrawal_id') }} #{{ $withdrawal->id }}</span>
                                                            <span class="transaction-date">{{ $withdrawal->created_at->translatedFormat('M d, Y') }}</span>
                                                        </div>
                                                        <span class="transaction-amount debit">-{{ $withdrawal->formatted_amount }}</span>
                                                    </div>
                                                    <div class="transaction-meta mt-3">
                                                        <div class="row g-3">
                                                            <div class="col-6 col-md-4">
                                                                <div class="info-item">
                                                                    <span class="label">{{ trans('plugins/e-wallet::withdrawal.payment_method') }}</span>
                                                                    <span class="value">{{ $withdrawal->payment_channel?->label() ?? '---' }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-6 col-md-4">
                                                                <div class="info-item">
                                                                    <span class="label">{{ trans('plugins/e-wallet::withdrawal.status') }}</span>
                                                                    <span class="value">{!! $withdrawal->status->toHtml() !!}</span>
                                                                </div>
                                                            </div>
                                                            @if($withdrawal->processed_at)
                                                                <div class="col-12 col-md-4">
                                                                    <div class="info-item">
                                                                        <span class="label">{{ trans('plugins/e-wallet::withdrawal.processed_at') }}</span>
                                                                        <span class="value">{{ $withdrawal->processed_at->translatedFormat('M d, Y') }}</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($withdrawal->notes)
                                                        <div class="withdrawal-notes mt-3">
                                                            <x-core::icon name="ti ti-note" class="me-1" />
                                                            {{ $withdrawal->notes }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if($withdrawals->hasPages())
                                    <div class="d-flex justify-content-center p-3 border-top">
                                        {!! $withdrawals->links() !!}
                                    </div>
                                @endif
                            @else
                                <div class="bb-empty">
                                    <div class="bb-empty-img">
                                        <x-core::icon name="ti ti-wallet-off" />
                                    </div>
                                    <p class="bb-empty-title">{{ trans('plugins/e-wallet::withdrawal.no_withdrawals') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var paymentInputs = document.querySelectorAll('input[name="payment_channel"]');
    var bankInfoWrapper = document.getElementById('bank-info-wrapper');
    var paypalWrapper = document.getElementById('paypal-wrapper');
    var otherWrapper = document.getElementById('other-wrapper');
    var amountInput = document.getElementById('amount');
    var summary = document.getElementById('withdrawal-summary');
    var summaryAmount = document.getElementById('summary-amount');
    var summaryRemaining = document.getElementById('summary-remaining');
    var submitBtn = document.getElementById('submit-withdrawal');
    var form = document.getElementById('withdrawal-form');

    var currentBalance = {{ $wallet->balance / 100 }};
    var currencySymbol = '{{ get_application_currency()->symbol }}';
    var minAmount = {{ $minimumAmount }};
    var maxAmount = Math.min({{ $maximumAmount }}, currentBalance);

    function formatPrice(amount) {
        return currencySymbol + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateSummary() {
        if (!amountInput) return;
        var amount = parseFloat(amountInput.value) || 0;

        if (amount >= minAmount && amount <= maxAmount) {
            summary.style.display = 'block';
            summaryAmount.textContent = '-' + formatPrice(amount);
            summaryRemaining.textContent = formatPrice(currentBalance - amount);
        } else {
            summary.style.display = 'none';
        }
    }

    function showPaymentDetails(method) {
        if (bankInfoWrapper) bankInfoWrapper.style.display = 'none';
        if (paypalWrapper) paypalWrapper.style.display = 'none';
        if (otherWrapper) otherWrapper.style.display = 'none';

        if (method === 'bank_transfer' && bankInfoWrapper) {
            bankInfoWrapper.style.display = 'block';
        } else if (method === 'paypal' && paypalWrapper) {
            paypalWrapper.style.display = 'block';
        } else if (method === 'other' && otherWrapper) {
            otherWrapper.style.display = 'block';
        }
    }

    paymentInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            showPaymentDetails(this.value);
        });

        if (input.checked) {
            showPaymentDetails(input.value);
        }
    });

    if (amountInput) {
        amountInput.addEventListener('input', updateSummary);
        updateSummary();
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var amount = parseFloat(amountInput.value) || 0;

            if (amount > currentBalance) {
                if (typeof Botble !== 'undefined') {
                    Botble.showError('{{ trans('plugins/e-wallet::withdrawal.insufficient_balance') }}');
                } else {
                    alert('{{ trans('plugins/e-wallet::withdrawal.insufficient_balance') }}');
                }
                return false;
            }

            if (amount < minAmount) {
                if (typeof Botble !== 'undefined') {
                    Botble.showError('{{ trans('plugins/e-wallet::withdrawal.minimum_amount', ['amount' => format_price($minimumAmount)]) }}');
                } else {
                    alert('{{ trans('plugins/e-wallet::withdrawal.minimum_amount', ['amount' => format_price($minimumAmount)]) }}');
                }
                return false;
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('button-loading');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(new FormData(form))
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) {
                    if (typeof Botble !== 'undefined') {
                        Botble.showError(data.message);
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (typeof Botble !== 'undefined') {
                        Botble.showSuccess(data.message);
                    }
                    window.location.reload();
                }
            })
            .catch(function(error) {
                if (typeof Botble !== 'undefined') {
                    Botble.showError('An error occurred');
                } else {
                    alert('An error occurred');
                }
            })
            .finally(function() {
                submitBtn.disabled = false;
                submitBtn.classList.remove('button-loading');
            });
        });
    }
});
</script>

<style>
.payment-method-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.payment-method-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.payment-method-label {
    display: flex;
    align-items: center;
    padding: 1rem;
    background-color: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 0;
}

.payment-method-label:hover {
    border-color: #d1d5db;
    background-color: #f9fafb;
}

.payment-method-input:checked + .payment-method-label {
    border-color: var(--bs-primary, #206bc4);
    background-color: rgba(var(--bs-primary-rgb, 32, 107, 196), 0.03);
}

.payment-method-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: #f3f4f6;
    border-radius: 8px;
    margin-right: 0.875rem;
    flex-shrink: 0;
}

.payment-method-input:checked + .payment-method-label .payment-method-icon {
    background-color: var(--bs-primary, #206bc4);
}

.payment-method-icon .icon {
    width: 20px;
    height: 20px;
    color: #6b7280;
}

.payment-method-input:checked + .payment-method-label .payment-method-icon .icon {
    color: white;
}

.payment-method-info {
    flex: 1;
}

.payment-method-title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 500;
    color: #374151;
}

.payment-method-input:checked + .payment-method-label .payment-method-title {
    color: #111827;
    font-weight: 600;
}

.payment-method-check {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    opacity: 0;
    transition: all 0.2s ease;
}

.payment-method-input:checked + .payment-method-label .payment-method-check {
    border-color: var(--bs-primary, #206bc4);
    background-color: var(--bs-primary, #206bc4);
    opacity: 1;
}

.payment-method-check .icon {
    width: 12px;
    height: 12px;
    color: white;
}

.withdrawal-summary {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem 1.25rem;
}

.withdrawal-summary .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.withdrawal-summary .summary-row:not(:last-child) {
    border-bottom: 1px solid #e5e7eb;
}

.withdrawal-summary .summary-label {
    color: #6b7280;
    font-size: 0.875rem;
}

.withdrawal-summary .summary-value {
    font-weight: 600;
    color: #111827;
}

.withdrawal-summary .summary-value.amount {
    font-size: 1.125rem;
}

.withdrawal-status-icon {
    flex-shrink: 0;
}

.status-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: #f3f4f6;
}

.status-icon-wrapper .icon {
    width: 22px;
    height: 22px;
}

.status-icon-wrapper.pending {
    background-color: rgba(245, 158, 11, 0.1);
}
.status-icon-wrapper.pending .icon {
    color: #f59e0b;
}

.status-icon-wrapper.processing {
    background-color: rgba(59, 130, 246, 0.1);
}
.status-icon-wrapper.processing .icon {
    color: #3b82f6;
}

.status-icon-wrapper.completed {
    background-color: rgba(16, 185, 129, 0.1);
}
.status-icon-wrapper.completed .icon {
    color: #10b981;
}

.status-icon-wrapper.rejected {
    background-color: rgba(239, 68, 68, 0.1);
}
.status-icon-wrapper.rejected .icon {
    color: #ef4444;
}

.status-icon-wrapper.cancelled {
    background-color: rgba(107, 114, 128, 0.1);
}
.status-icon-wrapper.cancelled .icon {
    color: #6b7280;
}

.withdrawal-notes {
    padding: 0.625rem 0.875rem;
    background-color: #f8fafc;
    border-radius: 6px;
    border-left: 3px solid #3b82f6;
    font-size: 0.875rem;
    color: #6b7280;
}

.withdrawal-notes .icon {
    width: 14px;
    height: 14px;
    color: #9ca3af;
}

@media (max-width: 767.98px) {
    .payment-method-cards {
        grid-template-columns: 1fr;
    }

    .payment-method-label {
        padding: 0.875rem;
    }

    .payment-method-icon {
        width: 36px;
        height: 36px;
    }

    .payment-method-icon .icon {
        width: 18px;
        height: 18px;
    }

    .status-icon-wrapper {
        width: 40px;
        height: 40px;
    }

    .status-icon-wrapper .icon {
        width: 20px;
        height: 20px;
    }
}
</style>
@stop
