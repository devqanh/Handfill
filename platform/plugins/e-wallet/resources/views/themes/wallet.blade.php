@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/e-wallet::e-wallet.customer.my_wallet'))

@section('content')
    <div class="bb-customer-content-wrapper">
        <div class="bb-customer-card-list wallet-cards">
            <div class="bb-customer-card wallet-balance-section mb-4">
                <div class="bb-customer-card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100">
                        <h4 class="bb-customer-card-title mb-0">
                            <x-core::icon name="ti ti-wallet" class="me-2" />
                            {{ trans('plugins/e-wallet::e-wallet.customer.my_wallet') }}
                        </h4>
                        <div class="d-flex gap-2">
                            @if(get_wallet_setting('enable_withdrawal', true) && $wallet->balance > 0)
                                <a href="{{ route('customer.e-wallet.withdrawals.index') }}" class="btn btn-outline-danger btn-sm">
                                    <x-core::icon name="ti ti-arrow-down-right" class="me-1" />
                                    {{ trans('plugins/e-wallet::e-wallet.customer.withdraw') }}
                                </a>
                            @endif
                            @if(app(\Botble\EWallet\Helpers\WalletHelper::class)->isTopUpEnabled())
                                <a href="{{ route('customer.e-wallet.topup.create') }}" class="btn btn-primary btn-sm">
                                    <x-core::icon name="ti ti-plus" class="me-1" />
                                    {{ trans('plugins/e-wallet::e-wallet.topup.title') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="bb-customer-card-body">
                    <div class="info-item">
                        <span class="label">{{ trans('plugins/e-wallet::e-wallet.customer.current_balance') }}</span>
                        <span class="value {{ $wallet->balance >= 0 ? 'text-success' : 'text-danger' }}">{{ $wallet->formatted_balance }}</span>
                    </div>
                </div>
            </div>

            @if(gift_cards_enabled())
                <div class="bb-customer-card gift-card-section">
                    <div class="bb-customer-card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-1">
                                    <x-core::icon name="ti ti-gift" class="me-2" />
                                    {{ trans('plugins/e-wallet::gift-card.have_gift_card') }}
                                </h5>
                                <p class="text-muted mb-0 small">{{ trans('plugins/e-wallet::gift-card.redeem_description') }}</p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('customer.e-wallet.gift-card.redeem') }}" class="btn btn-success btn-sm">
                                    <x-core::icon name="ti ti-wallet" class="me-1" />
                                    {{ trans('plugins/e-wallet::gift-card.redeem') }}
                                </a>
                                @if(gift_card_purchase_enabled())
                                    <a href="{{ route('customer.e-wallet.gift-card.purchase') }}" class="btn btn-primary btn-sm">
                                        <x-core::icon name="ti ti-gift" class="me-1" />
                                        {{ trans('plugins/e-wallet::gift-card.purchase.buy_gift_card') }}
                                    </a>
                                @endif
                                <a href="{{ route('customer.e-wallet.gift-card.my-cards') }}" class="btn btn-outline-secondary btn-sm">
                                    <x-core::icon name="ti ti-cards" class="me-1" />
                                    {{ trans('plugins/e-wallet::gift-card.my_cards.title') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($pendingTopUps) && $pendingTopUps->count() > 0)
                <div class="pending-topups-section mt-4">
                    @foreach($pendingTopUps as $topup)
                        <a href="{{ route('customer.e-wallet.topup.success', $topup->code) }}" class="pending-topup-card">
                            <div class="pending-topup-icon">
                                <x-core::icon name="ti ti-clock" />
                            </div>
                            <div class="pending-topup-content">
                                <div class="pending-topup-header">
                                    <span class="pending-topup-title">{{ trans('plugins/e-wallet::e-wallet.topup.pending_payment') }}</span>
                                    {!! $topup->status->toHtml() !!}
                                </div>
                                <div class="pending-topup-details">
                                    <span class="pending-topup-code">{{ $topup->code }}</span>
                                    @if($topup->payment_method)
                                        <span class="pending-topup-method">{{ get_payment_setting('name', $topup->payment_method) ?: ucfirst(str_replace('_', ' ', $topup->payment_method)) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="pending-topup-amount">
                                +{{ $topup->formatted_amount }}
                            </div>
                            <div class="pending-topup-arrow">
                                <x-core::icon name="ti ti-chevron-right" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bb-customer-card-list transaction-cards">
            <div class="bb-customer-card">
                <div class="bb-customer-card-header">
                    <h4 class="bb-customer-card-title mb-0">{{ trans('plugins/e-wallet::e-wallet.customer.transaction_history') }}</h4>
                </div>
                <div class="bb-customer-card-body p-0">
                    @if($transactions->count() > 0)
                        <div class="transaction-list">
                            @foreach($transactions as $transaction)
                                <div class="transaction-item">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <div class="bb-customer-card-status">
                                                    {!! $transaction->type->badge() !!}
                                                </div>
                                                <span class="transaction-date">{{ $transaction->created_at->translatedFormat('M d, Y H:i') }}</span>
                                            </div>
                                            @if($transaction->description)
                                                <p class="transaction-description mb-0">
                                                    {{ $transaction->description }}
                                                    @if($transaction->reference && $transaction->reference instanceof \Botble\Ecommerce\Models\Order)
                                                        <a href="{{ route('customer.orders.view', $transaction->reference->id) }}" target="_blank" class="ms-2 text-decoration-none">
                                                            <x-core::icon name="ti ti-external-link" class="me-1" />{{ trans('plugins/e-wallet::e-wallet.customer.view_order') }}
                                                        </a>
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <div class="transaction-amount {{ $transaction->amount >= 0 ? 'credit' : 'debit' }}">
                                                {{ $transaction->formatted_amount }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="transaction-meta mt-3">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="info-item">
                                                    <span class="label">{{ trans('plugins/e-wallet::e-wallet.transaction.balance_after') }}</span>
                                                    <span class="value">{{ $transaction->formatted_balance_after }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item">
                                                    <span class="label">{{ trans('plugins/e-wallet::e-wallet.transaction.status') }}</span>
                                                    <span class="value">{!! $transaction->status->badge() !!}</span>
                                                </div>
                                            </div>
                                            @if($transaction->reference && $transaction->reference instanceof \Botble\EWallet\Models\Withdrawal)
                                                @php $withdrawal = $transaction->reference; @endphp
                                                <div class="col-6">
                                                    <div class="info-item">
                                                        <span class="label">{{ trans('plugins/e-wallet::withdrawal.payment_method') }}</span>
                                                        <span class="value">
                                                            @if($withdrawal->payment_channel)
                                                                {{ $withdrawal->payment_channel->label() }}
                                                            @else
                                                                —
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="info-item">
                                                        <span class="label">{{ trans('plugins/e-wallet::withdrawal.withdrawal_status') }}</span>
                                                        <span class="value">{!! $withdrawal->status->toHtml() !!}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($transactions->hasPages())
                            <div class="d-flex justify-content-center p-3 border-top">
                                {!! $transactions->links() !!}
                            </div>
                        @endif
                    @else
                        <div class="bb-empty">
                            <div class="bb-empty-img">
                                <x-core::icon name="ti ti-receipt-off" />
                            </div>
                            <p class="bb-empty-title">{{ trans('plugins/e-wallet::e-wallet.customer.no_transactions') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
