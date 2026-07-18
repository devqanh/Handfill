@php
    Assets::addScriptsDirectly('vendor/core/plugins/e-wallet/js/wallet-adjustment.js');
@endphp

@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <div class="ui-layout">
            <div class="flexbox-layout-sections">
                <div class="flexbox-layout-section-primary mt-20">
                    {{-- Wallet Info Card --}}
                    <x-core::card class="mb-3">
                        <x-core::card.header>
                            <x-core::card.title>
                                <x-core::icon name="ti ti-wallet" />
                                {{ trans('plugins/e-wallet::e-wallet.wallet.details') }}
                            </x-core::card.title>
                        </x-core::card.header>
                        <x-core::card.body>
                            <div class="row g-3">
                                {{-- Customer Info Column --}}
                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-user" />
                                                {{ trans('plugins/ecommerce::customer.name') }}
                                            </x-slot:title>
                                            <span class="fw-semibold">{{ $wallet->customer?->name ?? '—' }}</span>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-mail" />
                                                {{ trans('plugins/ecommerce::customer.email') }}
                                            </x-slot:title>
                                            @if($wallet->customer?->email)
                                                <a href="mailto:{{ $wallet->customer->email }}" class="text-decoration-none">
                                                    {{ $wallet->customer->email }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </x-core::datagrid.item>
                                    </x-core::datagrid>
                                </div>

                                {{-- Balance Info Column --}}
                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-coins" />
                                                {{ trans('plugins/e-wallet::e-wallet.wallet.balance') }}
                                            </x-slot:title>
                                            <x-core::badge
                                                :color="$wallet->balance >= 0 ? 'success' : 'danger'"
                                                :label="$wallet->formatted_balance"
                                                class="fs-5"
                                            />
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-currency-dollar" />
                                                {{ trans('plugins/e-wallet::e-wallet.wallet.currency') }}
                                            </x-slot:title>
                                            <span class="badge bg-secondary-lt">{{ $wallet->currency_code }}</span>
                                        </x-core::datagrid.item>
                                    </x-core::datagrid>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="d-flex gap-2 mt-3 pt-3 border-top">
                                <button type="button" class="btn btn-primary" data-bb-toggle="adjust-balance">
                                    <x-core::icon name="ti ti-adjustments-horizontal" />
                                    {{ trans('plugins/e-wallet::e-wallet.adjustment.title') }}
                                </button>
                                <a href="{{ route('e-wallet.wallets.index') }}" class="btn btn-secondary">
                                    <x-core::icon name="ti ti-arrow-left" />
                                    {{ trans('plugins/e-wallet::e-wallet.forms.back') }}
                                </a>
                            </div>
                        </x-core::card.body>
                    </x-core::card>

                    {{-- Transaction History --}}
                    <x-core::card>
                        <x-core::card.header>
                            <x-core::card.title>
                                <x-core::icon name="ti ti-history" />
                                {{ trans('plugins/e-wallet::e-wallet.transaction.history') }}
                            </x-core::card.title>
                        </x-core::card.header>
                        <x-core::card.body class="p-0">
                            {!! $transactionsTable->render('core/table::base-table') !!}
                        </x-core::card.body>
                    </x-core::card>
                </div>
            </div>
        </div>
    </div>

    <x-core::modal
        id="adjust-balance-modal"
        :title="trans('plugins/e-wallet::e-wallet.adjustment.adjust_for', ['name' => $wallet->customer?->name ?? 'N/A'])"
        button-id="confirm-adjust-balance-button"
        :button-label="trans('plugins/e-wallet::e-wallet.adjustment.title')"
    >
        <form data-action="{{ route('e-wallet.wallets.adjust.store') }}">
            <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">

            <div class="mb-3">
                <label class="form-label required" for="adjustment_type">
                    {{ trans('plugins/e-wallet::e-wallet.adjustment.type') }}
                </label>
                <select class="form-select" name="adjustment_type" id="adjustment_type" required>
                    <option value="credit">{{ trans('plugins/e-wallet::e-wallet.adjustment.credit') }}</option>
                    <option value="debit">{{ trans('plugins/e-wallet::e-wallet.adjustment.debit') }}</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label required" for="amount">
                    {{ trans('plugins/e-wallet::e-wallet.adjustment.amount') }}
                </label>
                <input type="number" class="form-control" name="amount" id="amount" min="0.01" step="0.01" required>
                <div class="form-hint">{{ trans('plugins/e-wallet::e-wallet.adjustment.amount_help') }}</div>
            </div>

            <div class="mb-3">
                <label class="form-label required" for="reason">
                    {{ trans('plugins/e-wallet::e-wallet.adjustment.reason') }}
                </label>
                <textarea class="form-control" name="reason" id="reason" rows="3" required></textarea>
                <div class="form-hint">{{ trans('plugins/e-wallet::e-wallet.adjustment.reason_help') }}</div>
            </div>
        </form>
    </x-core::modal>
@endsection
