<style>
    .payfs-topup.fob-container {
        margin-top: 1rem;
    }

    .payfs-topup .fob-qr-code {
        text-align: center;
        margin-bottom: 20px;
    }

    .payfs-topup .fob-qr-code img {
        width: 220px;
        height: auto;
        margin: 0;
        padding: 0;
        border-radius: 8px;
    }

    .payfs-topup .fob-qr-intro {
        margin-bottom: 10px;
        font-size: 14px;
    }

    .payfs-topup .transaction-status-done {
        background-color: var(--bs-tertiary-bg);
        border: none;
        color: var(--primary-color);
    }

    .payfs-topup .transaction-status-done .icon {
        width: 40px;
        height: 40px;
    }
</style>

@php
    $isPaymentCompleted = $payment && $payment->status == \Botble\Payment\Enums\PaymentStatusEnum::COMPLETED;
@endphp

<div id="fob-payfs-topup" class="payfs-topup fob-container">
    @if (!$isPaymentCompleted)
        <div id="payfs-topup-info">
            <div class="fob-qr-intro text-center">
                <strong>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.scan_qr') }}</strong>
            </div>
            <div class="fob-qr-code">
                <figure>
                    <img src="{{ $imageUrl }}" alt="QR Code">
                </figure>
            </div>

            <div class="fob-qr-intro">
                <strong>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.manual_transfer') }}</strong>
            </div>
            <div class="fob-qr-information">
                <table class="table table-hover table-striped">
                    <tr>
                        <td>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.bank_name') }}</td>
                        <td>
                            <strong>{{ $bank }}</strong>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.account_holder') }}</td>
                        <td>
                            <strong>{{ $bankAccountHolder }}</strong>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.account_number') }}</td>
                        <td>
                            <strong>{{ $bankAccountNumber }}</strong>
                        </td>
                        <td class="text-end" style="width: 80px;">
                            <a href="javascript:void(0);" rel="nooper" class="ms-2" type="button" data-clipboard="{{ $bankAccountNumber }}" data-bb-toggle="payfs-copy">
                                <x-core::icon name="ti ti-clipboard" />
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.transfer_content') }}</td>
                        <td>
                            <strong>{{ $chargeId }}</strong>
                        </td>
                        <td class="text-end" style="width: 80px;">
                            <a href="javascript:void(0);" rel="nooper" class="ms-2" type="button" data-clipboard="{{ $chargeId }}" data-bb-toggle="payfs-copy">
                                <x-core::icon name="ti ti-clipboard" />
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.amount') }}</td>
                        <td>
                            <strong>{{ $formattedOrderAmount = number_format($orderAmount, 0, ',', '.') . ' VND' }}</strong>
                            @if (isset($originalCurrency) && $originalCurrency !== 'VND')
                                <br>
                                <small class="text-muted">({{ number_format($originalAmount, 2) }} {{ $originalCurrency }})</small>
                            @endif
                        </td>
                        <td class="text-end" style="width: 80px;">
                            <a href="javascript:void(0);" rel="nooper" class="ms-2" type="button" data-clipboard="{{ $orderAmount }}" data-bb-toggle="payfs-copy">
                                <x-core::icon name="ti ti-clipboard" />
                            </a>
                        </td>
                    </tr>
                </table>

                <div class="alert alert-warning">
                    <p class="mb-0">{!! trans('plugins/e-wallet::e-wallet.topup.payfs.warning', [
                        'charge_id' => '<strong class="text-danger">' . $chargeId . '</strong>',
                        'amount' => '<strong class="text-danger">' . $formattedOrderAmount . '</strong>',
                    ]) !!}</p>
                    @if (isset($originalCurrency) && $originalCurrency !== 'VND')
                        <p class="mt-2 mb-0"><em>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.currency_converted', ['currency' => $originalCurrency]) }}</em></p>
                    @endif
                </div>

                <div class="transaction-status text-center" data-bb-toggle="payfs-topup-status" data-url="{{ route('payfs.transactions.check') }}" data-charge-id="{{ $chargeId }}" data-topup-code="{{ $topupCode }}">
                    {{ trans('plugins/e-wallet::e-wallet.topup.payfs.waiting_payment') }} <img src="{{ url('vendor/core/plugins/fob-payfs/images/loading.gif') }}" width="20" height="20" alt="Loading">
                </div>
            </div>
        </div>
    @endif

    <div @style(['display: none' => !$isPaymentCompleted])
         id="payfs-topup-status-done">
        <div class="transaction-status-done card text-center pb-3 pt-2">
            <div class="p-4">
                <div class="mb-2">
                    <x-core::icon name="ti ti-circle-check" style="width: 48px; height: 48px; color: var(--bs-success);" />
                </div>
                <h4>{{ trans('plugins/e-wallet::e-wallet.topup.payfs.payment_success') }}</h4>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const copyButtons = document.querySelectorAll('[data-bb-toggle="payfs-copy"]');

        copyButtons.forEach((button) => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                const textToCopy = this.getAttribute('data-clipboard');
                payfsTopupCopyToClipboard(textToCopy);
            })
        })
    })

    let payfsTopupInterval = null;

    document.addEventListener('DOMContentLoaded', function() {
        const paymentStatus = document.querySelector('[data-bb-toggle="payfs-topup-status"]');

        if (paymentStatus) {
            payfsTopupInterval = setInterval(() => fetchPayfsTopupStatus(paymentStatus), 3000);
        }
    });

    function fetchPayfsTopupStatus(elm) {
        const url = elm.getAttribute('data-url');
        const chargeId = elm.getAttribute('data-charge-id');
        const topupCode = elm.getAttribute('data-topup-code');

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
                document.getElementById('payfs-topup-status-done').style.display = 'block';
                const infoEl = document.getElementById('payfs-topup-info');
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

                clearInterval(payfsTopupInterval);

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

    async function payfsTopupCopyToClipboard(textToCopy) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(textToCopy);
        } else {
            payfsTopupUnsecuredCopy(textToCopy);
        }

        alert('{{ trans('plugins/e-wallet::e-wallet.topup.payfs.copied') }}');
    }

    function payfsTopupUnsecuredCopy(textToCopy) {
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
