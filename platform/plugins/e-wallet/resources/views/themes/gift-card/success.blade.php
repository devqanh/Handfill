@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::gift-card.success_title'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="bb-customer-card-list">
            <div class="bb-customer-card">
                <div class="bb-customer-card-body text-center py-5">
                    <div class="mb-4">
                        <span class="display-1 text-success">
                            <x-core::icon name="ti ti-circle-check" />
                        </span>
                    </div>
                    <h2 class="mb-3">{{ trans('plugins/e-wallet::gift-card.success_title') }}</h2>
                    <p class="text-muted mb-4">
                        {{ trans('plugins/e-wallet::gift-card.success_message') }}
                    </p>

                    @if($transaction)
                        <div class="alert alert-success d-inline-block">
                            <p class="mb-1">{{ trans('plugins/e-wallet::gift-card.credited_amount') }}:</p>
                            <h3 class="mb-0">{{ $transaction->formatted_amount }}</h3>
                        </div>
                    @endif

                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <a href="{{ route('customer.e-wallet.index') }}" class="btn btn-primary">
                            <x-core::icon name="ti ti-wallet" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.view_wallet') }}
                        </a>
                        <a href="{{ route('customer.e-wallet.gift-card.redeem') }}" class="btn btn-outline-secondary">
                            <x-core::icon name="ti ti-gift" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.redeem_another') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
