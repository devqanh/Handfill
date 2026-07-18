@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <div class="ui-layout">
            <div class="flexbox-layout-sections">
                <div class="flexbox-layout-section-primary mt-20">
                    {{-- Balance Adjustment Card --}}
                    <x-core::card>
                        <x-core::card.header>
                            <x-core::card.title>
                                <x-core::icon name="ti ti-adjustments-horizontal" />
                                {{ trans('plugins/e-wallet::e-wallet.adjustment.adjust_for', ['name' => $wallet->customer?->name ?? 'N/A']) }}
                            </x-core::card.title>
                        </x-core::card.header>
                        <x-core::card.body>
                            {{-- Customer & Balance Info --}}
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-2">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-user" />
                                                {{ trans('plugins/ecommerce::customer.name') }}
                                            </x-slot:title>
                                            <span class="fw-semibold">{{ $wallet->customer?->name ?? '—' }}</span>
                                        </x-core::datagrid.item>
                                        <x-core::datagrid.item>
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-mail" />
                                                {{ trans('core/base::forms.email') }}
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
                                <div class="col-12 col-md-6">
                                    <div class="text-center p-3 rounded bg-light">
                                        <div class="text-muted small mb-1">
                                            {{ trans('plugins/e-wallet::e-wallet.wallet.current_balance') }}
                                        </div>
                                        <x-core::badge
                                            :color="$wallet->balance >= 0 ? 'success' : 'danger'"
                                            :label="$wallet->formatted_balance"
                                            class="fs-4 px-3 py-2"
                                        />
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Adjustment Form --}}
                            {!! $form->renderForm() !!}
                        </x-core::card.body>
                    </x-core::card>
                </div>
            </div>
        </div>
    </div>
@endsection
