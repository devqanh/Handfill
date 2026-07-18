@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', $isPending ? trans('plugins/e-wallet::e-wallet.topup.processing_title') : trans('plugins/e-wallet::e-wallet.topup.success_title'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="bb-customer-card-list account-settings-cards">
                    <div class="bb-customer-card topup-result-card {{ $isPending ? 'topup-pending' : 'topup-success' }}">
                        <div class="bb-customer-card-body text-center py-5">
                            @if($isPending)
                                <div class="result-icon pending-icon mb-4">
                                    <x-core::icon name="ti ti-clock" />
                                </div>
                                <h2 class="result-title mb-3">{{ trans('plugins/e-wallet::e-wallet.topup.processing_title') }}</h2>
                                <p class="result-message text-muted mb-4">
                                    {{ trans('plugins/e-wallet::e-wallet.topup.processing_message') }}
                                </p>
                            @else
                                <div class="result-icon success-icon mb-4">
                                    <x-core::icon name="ti ti-circle-check" />
                                </div>
                                <h2 class="result-title mb-3">{{ trans('plugins/e-wallet::e-wallet.topup.success_title') }}</h2>
                                <p class="result-message text-muted mb-4">
                                    {{ trans('plugins/e-wallet::e-wallet.topup.success_message') }}
                                </p>
                            @endif

                            <div class="transaction-receipt mb-4">
                                <div class="bb-customer-card-info">
                                    <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                        <span class="label">{{ trans('plugins/e-wallet::e-wallet.topup.code') }}</span>
                                        <span class="value fw-semibold">{{ $topup->code }}</span>
                                    </div>
                                    <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                        <span class="label">{{ trans('plugins/e-wallet::e-wallet.topup.amount') }}</span>
                                        <span class="value fw-semibold text-success">+{{ $topup->formatted_converted_amount }}</span>
                                    </div>
                                    <div class="info-item d-flex justify-content-between py-2">
                                        <span class="label">{{ trans('plugins/e-wallet::e-wallet.topup.payment_method') }}</span>
                                        <span class="value fw-semibold">{{ $topup->payment_method ? (get_payment_setting('name', $topup->payment_method) ?: ucfirst($topup->payment_method)) : '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            @if(!empty($bankTransferInfo))
                                <div class="bank-transfer-section mb-4">
                                    {!! $bankTransferInfo !!}
                                </div>
                            @endif

                            @if(!empty($payfsInfo))
                                <div class="payfs-section mb-4">
                                    @include('plugins/e-wallet::themes.topup.partials.payfs-info', array_merge($payfsInfo, ['topupCode' => $topup->code]))
                                </div>
                            @endif

                            @if(!empty($sepayInfo))
                                <div class="sepay-section mb-4">
                                    @include('plugins/e-wallet::themes.topup.partials.sepay-info', array_merge($sepayInfo, ['topupCode' => $topup->code]))
                                </div>
                            @endif

                            <div class="current-balance-display mb-4">
                                <div class="info-item text-center">
                                    <span class="label">{{ trans('plugins/e-wallet::e-wallet.wallet.current_balance') }}</span>
                                    <span class="value balance-value text-success">{{ $wallet->formatted_balance }}</span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="{{ route('customer.e-wallet.index') }}" class="btn btn-primary">
                                    <x-core::icon name="ti ti-wallet" class="me-2" />
                                    {{ trans('plugins/e-wallet::e-wallet.topup.back_to_wallet') }}
                                </a>
                                <a href="{{ route('customer.e-wallet.topup.create') }}" class="btn btn-outline-secondary">
                                    <x-core::icon name="ti ti-plus" class="me-2" />
                                    {{ trans('plugins/e-wallet::e-wallet.topup.topup_again') }}
                                </a>
                                @if($isPending && $topup->status->getValue() !== 'completed')
                                    <a href="{{ route('customer.e-wallet.topup.checkout', $topup->code) }}" class="btn btn-outline-secondary">
                                        <x-core::icon name="ti ti-arrow-left" class="me-2" />
                                        {{ trans('plugins/e-wallet::e-wallet.topup.try_different_method') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
