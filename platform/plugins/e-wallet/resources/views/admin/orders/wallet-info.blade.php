@php
    $totalPaid = $transactions->where('amount', '<', 0)->sum('amount');
    $totalRefunded = $transactions->where('amount', '>', 0)->sum('amount');
    $transactionCurrency = $transactions->first()?->transaction_currency;
@endphp

<x-core::card class="mb-3">
    <x-core::card.header>
        <x-core::card.title class="d-flex align-items-center gap-2">
            <x-core::icon name="ti ti-wallet" class="text-primary" />
            {{ trans('plugins/e-wallet::e-wallet.order.wallet_transactions') }}
            <span class="badge bg-primary-lt ms-auto">{{ $transactions->count() }}</span>
        </x-core::card.title>
    </x-core::card.header>

    <x-core::card.body class="p-0">
        @if($transactions->count() > 1 || $totalRefunded > 0)
            <div class="card-stamp card-stamp-lg">
                <div class="card-stamp-icon bg-primary">
                    <x-core::icon name="ti ti-wallet" />
                </div>
            </div>
            <div class="row g-0 border-bottom">
                <div class="col-6 p-3 border-end">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-danger-lt">
                            <x-core::icon name="ti ti-arrow-up" class="text-danger" />
                        </span>
                        <div>
                            <div class="text-muted small">{{ trans('plugins/e-wallet::e-wallet.order.paid_from_wallet') }}</div>
                            <div class="fw-bold text-danger">{{ format_price(abs($totalPaid) / 100, $transactionCurrency) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 p-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-success-lt">
                            <x-core::icon name="ti ti-arrow-down" class="text-success" />
                        </span>
                        <div>
                            <div class="text-muted small">{{ trans('plugins/e-wallet::e-wallet.order.refunded_to_wallet') }}</div>
                            <div class="fw-bold text-success">{{ format_price($totalRefunded / 100, $transactionCurrency) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="list-group list-group-flush">
            @foreach($transactions as $transaction)
                @php
                    $amount = $transaction->amount;
                    $isDebit = $amount < 0;
                @endphp
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            {!! $transaction->type->badge() !!}
                            {!! $transaction->status->toHtml() !!}
                        </div>
                        <a href="{{ route('e-wallet.transactions.show', $transaction->id) }}"
                           class="btn btn-sm btn-outline-primary"
                           data-bs-toggle="tooltip"
                           title="{{ trans('core/base::tables.view') }}">
                            <x-core::icon name="ti ti-eye" />
                        </a>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="text-muted small">
                            <x-core::icon name="ti ti-clock" class="me-1" />
                            {{ $transaction->created_at->format('M d, Y H:i') }}
                            <span class="d-block d-sm-inline">({{ $transaction->created_at->diffForHumans() }})</span>
                        </div>
                        <div @class([
                            'fs-4 fw-bold',
                            'text-danger' => $isDebit,
                            'text-success' => !$isDebit,
                        ])>
                            {{ $isDebit ? '-' : '+' }}{{ format_price(abs($amount) / 100, $transaction->transaction_currency) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-core::card.body>
</x-core::card>
