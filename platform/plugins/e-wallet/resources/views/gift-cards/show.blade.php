@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <div class="ui-layout">
            <div class="flexbox-layout-sections">
                <div class="flexbox-layout-section-primary mt-20">
                    <x-core::card class="mb-3">
                        <x-core::card.header>
                            <x-core::card.title>
                                <x-core::icon name="ti ti-gift" />
                                {{ trans('plugins/e-wallet::gift-card.detail', ['code' => $giftCard->masked_code]) }}
                            </x-core::card.title>
                        </x-core::card.header>
                        <x-core::card.body>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-barcode" />
                                                {{ trans('plugins/e-wallet::gift-card.table.code') }}
                                            </x-slot:title>
                                            <code class="fs-5 text-primary">{{ $giftCard->code }}</code>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-coin" />
                                                {{ trans('plugins/e-wallet::gift-card.table.value') }}
                                            </x-slot:title>
                                            <span class="fw-semibold">{{ $giftCard->formatted_initial_value }}</span>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-coins" />
                                                {{ trans('plugins/e-wallet::gift-card.table.balance') }}
                                            </x-slot:title>
                                            <x-core::badge
                                                :color="$giftCard->balance > 0 ? 'success' : 'secondary'"
                                                :label="$giftCard->formatted_balance"
                                            />
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-flag" />
                                                {{ trans('plugins/e-wallet::gift-card.table.status') }}
                                            </x-slot:title>
                                            {!! $giftCard->status->toHtml() !!}
                                        </x-core::datagrid.item>
                                    </x-core::datagrid>
                                </div>

                                <div class="col-12 col-md-6">
                                    <x-core::datagrid>
                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-user" />
                                                {{ trans('plugins/e-wallet::gift-card.table.customer') }}
                                            </x-slot:title>
                                            <span>{{ $giftCard->customer?->name ?? '—' }}</span>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-user-check" />
                                                {{ trans('plugins/e-wallet::gift-card.table.redeemed_by') }}
                                            </x-slot:title>
                                            <span>{{ $giftCard->redeemedBy?->name ?? '—' }}</span>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-user-star" />
                                                {{ trans('plugins/e-wallet::gift-card.table.issued_by') }}
                                            </x-slot:title>
                                            <span>{{ $giftCard->issuedByUser?->name ?? '—' }}</span>
                                        </x-core::datagrid.item>

                                        <x-core::datagrid.item class="mb-3">
                                            <x-slot:title>
                                                <x-core::icon name="ti ti-calendar" />
                                                {{ trans('plugins/e-wallet::gift-card.table.expires_at') }}
                                            </x-slot:title>
                                            @if($giftCard->expires_at)
                                                <span class="{{ $giftCard->expires_at->isPast() ? 'text-danger' : '' }}">
                                                    {{ $giftCard->expires_at->format('Y-m-d H:i') }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </x-core::datagrid.item>
                                    </x-core::datagrid>
                                </div>
                            </div>

                            @if($giftCard->note)
                                <div class="mt-3 pt-3 border-top">
                                    <strong>{{ trans('plugins/e-wallet::gift-card.form.note') }}:</strong>
                                    <p class="text-muted mb-0">{{ $giftCard->note }}</p>
                                </div>
                            @endif

                            <div class="mt-3 pt-3 border-top">
                                <x-core::datagrid>
                                    <x-core::datagrid.item class="mb-2">
                                        <x-slot:title>{{ trans('plugins/e-wallet::gift-card.table.created_at') }}</x-slot:title>
                                        <span>{{ $giftCard->created_at?->format('Y-m-d H:i:s') }}</span>
                                    </x-core::datagrid.item>
                                    @if($giftCard->redeemed_at)
                                        <x-core::datagrid.item class="mb-2">
                                            <x-slot:title>{{ trans('plugins/e-wallet::gift-card.table.redeemed_at') }}</x-slot:title>
                                            <span>{{ $giftCard->redeemed_at->format('Y-m-d H:i:s') }}</span>
                                        </x-core::datagrid.item>
                                    @endif
                                </x-core::datagrid>
                            </div>

                            <div class="d-flex gap-2 mt-3 pt-3 border-top">
                                @if($giftCard->status->getValue() === \Botble\EWallet\Enums\GiftCardStatusEnum::ACTIVE)
                                    <form action="{{ route('e-wallet.gift-cards.cancel', $giftCard->id) }}" method="POST"
                                          onsubmit="return confirm('{{ trans('plugins/e-wallet::gift-card.confirm.cancel') }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">
                                            <x-core::icon name="ti ti-x" />
                                            {{ trans('plugins/e-wallet::gift-card.actions.cancel') }}
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('e-wallet.gift-cards.index') }}" class="btn btn-secondary">
                                    <x-core::icon name="ti ti-arrow-left" />
                                    {{ trans('plugins/e-wallet::e-wallet.forms.back') }}
                                </a>
                            </div>
                        </x-core::card.body>
                    </x-core::card>

                    @if($orders->count() > 0)
                        <x-core::card class="mb-3">
                            <x-core::card.header>
                                <x-core::card.title>
                                    <x-core::icon name="ti ti-shopping-cart" />
                                    {{ trans('plugins/e-wallet::gift-card.orders_using_card') }}
                                    <span class="badge bg-primary ms-2 text-white">{{ $orders->count() }}</span>
                                </x-core::card.title>
                            </x-core::card.header>
                            <x-core::card.body class="p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ trans('plugins/ecommerce::order.order_id') }}</th>
                                            <th>{{ trans('plugins/ecommerce::order.customer_label') }}</th>
                                            <th>{{ trans('plugins/e-wallet::gift-card.admin.discount_applied') }}</th>
                                            <th>{{ trans('plugins/ecommerce::order.amount') }}</th>
                                            <th>{{ trans('plugins/ecommerce::order.status') }}</th>
                                            <th>{{ trans('core/base::tables.created_at') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders as $order)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('orders.edit', $order->id) }}" class="fw-bold">
                                                        {{ $order->code }}
                                                    </a>
                                                </td>
                                                <td>{{ $order->user?->name ?? $order->address?->name ?? '—' }}</td>
                                                <td class="text-success fw-bold">
                                                    -{{ format_price($order->getOrderMetadata('gift_card_discount') / 100, $giftCard->gift_card_currency) }}
                                                </td>
                                                <td>{{ format_price($order->amount) }}</td>
                                                <td>{!! $order->status->toHtml() !!}</td>
                                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </x-core::card.body>
                        </x-core::card>
                    @endif

                    @if($giftCard->transactions->count() > 0)
                        <x-core::card>
                            <x-core::card.header>
                                <x-core::card.title>
                                    <x-core::icon name="ti ti-history" />
                                    {{ trans('plugins/e-wallet::e-wallet.transaction.history') }}
                                </x-core::card.title>
                            </x-core::card.header>
                            <x-core::card.body class="p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ trans('plugins/e-wallet::e-wallet.transaction.type') }}</th>
                                            <th>{{ trans('plugins/e-wallet::e-wallet.transaction.amount') }}</th>
                                            <th>{{ trans('plugins/e-wallet::e-wallet.transaction.description') }}</th>
                                            <th>{{ trans('plugins/e-wallet::e-wallet.transaction.date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($giftCard->transactions as $transaction)
                                            <tr>
                                                <td>{!! $transaction->type->toHtml() !!}</td>
                                                <td class="{{ $transaction->amount > 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $transaction->formatted_amount }}
                                                </td>
                                                <td>{{ $transaction->description ?? '—' }}</td>
                                                <td>{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </x-core::card.body>
                        </x-core::card>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
