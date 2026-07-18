@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <div class="ui-layout">
            <div class="flexbox-layout-sections">
                <div class="flexbox-layout-section-primary mt-20">
                    {{-- Top-up Details Card --}}
                    <x-core::card class="mb-3">
                        <x-core::card.header>
                            <x-core::card.title>
                                <x-core::icon name="ti ti-cash" />
                                {{ trans('plugins/e-wallet::e-wallet.topup.detail', ['code' => $topup->code]) }}
                            </x-core::card.title>
                        </x-core::card.header>
                        <x-core::card.body>
                            <div class="row g-3">
                                {{-- Left Column: Top-up Info --}}
                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-hash" />
                                                {{ trans('plugins/e-wallet::e-wallet.topup.code') }}
                                            </x-slot:title>
                                            <span class="fw-bold">{{ $topup->code }}</span>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-circle-check" />
                                                {{ trans('plugins/e-wallet::e-wallet.transaction.status') }}
                                            </x-slot:title>
                                            {!! $topup->status->toHtml() !!}
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-coins" />
                                                {{ trans('plugins/e-wallet::e-wallet.topup.amount') }}
                                            </x-slot:title>
                                            <span class="text-success fw-bold fs-4">+{{ $topup->formatted_amount }}</span>
                                        </x-core::datagrid.item>

                                        @if($topup->currency_code !== $topup->wallet_currency_code)
                                            <x-core::datagrid.item class="mb-3">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-wallet" />
                                                    {{ trans('plugins/e-wallet::e-wallet.topup.wallet_credit') }}
                                                </x-slot:title>
                                                <x-core::badge color="success" :label="$topup->formatted_converted_amount" />
                                            </x-core::datagrid.item>

                                            <x-core::datagrid.item class="mb-3">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-arrows-exchange" />
                                                    {{ trans('plugins/e-wallet::e-wallet.topup.exchange_rate') }}
                                                </x-slot:title>
                                                <span class="badge bg-secondary-lt">{{ $topup->exchange_rate }}</span>
                                            </x-core::datagrid.item>
                                        @endif
                                    </x-core::datagrid>
                                </div>

                                {{-- Right Column: Payment Info --}}
                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-credit-card" />
                                                {{ trans('plugins/e-wallet::e-wallet.topup.payment_method') }}
                                            </x-slot:title>
                                            {{ $topup->payment_method ?? '—' }}
                                        </x-core::datagrid.item>

                                        @if($topup->payment_id)
                                            <x-core::datagrid.item class="mb-3">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-key" />
                                                    {{ trans('plugins/e-wallet::e-wallet.topup.payment_id') }}
                                                </x-slot:title>
                                                <code class="small">{{ $topup->payment_id }}</code>
                                            </x-core::datagrid.item>
                                        @endif

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-calendar" />
                                                {{ trans('plugins/e-wallet::e-wallet.transaction.date') }}
                                            </x-slot:title>
                                            {{ $topup->created_at->format('M d, Y H:i:s') }}
                                        </x-core::datagrid.item>
                                    </x-core::datagrid>
                                </div>
                            </div>
                        </x-core::card.body>
                    </x-core::card>

                    {{-- Customer & Wallet Cards --}}
                    <div class="row g-3 mb-3">
                        {{-- Customer Card --}}
                        <div class="col-12 col-md-6">
                            <x-core::card class="h-100">
                                <x-core::card.header>
                                    <x-core::card.title>
                                        <x-core::icon name="ti ti-user" />
                                        {{ trans('plugins/ecommerce::customer.name') }}
                                    </x-core::card.title>
                                </x-core::card.header>
                                <x-core::card.body>
                                    @if($topup->customer)
                                        <x-core::datagrid>
                                            <x-core::datagrid.item class="mb-2">
                                                <x-slot:title>{{ trans('plugins/ecommerce::customer.name') }}</x-slot:title>
                                                <span class="fw-semibold">{{ $topup->customer->name }}</span>
                                            </x-core::datagrid.item>
                                            <x-core::datagrid.item>
                                                <x-slot:title>{{ trans('plugins/ecommerce::customer.email') }}</x-slot:title>
                                                <a href="mailto:{{ $topup->customer->email }}" class="text-decoration-none">
                                                    {{ $topup->customer->email }}
                                                </a>
                                            </x-core::datagrid.item>
                                        </x-core::datagrid>
                                    @else
                                        <div class="text-center py-3">
                                            <x-core::icon name="ti ti-user-off" style="--bb-icon-size: 32px; opacity: 0.3" class="mb-2" />
                                            <p class="text-muted mb-0">{{ trans('plugins/e-wallet::e-wallet.reports.no_data') }}</p>
                                        </div>
                                    @endif
                                </x-core::card.body>
                            </x-core::card>
                        </div>

                        {{-- Wallet Card --}}
                        <div class="col-12 col-md-6">
                            <x-core::card class="h-100">
                                <x-core::card.header>
                                    <x-core::card.title>
                                        <x-core::icon name="ti ti-wallet" />
                                        {{ trans('plugins/e-wallet::e-wallet.wallet.details') }}
                                    </x-core::card.title>
                                </x-core::card.header>
                                <x-core::card.body>
                                    @if($topup->wallet)
                                        <x-core::datagrid>
                                            <x-core::datagrid.item class="mb-2">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-coins" />
                                                    {{ trans('plugins/e-wallet::e-wallet.wallet.balance') }}
                                                </x-slot:title>
                                                <x-core::badge
                                                    :color="$topup->wallet->balance >= 0 ? 'success' : 'danger'"
                                                    :label="$topup->wallet->formatted_balance"
                                                />
                                            </x-core::datagrid.item>
                                            <x-core::datagrid.item class="mb-3">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-currency-dollar" />
                                                    {{ trans('plugins/e-wallet::e-wallet.wallet.currency') }}
                                                </x-slot:title>
                                                {{ $topup->wallet->currency_code }}
                                            </x-core::datagrid.item>
                                        </x-core::datagrid>
                                        <a href="{{ route('e-wallet.wallets.show', $topup->wallet_id) }}" class="btn btn-sm btn-outline-primary">
                                            <x-core::icon name="ti ti-eye" />
                                            {{ trans('plugins/e-wallet::e-wallet.transaction.view_wallet') }}
                                        </a>
                                    @else
                                        <div class="text-center py-3">
                                            <x-core::icon name="ti ti-wallet-off" style="--bb-icon-size: 32px; opacity: 0.3" class="mb-2" />
                                            <p class="text-muted mb-0">{{ trans('plugins/e-wallet::e-wallet.reports.no_data') }}</p>
                                        </div>
                                    @endif
                                </x-core::card.body>
                            </x-core::card>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2 flex-wrap">
                        @if(in_array($topup->status->getValue(), ['pending', 'processing']))
                            <x-core::button
                                type="button"
                                color="success"
                                icon="ti ti-check"
                                class="btn-trigger-complete-topup"
                                :data-target="route('e-wallet.topups.complete', $topup->id)"
                            >
                                {{ trans('plugins/e-wallet::e-wallet.topup.actions.complete') }}
                            </x-core::button>
                            <x-core::button
                                type="button"
                                color="danger"
                                icon="ti ti-x"
                                class="btn-trigger-cancel-topup"
                                :data-target="route('e-wallet.topups.cancel', $topup->id)"
                            >
                                {{ trans('plugins/e-wallet::e-wallet.topup.actions.cancel') }}
                            </x-core::button>
                        @endif
                        <a href="{{ route('e-wallet.topups.index') }}" class="btn btn-secondary">
                            <x-core::icon name="ti ti-arrow-left" />
                            {{ trans('plugins/e-wallet::e-wallet.forms.back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@pushif(in_array($topup->status->getValue(), ['pending', 'processing']), 'footer')
    <x-core::modal.action
        id="complete-topup-modal"
        type="success"
        :title="trans('plugins/e-wallet::e-wallet.topup.actions.complete')"
        :description="trans('plugins/e-wallet::e-wallet.topup.confirm_complete')"
        :submit-button-attrs="['id' => 'confirm-complete-topup-button']"
        :submit-button-label="trans('plugins/e-wallet::e-wallet.topup.actions.complete')"
    />

    <x-core::modal.action
        id="cancel-topup-modal"
        type="danger"
        :title="trans('plugins/e-wallet::e-wallet.topup.actions.cancel')"
        :description="trans('plugins/e-wallet::e-wallet.topup.confirm_cancel')"
        :submit-button-attrs="['id' => 'confirm-cancel-topup-button']"
        :submit-button-label="trans('plugins/e-wallet::e-wallet.topup.actions.cancel')"
    />
@endpushif
