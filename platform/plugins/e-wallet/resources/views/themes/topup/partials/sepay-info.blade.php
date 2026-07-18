@php
    $isPaymentCompleted = $payment && $payment->status == \Botble\Payment\Enums\PaymentStatusEnum::COMPLETED;
@endphp

<style>
    .sepay-topup-container {
        max-width: 100%;
        margin: 1rem 0;
    }

    .sepay-topup-card {
        background-color: var(--bs-body-bg, #fff);
        border-radius: 12px;
        border: 1px solid var(--bs-primary);
        padding: 20px;
        margin-bottom: 20px;
    }

    .sepay-topup-heading {
        font-size: 16px;
        font-weight: 600;
        color: var(--bs-heading-color, #333);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sepay-topup-qr-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 20px;
    }

    .sepay-topup-qr-code {
        width: 220px;
        height: auto;
        border-radius: 8px;
        margin-top: 0.5rem;
    }

    .sepay-topup-qr-caption {
        font-size: 13px;
        color: var(--bs-secondary-color, #6c757d);
        text-align: center;
        font-weight: 500;
    }

    .sepay-topup-detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--bs-border-color, #dee2e6);
    }

    .sepay-topup-detail-row:last-child {
        border-bottom: none;
    }

    .sepay-topup-detail-label {
        color: var(--bs-secondary-color, #6c757d);
        font-size: 13px;
    }

    .sepay-topup-detail-value {
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sepay-topup-warning {
        background-color: rgba(255, 243, 205, 0.5);
        border-left: 4px solid #ffc107;
        border-radius: 6px;
        padding: 12px;
        margin-top: 12px;
        font-size: 13px;
        line-height: 1.5;
    }

    .sepay-topup-warning strong {
        color: #dc3545;
    }

    .sepay-topup-copy-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        color: var(--bs-secondary-color, #6c757d);
    }

    .sepay-topup-copy-btn svg {
        width: 14px;
        height: 14px;
    }

    .sepay-topup-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        padding: 20px;
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        border-radius: 8px;
        margin-top: 16px;
        text-align: center;
    }

    .sepay-topup-loading-status {
        font-weight: 600;
        font-size: 14px;
    }

    .sepay-topup-loading-progress {
        width: 100%;
        height: 4px;
        background-color: rgba(13, 110, 253, 0.1);
        border-radius: 2px;
        overflow: hidden;
    }

    .sepay-topup-loading-progress-bar {
        height: 100%;
        width: 30%;
        background-color: var(--primary-color, #0d6efd);
        border-radius: 2px;
        animation: sepay-topup-progress 2s infinite;
    }

    @keyframes sepay-topup-progress {
        0% { width: 0%; }
        50% { width: 70%; }
        100% { width: 100%; }
    }

    .sepay-topup-success {
        text-align: center;
        padding: 30px 20px;
        background-color: var(--bs-tertiary-bg, #f8f9fa);
        border-radius: 8px;
    }

    .sepay-topup-success svg {
        width: 48px;
        height: 48px;
        color: #198754;
        margin-bottom: 12px;
    }

    .sepay-topup-success h4 {
        font-size: 18px;
        color: #198754;
        margin-bottom: 0;
    }

    .sepay-topup-bank-logo {
        height: 50px;
        margin-bottom: 10px;
    }
</style>

<div id="fob-sepay-topup" class="sepay-topup-container">
    @if (!$isPaymentCompleted)
        <div id="sepay-topup-info">
            <div class="sepay-topup-card">
                <div class="sepay-topup-heading">
                    <x-core::icon name="ti ti-credit-card" />
                    <span>{{ trans('plugins/e-wallet::e-wallet.topup.sepay.payment_info') }}</span>
                </div>

                <div class="row">
                    <div class="col-md-5 sepay-topup-qr-container">
                        <div class="sepay-topup-qr-caption">{{ trans('plugins/e-wallet::e-wallet.topup.sepay.scan_qr') }}</div>
                        <img src="{{ $qrCodeUrl }}" alt="QR Code" class="sepay-topup-qr-code" id="sepayQrCodeImage">
                    </div>

                    <div class="col-md-7 sepay-topup-details">
                        @if(isset($bankInfo['bankLogo']) && $bankInfo['bankLogo'])
                            <img src="{{ $bankInfo['bankLogo'] }}" alt="{{ $bankInfo['bank'] }}" class="sepay-topup-bank-logo">
                        @endif
                        <div class="sepay-topup-detail-row">
                            <div class="sepay-topup-detail-label">{{ trans('plugins/e-wallet::e-wallet.topup.sepay.bank_name') }}</div>
                            <div class="sepay-topup-detail-value">{{ $bankInfo['bank'] }}</div>
                        </div>
                        <div class="sepay-topup-detail-row">
                            <div class="sepay-topup-detail-label">{{ trans('plugins/e-wallet::e-wallet.topup.sepay.account_holder') }}</div>
                            <div class="sepay-topup-detail-value">{{ $bankInfo['bankAccountHolder'] }}</div>
                        </div>
                        <div class="sepay-topup-detail-row">
                            <div class="sepay-topup-detail-label">{{ trans('plugins/e-wallet::e-wallet.topup.sepay.account_number') }}</div>
                            <div class="sepay-topup-detail-value">
                                {{ $bankInfo['bankAccountNumber'] }}
                                <button class="sepay-topup-copy-btn" data-clipboard="{{ $bankInfo['bankAccountNumber'] }}" data-bb-toggle="sepay-topup-copy">
                                    <x-core::icon name="ti ti-clipboard" />
                                </button>
                            </div>
                        </div>
                        <div class="sepay-topup-detail-row">
                            <div class="sepay-topup-detail-label">{{ trans('plugins/e-wallet::e-wallet.topup.sepay.transfer_content') }}</div>
                            <div class="sepay-topup-detail-value">
                                {{ $chargeId }}
                                <button class="sepay-topup-copy-btn" data-clipboard="{{ $chargeId }}" data-bb-toggle="sepay-topup-copy">
                                    <x-core::icon name="ti ti-clipboard" />
                                </button>
                            </div>
                        </div>
                        <div class="sepay-topup-detail-row">
                            <div class="sepay-topup-detail-label">{{ trans('plugins/e-wallet::e-wallet.topup.sepay.amount') }}</div>
                            <div class="sepay-topup-detail-value">
                                {{ $formattedOrderAmount = number_format($orderAmount, 0, ',', '.') . ' VND' }}
                                <button class="sepay-topup-copy-btn" data-clipboard="{{ $orderAmount }}" data-bb-toggle="sepay-topup-copy">
                                    <x-core::icon name="ti ti-clipboard" />
                                </button>
                            </div>
                        </div>
                        <div class="sepay-topup-warning">
                            <p class="mb-0">{!! trans('plugins/e-wallet::e-wallet.topup.sepay.warning', [
                                'charge_id' => '<strong>' . $chargeId . '</strong>',
                                'amount' => '<strong>' . $formattedOrderAmount . '</strong>',
                            ]) !!}</p>
                        </div>
                    </div>
                </div>

                <div class="sepay-topup-loading" data-bb-toggle="sepay-topup-status" data-url="{{ route('sepay.transactions.check') }}" data-charge-id="{{ $chargeId }}" data-topup-code="{{ $topupCode }}">
                    <div>
                        <div class="sepay-topup-loading-status">{{ trans('plugins/e-wallet::e-wallet.topup.sepay.waiting_payment') }}</div>
                    </div>
                    <div class="sepay-topup-loading-progress">
                        <div class="sepay-topup-loading-progress-bar"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div @style(['display: none' => !$isPaymentCompleted]) id="sepay-topup-status-done">
        <div class="sepay-topup-card sepay-topup-success">
            <x-core::icon name="ti ti-circle-check" />
            <h4>{{ trans('plugins/e-wallet::e-wallet.topup.sepay.payment_success') }}</h4>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const copyButtons = document.querySelectorAll('[data-bb-toggle="sepay-topup-copy"]');

        copyButtons.forEach((button) => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const textToCopy = this.getAttribute('data-clipboard');

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(textToCopy);
                } else {
                    sepayTopupUnsecuredCopy(textToCopy);
                }

                const originalIcon = this.innerHTML;
                this.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

                setTimeout(() => {
                    this.innerHTML = originalIcon;
                }, 1500);
            });
        });
    });

    let sepayTopupInterval = null;

    document.addEventListener('DOMContentLoaded', function() {
        const paymentStatus = document.querySelector('[data-bb-toggle="sepay-topup-status"]');

        if (paymentStatus) {
            sepayTopupInterval = setInterval(() => fetchSepayTopupStatus(paymentStatus), 3000);
        }
    });

    function fetchSepayTopupStatus(elm) {
        const url = elm.getAttribute('data-url');
        const chargeId = elm.getAttribute('data-charge-id');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ charge_id: chargeId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.data && result.data.status && result.data.status.value === 'completed') {
                document.getElementById('sepay-topup-status-done').style.display = 'block';
                const infoEl = document.getElementById('sepay-topup-info');
                if (infoEl) infoEl.remove();

                // Update the page UI
                const pendingIcon = document.querySelector('.topup-pending');
                if (pendingIcon) {
                    pendingIcon.classList.remove('topup-pending');
                    pendingIcon.classList.add('topup-success');
                }

                const resultIcon = document.querySelector('.result-icon.pending-icon');
                if (resultIcon) {
                    resultIcon.classList.remove('pending-icon');
                    resultIcon.classList.add('success-icon');
                    resultIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>';
                }

                const resultTitle = document.querySelector('.result-title');
                if (resultTitle) {
                    resultTitle.textContent = '{{ trans('plugins/e-wallet::e-wallet.topup.success_title') }}';
                }

                const resultMessage = document.querySelector('.result-message');
                if (resultMessage) {
                    resultMessage.textContent = '{{ trans('plugins/e-wallet::e-wallet.topup.success_message') }}';
                }

                clearInterval(sepayTopupInterval);

                // Reload page after short delay to update balance
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error checking payment status:', error);
        });
    }

    function sepayTopupUnsecuredCopy(textToCopy) {
        const textArea = document.createElement('textarea');
        textArea.value = textToCopy;
        textArea.style.position = 'absolute';
        textArea.style.left = '-999999px';
        document.body.append(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
        } catch (error) {
            console.error('Unable to copy to clipboard', error);
        }

        document.body.removeChild(textArea);
    }
</script>
