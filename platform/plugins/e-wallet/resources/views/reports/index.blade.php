@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <div class="ui-layout">
            <div class="flexbox-layout-sections">
                <div class="flexbox-layout-section-primary mt-20">
                    {{-- Stats Widgets Row --}}
                    <div class="row mb-3 g-2">
                        <x-core::stat-widget.item
                            :label="trans('plugins/e-wallet::e-wallet.reports.total_wallets')"
                            :value="(int) $totalWallets"
                            icon="ti ti-wallet"
                            color="primary"
                            :url="route('e-wallet.wallets.index')"
                            column="col-12 col-sm-6 col-lg-3"
                        />

                        <x-core::stat-widget.item
                            :label="trans('plugins/e-wallet::e-wallet.reports.active_wallets')"
                            :value="(int) $activeWallets"
                            icon="ti ti-circle-check"
                            color="success"
                            :url="route('e-wallet.wallets.index')"
                            column="col-12 col-sm-6 col-lg-3"
                        />

                        <x-core::stat-widget.item
                            :label="trans('plugins/e-wallet::e-wallet.reports.total_credits')"
                            :value="format_price($totalCredits / 100, $defaultCurrency)"
                            icon="ti ti-trending-up"
                            color="teal"
                            :url="route('e-wallet.transactions.index')"
                            column="col-12 col-sm-6 col-lg-3"
                        />

                        <x-core::stat-widget.item
                            :label="trans('plugins/e-wallet::e-wallet.reports.total_debits')"
                            :value="format_price($totalDebits / 100, $defaultCurrency)"
                            icon="ti ti-trending-down"
                            color="red"
                            :url="route('e-wallet.transactions.index')"
                            column="col-12 col-sm-6 col-lg-3"
                        />
                    </div>

                    {{-- Withdrawals Stats Row --}}
                    <div class="row mb-3 g-2">
                        <x-core::stat-widget.item
                            :label="trans('plugins/e-wallet::e-wallet.reports.pending_withdrawals')"
                            :value="(int) $pendingWithdrawals"
                            icon="ti ti-clock-hour-4"
                            color="warning"
                            :url="route('e-wallet.withdrawals.index')"
                            column="col-12 col-sm-6 col-lg-4"
                        />

                        <x-core::stat-widget.item
                            :label="trans('plugins/e-wallet::e-wallet.reports.pending_withdrawals_amount')"
                            :value="format_price($pendingWithdrawalsAmount / 100, $defaultCurrency)"
                            icon="ti ti-cash"
                            color="orange"
                            :url="route('e-wallet.withdrawals.index')"
                            column="col-12 col-sm-6 col-lg-4"
                        />

                        <x-core::stat-widget.item
                            :label="trans('plugins/e-wallet::e-wallet.reports.completed_withdrawals')"
                            :value="format_price($completedWithdrawalsAmount / 100, $defaultCurrency)"
                            icon="ti ti-check"
                            color="green"
                            :url="route('e-wallet.withdrawals.index')"
                            column="col-12 col-sm-6 col-lg-4"
                        />
                    </div>

                    {{-- Balance in Circulation Card --}}
                    <div class="row mb-3 g-2">
                        <div class="col-12">
                            <x-core::card>
                                <x-core::card.body class="text-center py-4">
                                    <div class="text-muted mb-2">
                                        <x-core::icon name="ti ti-coins" style="--bb-icon-size: 32px; opacity: 0.6" />
                                    </div>
                                    <h2 class="mb-1 text-primary">{{ format_price($totalBalanceInCirculation / 100, $defaultCurrency) }}</h2>
                                    <p class="text-muted mb-0">{{ trans('plugins/e-wallet::e-wallet.reports.total_balance') }}</p>
                                </x-core::card.body>
                            </x-core::card>
                        </div>
                    </div>

                    {{-- Top Wallets & Recent Transactions --}}
                    <div class="row g-2">
                        <div class="col-12 col-lg-6 mb-3">
                            <x-core::card class="h-100">
                                <x-core::card.header>
                                    <x-core::card.title>
                                        <x-core::icon name="ti ti-trophy" />
                                        {{ trans('plugins/e-wallet::e-wallet.reports.top_wallets') }}
                                    </x-core::card.title>
                                </x-core::card.header>

                                @if($topWallets->count())
                                    <div class="table-responsive">
                                        <x-core::table class="table-striped mb-0">
                                            <x-core::table.header>
                                                <x-core::table.header.cell>{{ trans('plugins/ecommerce::customer.name') }}</x-core::table.header.cell>
                                                <x-core::table.header.cell class="text-end">{{ trans('plugins/e-wallet::e-wallet.wallet.balance') }}</x-core::table.header.cell>
                                            </x-core::table.header>
                                            <x-core::table.body>
                                                @foreach($topWallets as $wallet)
                                                    <x-core::table.body.row>
                                                        <x-core::table.body.cell>
                                                            <a href="{{ route('e-wallet.wallets.show', $wallet->id) }}" class="text-decoration-none d-flex align-items-center gap-2">
                                                                <span class="avatar avatar-sm bg-primary-lt text-primary">
                                                                    {{ strtoupper(substr($wallet->customer?->name ?? '?', 0, 1)) }}
                                                                </span>
                                                                <span>{{ $wallet->customer?->name ?? '—' }}</span>
                                                            </a>
                                                        </x-core::table.body.cell>
                                                        <x-core::table.body.cell class="text-end">
                                                            <x-core::badge color="success" :label="$wallet->formatted_balance" />
                                                        </x-core::table.body.cell>
                                                    </x-core::table.body.row>
                                                @endforeach
                                            </x-core::table.body>
                                        </x-core::table>
                                    </div>

                                    <x-core::card.footer>
                                        <a href="{{ route('e-wallet.wallets.index') }}" class="d-flex align-items-center gap-1">
                                            {{ trans('plugins/e-wallet::e-wallet.reports.view_all_wallets') }}
                                            <x-core::icon name="ti ti-chevron-right" />
                                        </a>
                                    </x-core::card.footer>
                                @else
                                    <x-core::card.body>
                                        <div class="text-center py-4">
                                            <x-core::icon name="ti ti-inbox" style="--bb-icon-size: 48px; opacity: 0.3" class="mb-2" />
                                            <p class="text-muted mb-0">{{ trans('plugins/e-wallet::e-wallet.reports.no_data') }}</p>
                                        </div>
                                    </x-core::card.body>
                                @endif
                            </x-core::card>
                        </div>

                        <div class="col-12 col-lg-6 mb-3">
                            <x-core::card class="h-100">
                                <x-core::card.header>
                                    <x-core::card.title>
                                        <x-core::icon name="ti ti-history" />
                                        {{ trans('plugins/e-wallet::e-wallet.reports.recent_transactions') }}
                                    </x-core::card.title>
                                </x-core::card.header>

                                @if($recentTransactions->count())
                                    <div class="table-responsive">
                                        <x-core::table class="table-striped mb-0">
                                            <x-core::table.header>
                                                <x-core::table.header.cell>{{ trans('plugins/ecommerce::customer.name') }}</x-core::table.header.cell>
                                                <x-core::table.header.cell>{{ trans('plugins/e-wallet::e-wallet.transaction.type') }}</x-core::table.header.cell>
                                                <x-core::table.header.cell class="text-end">{{ trans('plugins/e-wallet::e-wallet.transaction.amount') }}</x-core::table.header.cell>
                                            </x-core::table.header>
                                            <x-core::table.body>
                                                @foreach($recentTransactions as $transaction)
                                                    <x-core::table.body.row>
                                                        <x-core::table.body.cell>
                                                            <span class="text-truncate d-inline-block" style="max-width: 120px;">
                                                                {{ $transaction->customer?->name ?? '—' }}
                                                            </span>
                                                        </x-core::table.body.cell>
                                                        <x-core::table.body.cell>
                                                            {!! $transaction->type->badge() !!}
                                                        </x-core::table.body.cell>
                                                        <x-core::table.body.cell class="text-end">
                                                            <span class="{{ $transaction->isCredit() ? 'text-success' : 'text-danger' }} fw-semibold">{{ $transaction->formatted_amount }}</span>
                                                        </x-core::table.body.cell>
                                                    </x-core::table.body.row>
                                                @endforeach
                                            </x-core::table.body>
                                        </x-core::table>
                                    </div>

                                    <x-core::card.footer>
                                        <a href="{{ route('e-wallet.transactions.index') }}" class="d-flex align-items-center gap-1">
                                            {{ trans('plugins/e-wallet::e-wallet.reports.view_all_transactions') }}
                                            <x-core::icon name="ti ti-chevron-right" />
                                        </a>
                                    </x-core::card.footer>
                                @else
                                    <x-core::card.body>
                                        <div class="text-center py-4">
                                            <x-core::icon name="ti ti-inbox" style="--bb-icon-size: 48px; opacity: 0.3" class="mb-2" />
                                            <p class="text-muted mb-0">{{ trans('plugins/e-wallet::e-wallet.reports.no_data') }}</p>
                                        </div>
                                    </x-core::card.body>
                                @endif
                            </x-core::card>
                        </div>
                    </div>

                    {{-- Pending Withdrawals --}}
                    <div class="row g-2">
                        <div class="col-12 mb-3">
                            <x-core::card>
                                <x-core::card.header>
                                    <x-core::card.title>
                                        <x-core::icon name="ti ti-cash-off" />
                                        {{ trans('plugins/e-wallet::e-wallet.reports.pending_withdrawals_list') }}
                                    </x-core::card.title>
                                </x-core::card.header>

                                @if($recentPendingWithdrawals->count())
                                    <div class="table-responsive">
                                        <x-core::table class="table-striped mb-0">
                                            <x-core::table.header>
                                                <x-core::table.header.cell>{{ trans('plugins/ecommerce::customer.name') }}</x-core::table.header.cell>
                                                <x-core::table.header.cell>{{ trans('plugins/e-wallet::e-wallet.transaction.status') }}</x-core::table.header.cell>
                                                <x-core::table.header.cell>{{ trans('plugins/e-wallet::e-wallet.transaction.date') }}</x-core::table.header.cell>
                                                <x-core::table.header.cell class="text-end">{{ trans('plugins/e-wallet::e-wallet.transaction.amount') }}</x-core::table.header.cell>
                                            </x-core::table.header>
                                            <x-core::table.body>
                                                @foreach($recentPendingWithdrawals as $withdrawal)
                                                    <x-core::table.body.row>
                                                        <x-core::table.body.cell>
                                                            <a href="{{ route('e-wallet.withdrawals.show', $withdrawal->id) }}" class="text-decoration-none d-flex align-items-center gap-2">
                                                                <span class="avatar avatar-sm bg-warning-lt text-warning">
                                                                    {{ strtoupper(substr($withdrawal->customer?->name ?? '?', 0, 1)) }}
                                                                </span>
                                                                <span>{{ $withdrawal->customer?->name ?? '—' }}</span>
                                                            </a>
                                                        </x-core::table.body.cell>
                                                        <x-core::table.body.cell>
                                                            {!! $withdrawal->status->toHtml() !!}
                                                        </x-core::table.body.cell>
                                                        <x-core::table.body.cell>
                                                            {{ $withdrawal->created_at->diffForHumans() }}
                                                        </x-core::table.body.cell>
                                                        <x-core::table.body.cell class="text-end">
                                                            <span class="text-danger fw-semibold">{{ $withdrawal->formatted_amount }}</span>
                                                        </x-core::table.body.cell>
                                                    </x-core::table.body.row>
                                                @endforeach
                                            </x-core::table.body>
                                        </x-core::table>
                                    </div>

                                    <x-core::card.footer>
                                        <a href="{{ route('e-wallet.withdrawals.index') }}" class="d-flex align-items-center gap-1">
                                            {{ trans('plugins/e-wallet::e-wallet.reports.view_all_withdrawals') }}
                                            <x-core::icon name="ti ti-chevron-right" />
                                        </a>
                                    </x-core::card.footer>
                                @else
                                    <x-core::card.body>
                                        <div class="text-center py-4">
                                            <x-core::icon name="ti ti-check" style="--bb-icon-size: 48px; opacity: 0.3" class="mb-2" />
                                            <p class="text-muted mb-0">{{ trans('plugins/e-wallet::e-wallet.reports.no_pending_withdrawals') }}</p>
                                        </div>
                                    </x-core::card.body>
                                @endif
                            </x-core::card>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
