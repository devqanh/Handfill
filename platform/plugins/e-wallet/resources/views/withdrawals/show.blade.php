@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row">
        <div class="col-md-12">
            <x-core::card>
                <x-core::card.header>
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <x-core::card.title>
                            <x-core::icon name="ti ti-cash-banknote" class="me-1" />
                            {{ trans('plugins/e-wallet::withdrawal.view', ['id' => $withdrawal->id]) }}
                        </x-core::card.title>
                        @php
                            $statusValue = $withdrawal->status->getValue();
                            $statusColor = match($statusValue) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'secondary',
                                default => 'primary'
                            };
                            $statusIcon = match($statusValue) {
                                'pending' => 'ti ti-clock',
                                'processing' => 'ti ti-loader',
                                'completed' => 'ti ti-check',
                                'rejected' => 'ti ti-x',
                                'cancelled' => 'ti ti-ban',
                                default => 'ti ti-circle'
                            };
                        @endphp
                        <x-core::badge :color="$statusColor" :icon="$statusIcon">
                            {{ $withdrawal->status->label() }}
                        </x-core::badge>
                    </div>
                </x-core::card.header>

                <x-core::card.body>
                    <x-core::datagrid>
                        <x-core::datagrid.item>
                            <x-slot:title>{{ trans('core/base::tables.id') }}</x-slot:title>
                            {{ $withdrawal->id }}
                        </x-core::datagrid.item>

                        <x-core::datagrid.item>
                            <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.customer') }}</x-slot:title>
                            @if ($withdrawal->customer)
                                <a href="{{ route('customers.edit', $withdrawal->customer_id) }}" class="text-decoration-none">
                                    <x-core::icon name="ti ti-user" class="me-1" />
                                    {{ $withdrawal->customer->name }}
                                </a>
                            @else
                                &mdash;
                            @endif
                        </x-core::datagrid.item>

                        <x-core::datagrid.item>
                            <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.amount') }}</x-slot:title>
                            <span class="text-danger fw-bold">{{ $withdrawal->formatted_amount }}</span>
                        </x-core::datagrid.item>

                        <x-core::datagrid.item>
                            <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.payment_method') }}</x-slot:title>
                            @if ($withdrawal->payment_channel)
                                <x-core::icon name="ti ti-credit-card" class="me-1" />
                                {{ $withdrawal->payment_channel->label() }}
                            @else
                                &mdash;
                            @endif
                        </x-core::datagrid.item>

                        @if ($withdrawal->bank_info)
                            <x-core::datagrid.item>
                                <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.bank_information') }}</x-slot:title>
                                <div class="text-wrap">
                                    @foreach ($withdrawal->bank_info as $key => $value)
                                        <div><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</div>
                                    @endforeach
                                </div>
                            </x-core::datagrid.item>
                        @endif

                        <x-core::datagrid.item>
                            <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.payment_details') }}</x-slot:title>
                            @if($withdrawal->payment_details)
                                <div class="text-wrap">{{ $withdrawal->payment_details }}</div>
                            @else
                                &mdash;
                            @endif
                        </x-core::datagrid.item>

                        <x-core::datagrid.item>
                            <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.notes') }}</x-slot:title>
                            @if($withdrawal->notes)
                                <div class="text-wrap">{{ $withdrawal->notes }}</div>
                            @else
                                &mdash;
                            @endif
                        </x-core::datagrid.item>

                        <x-core::datagrid.item>
                            <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.status') }}</x-slot:title>
                            <x-core::badge :color="$statusColor" :icon="$statusIcon">
                                {{ $withdrawal->status->label() }}
                            </x-core::badge>
                        </x-core::datagrid.item>

                        <x-core::datagrid.item>
                            <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.created_at') }}</x-slot:title>
                            <x-core::icon name="ti ti-calendar" class="me-1" />
                            {{ BaseHelper::formatDateTime($withdrawal->created_at) }}
                        </x-core::datagrid.item>

                        @if ($withdrawal->processed_at)
                            <x-core::datagrid.item>
                                <x-slot:title>{{ trans('plugins/e-wallet::withdrawal.processed_at') }}</x-slot:title>
                                <x-core::icon name="ti ti-calendar-check" class="me-1" />
                                {{ BaseHelper::formatDateTime($withdrawal->processed_at) }}
                            </x-core::datagrid.item>
                        @endif
                    </x-core::datagrid>
                </x-core::card.body>

                @if ($withdrawal->canEditStatus())
                    <div class="card-body border-top pt-3">
                        <div class="d-flex gap-2">
                            <x-core::button
                                type="button"
                                color="success"
                                icon="ti ti-check"
                                class="btn-trigger-approve-withdrawal"
                                :data-target="route('e-wallet.withdrawals.approve', $withdrawal->id)"
                            >
                                {{ trans('plugins/e-wallet::withdrawal.approve') }}
                            </x-core::button>

                            <x-core::button
                                type="button"
                                color="danger"
                                icon="ti ti-x"
                                class="btn-trigger-reject-withdrawal"
                                :data-target="route('e-wallet.withdrawals.reject', $withdrawal->id)"
                            >
                                {{ trans('plugins/e-wallet::withdrawal.reject') }}
                            </x-core::button>
                        </div>
                    </div>
                @endif

                <x-core::card.footer>
                    <a href="{{ route('e-wallet.withdrawals.index') }}" class="btn btn-secondary">
                        <x-core::icon name="ti ti-arrow-left" class="me-1" />
                        {{ trans('plugins/e-wallet::e-wallet.forms.back') }}
                    </a>
                </x-core::card.footer>
            </x-core::card>
        </div>
    </div>
@stop

@push('footer')
    <x-core::modal.action
        id="approve-withdrawal-modal"
        type="success"
        :title="trans('plugins/e-wallet::withdrawal.approve_withdrawal')"
        :description="trans('plugins/e-wallet::withdrawal.approve_withdrawal_confirmation', ['id' => $withdrawal->id])"
        :has-form="false"
        :submit-button-label="trans('plugins/e-wallet::withdrawal.approve')"
        :submit-button-attrs="['id' => 'confirm-approve-withdrawal-button', 'class' => 'btn-success']"
        :close-button-label="trans('core/base::forms.cancel')"
    />

    <x-core::modal.action
        id="reject-withdrawal-modal"
        type="danger"
        :title="trans('plugins/e-wallet::withdrawal.reject_withdrawal')"
        :description="trans('plugins/e-wallet::withdrawal.reject_withdrawal_confirmation', ['id' => $withdrawal->id])"
        :has-form="false"
        :submit-button-label="trans('plugins/e-wallet::withdrawal.reject')"
        :submit-button-attrs="['id' => 'confirm-reject-withdrawal-button', 'class' => 'btn-danger']"
        :close-button-label="trans('core/base::forms.cancel')"
    />
@endpush
