@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::gift-card.redeem_gift_card'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="bb-customer-card-list">
            <div class="bb-customer-card">
                <div class="bb-customer-card-header">
                    <h4 class="bb-customer-card-title mb-0">
                        <x-core::icon name="ti ti-gift" class="me-2" />
                        {{ trans('plugins/e-wallet::gift-card.redeem_gift_card') }}
                    </h4>
                </div>
                <div class="bb-customer-card-body">
                    @if($balance)
                        <div class="alert alert-success">
                            <h5 class="alert-heading mb-2">
                                <x-core::icon name="ti ti-circle-check" class="me-1" />
                                {{ trans('plugins/e-wallet::gift-card.card_valid') }}
                            </h5>
                            <p class="mb-1">
                                {{ trans('plugins/e-wallet::gift-card.table.balance') }}: <strong>{{ $balance['formatted_balance'] }}</strong>
                            </p>
                            @if($balance['expires_at'])
                                <p class="mb-0 small">
                                    {{ trans('plugins/e-wallet::gift-card.expires') }}: {{ \Carbon\Carbon::parse($balance['expires_at'])->format('M d, Y') }}
                                </p>
                            @endif
                        </div>
                    @endif

                    <form action="{{ route('customer.e-wallet.gift-card.redeem.submit') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">{{ trans('plugins/e-wallet::gift-card.code') }}</label>
                            <input type="text"
                                   class="form-control form-control-lg text-uppercase"
                                   id="code"
                                   name="code"
                                   value="{{ $code ?? '' }}"
                                   placeholder="GC-XXXX-XXXX-XXXXX"
                                   required
                                   maxlength="50"
                                   autocomplete="off">
                        </div>

                        <div class="alert alert-info">
                            <x-core::icon name="ti ti-info-circle" class="me-1" />
                            {{ trans('plugins/e-wallet::gift-card.redeem_info') }}
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <x-core::icon name="ti ti-wallet" class="me-1" />
                                {{ trans('plugins/e-wallet::gift-card.redeem_to_wallet') }}
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
@endsection
