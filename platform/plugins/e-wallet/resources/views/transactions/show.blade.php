@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <div class="ui-layout">
            <div class="flexbox-layout-sections">
                <div class="flexbox-layout-section-primary mt-20">
                    {{-- Transaction Summary Card --}}
                    <x-core::card class="mb-3">
                        <x-core::card.header>
                            <x-core::card.title>
                                <x-core::icon name="ti ti-receipt" />
                                {{ trans('plugins/e-wallet::e-wallet.transaction.detail', ['id' => $transaction->id]) }}
                            </x-core::card.title>
                        </x-core::card.header>
                        <x-core::card.body>
                            <div class="row g-3">
                                {{-- Left Column: Transaction Info --}}
                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-tag" />
                                                {{ trans('plugins/e-wallet::e-wallet.transaction.type') }}
                                            </x-slot:title>
                                            {!! $transaction->type->badge() !!}
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-circle-check" />
                                                {{ trans('plugins/e-wallet::e-wallet.transaction.status') }}
                                            </x-slot:title>
                                            @php
                                                $statusColor = match($transaction->status->getValue()) {
                                                    'completed' => 'success',
                                                    'pending' => 'warning',
                                                    default => 'danger',
                                                };
                                            @endphp
                                            <x-core::badge :color="$statusColor" :label="$transaction->status->label()" />
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-coins" />
                                                {{ trans('plugins/e-wallet::e-wallet.transaction.amount') }}
                                            </x-slot:title>
                                            <span class="{{ $transaction->isCredit() ? 'text-success' : 'text-danger' }} fw-bold fs-4">{{ $transaction->formatted_amount }}</span>
                                        </x-core::datagrid.item>

                                        @if($transaction->description)
                                            <x-core::datagrid.item class="mb-3">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-file-description" />
                                                    {{ trans('plugins/e-wallet::e-wallet.transaction.description') }}
                                                </x-slot:title>
                                                {{ $transaction->description }}
                                            </x-core::datagrid.item>
                                        @endif
                                    </x-core::datagrid>
                                </div>

                                {{-- Right Column: Balance & Date --}}
                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-arrow-down-right" />
                                                {{ trans('plugins/e-wallet::e-wallet.transaction.balance_before') }}
                                            </x-slot:title>
                                            <span class="text-muted">{{ $transaction->formatted_balance_before }}</span>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-arrow-up-right" />
                                                {{ trans('plugins/e-wallet::e-wallet.transaction.balance_after') }}
                                            </x-slot:title>
                                            <span class="fw-semibold">{{ $transaction->formatted_balance_after }}</span>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-calendar" />
                                                {{ trans('plugins/e-wallet::e-wallet.transaction.date') }}
                                            </x-slot:title>
                                            {{ $transaction->created_at->format('M d, Y H:i:s') }}
                                        </x-core::datagrid.item>

                                        @if($transaction->idempotency_key)
                                            <x-core::datagrid.item class="mb-3">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-key" />
                                                    {{ trans('plugins/e-wallet::e-wallet.transaction.idempotency_key') }}
                                                </x-slot:title>
                                                <code class="small">{{ $transaction->idempotency_key }}</code>
                                            </x-core::datagrid.item>
                                        @endif
                                    </x-core::datagrid>
                                </div>
                            </div>
                        </x-core::card.body>
                    </x-core::card>

                    {{-- Customer & Wallet Info --}}
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
                                    @if($transaction->customer)
                                        <x-core::datagrid>
                                            <x-core::datagrid.item class="mb-2">
                                                <x-slot:title>{{ trans('plugins/ecommerce::customer.name') }}</x-slot:title>
                                                <span class="fw-semibold">{{ $transaction->customer->name }}</span>
                                            </x-core::datagrid.item>
                                            <x-core::datagrid.item>
                                                <x-slot:title>{{ trans('plugins/ecommerce::customer.email') }}</x-slot:title>
                                                <a href="mailto:{{ $transaction->customer->email }}" class="text-decoration-none">
                                                    {{ $transaction->customer->email }}
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
                                    @if($transaction->wallet)
                                        <x-core::datagrid>
                                            <x-core::datagrid.item class="mb-2">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-coins" />
                                                    {{ trans('plugins/e-wallet::e-wallet.wallet.balance') }}
                                                </x-slot:title>
                                                <x-core::badge
                                                    :color="$transaction->wallet->balance >= 0 ? 'success' : 'danger'"
                                                    :label="$transaction->wallet->formatted_balance"
                                                />
                                            </x-core::datagrid.item>
                                            <x-core::datagrid.item class="mb-3">
                                                <x-slot:title>
                                                    <x-core::icon name="ti ti-currency-dollar" />
                                                    {{ trans('plugins/e-wallet::e-wallet.wallet.currency') }}
                                                </x-slot:title>
                                                {{ $transaction->wallet->currency_code }}
                                            </x-core::datagrid.item>
                                        </x-core::datagrid>
                                        <a href="{{ route('e-wallet.wallets.show', $transaction->wallet_id) }}" class="btn btn-sm btn-outline-primary">
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

                    {{-- Reference Card --}}
                    @if($transaction->reference)
                        <x-core::card class="mb-3">
                            <x-core::card.header>
                                <x-core::card.title>
                                    <x-core::icon name="ti ti-link" />
                                    {{ trans('plugins/e-wallet::e-wallet.transaction.reference') }}
                                </x-core::card.title>
                            </x-core::card.header>
                            <x-core::card.body>
                                <x-core::datagrid class="row">
                                    <x-core::datagrid.item class="col-12 col-md-6 mb-2">
                                        <x-slot:title>{{ trans('plugins/e-wallet::e-wallet.transaction.reference_type') }}</x-slot:title>
                                        <span class="badge bg-secondary-lt">{{ class_basename($transaction->reference_type) }}</span>
                                    </x-core::datagrid.item>
                                    <x-core::datagrid.item class="col-12 col-md-6 mb-2">
                                        <x-slot:title>{{ trans('plugins/e-wallet::e-wallet.transaction.reference_id') }}</x-slot:title>
                                        <code>{{ $transaction->reference_id }}</code>
                                    </x-core::datagrid.item>
                                </x-core::datagrid>
                                @if($transaction->reference_type === \Botble\Ecommerce\Models\Order::class && $transaction->reference)
                                    <div class="mt-3">
                                        <a href="{{ route('orders.edit', $transaction->reference_id) }}" class="btn btn-sm btn-outline-info">
                                            <x-core::icon name="ti ti-shopping-cart" />
                                            {{ trans('plugins/e-wallet::e-wallet.transaction.view_order') }}
                                        </a>
                                    </div>
                                @endif
                            </x-core::card.body>
                        </x-core::card>
                    @endif

                    {{-- Metadata Card --}}
                    @if($transaction->metadata)
                        <x-core::card class="mb-3">
                            <x-core::card.header>
                                <x-core::card.title>
                                    <x-core::icon name="ti ti-code" />
                                    {{ trans('plugins/e-wallet::e-wallet.transaction.metadata') }}
                                </x-core::card.title>
                            </x-core::card.header>
                            <x-core::card.body>
                                <pre class="bg-dark text-light p-3 rounded mb-0" style="font-size: 0.8rem;"><code>{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </x-core::card.body>
                        </x-core::card>
                    @endif

                    {{-- Back Button --}}
                    <div class="d-flex gap-2">
                        <a href="{{ route('e-wallet.transactions.index') }}" class="btn btn-secondary">
                            <x-core::icon name="ti ti-arrow-left" />
                            {{ trans('plugins/e-wallet::e-wallet.forms.back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
