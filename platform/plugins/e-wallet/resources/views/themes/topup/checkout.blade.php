@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::e-wallet.topup.checkout'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="bb-customer-card-list order-cards">
                    <div class="bb-customer-card mb-4">
                        <div class="bb-customer-card-header">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h4 class="bb-customer-card-title mb-0">
                                    <x-core::icon name="ti ti-receipt" class="me-2" />
                                    {{ trans('plugins/e-wallet::e-wallet.topup.order_summary') }}
                                </h4>
                            </div>
                        </div>
                        <div class="bb-customer-card-body">
                            <div class="bb-customer-card-info">
                                <div class="row g-4">
                                    <div class="col-sm-6">
                                        <div class="info-item">
                                            <span class="label">
                                                <x-core::icon name="ti ti-hash" class="me-1" />
                                                {{ trans('plugins/e-wallet::e-wallet.topup.code') }}
                                            </span>
                                            <span class="value">{{ $topup->code }}</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="info-item">
                                            <span class="label">
                                                <x-core::icon name="ti ti-wallet" class="me-1" />
                                                {{ trans('plugins/e-wallet::e-wallet.topup.topup_amount') }}
                                            </span>
                                            <span class="value text-success">+{{ $topup->formatted_amount }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bb-customer-card-list account-settings-cards">
                    <div class="bb-customer-card">
                        <div class="bb-customer-card-header">
                            <h4 class="bb-customer-card-title mb-0">
                                <x-core::icon name="ti ti-credit-card" class="me-2" />
                                {{ trans('plugins/e-wallet::e-wallet.topup.select_payment') }}
                            </h4>
                        </div>
                        <div class="bb-customer-card-body">
                            <form action="{{ route('customer.e-wallet.topup.pay', $topup->code) }}"
                                  method="POST"
                                  id="topup-payment-form">
                                @csrf

                                <div class="payment-methods mb-4">
                                    {!! $paymentMethodsHtml !!}
                                </div>

                                @error('payment')
                                    <div class="alert alert-danger d-flex align-items-center mb-4">
                                        <x-core::icon name="ti ti-alert-circle" class="me-2" />
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="security-note mb-4">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="security-icon">
                                            <x-core::icon name="ti ti-shield-check" />
                                        </div>
                                        <div>
                                            <strong class="d-block mb-1">{{ trans('plugins/e-wallet::e-wallet.topup.secure_payment') }}</strong>
                                            <span class="text-muted small">{{ trans('plugins/e-wallet::e-wallet.topup.secure_payment_note') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <x-core::icon name="ti ti-lock" class="me-2" />
                                        {{ trans('plugins/e-wallet::e-wallet.topup.pay_now') }} - {{ $topup->formatted_amount }}
                                    </button>
                                    <a href="{{ route('customer.e-wallet.topup.create') }}" class="btn btn-outline-secondary">
                                        <x-core::icon name="ti ti-arrow-left" class="me-2" />
                                        {{ trans('plugins/e-wallet::e-wallet.topup.change_amount') }}
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

{!! apply_filters(PAYMENT_FILTER_FOOTER_ASSETS, '') !!}

<link href="{{ asset('vendor/core/plugins/payment/css/payment.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/core/plugins/e-wallet/js/wallet.js') }}"></script>

@if(!empty($selectedPaymentMethod))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var paymentMethod = @json($selectedPaymentMethod);
        if (typeof jQuery !== 'undefined') {
            var $radio = jQuery('input[name="payment_method"][value="' + paymentMethod + '"]');
            if ($radio.length) {
                $radio.prop('checked', true);
                jQuery('.payment_collapse_wrap').removeClass('collapse').removeClass('show').removeClass('active');
                $radio.closest('.list-group-item').find('.payment_collapse_wrap').addClass('show').addClass('active');
            }
        }
    });
</script>
@endif
@stop
