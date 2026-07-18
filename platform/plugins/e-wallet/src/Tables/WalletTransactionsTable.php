<?php

namespace Botble\EWallet\Tables;

use Botble\Base\Facades\Html;
use Botble\EWallet\Models\WalletTransaction;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\ViewAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\EnumColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionsTable extends TableAbstract
{
    protected ?int $walletId = null;

    public function forWallet(int $walletId): self
    {
        $this->walletId = $walletId;

        return $this;
    }

    public function setup(): void
    {
        $this
            ->model(WalletTransaction::class)
            ->addColumns($this->getTableColumns())
            ->addActions([
                ViewAction::make()
                    ->url(fn (ViewAction $action) => route('e-wallet.transactions.show', $action->getItem()->id))
                    ->permission('e-wallet.transactions.index'),
            ])
            ->addBulkAction(DeleteBulkAction::make()->permission('e-wallet.transactions.destroy'))
            ->queryUsing(function (Builder $query) {
                $q = $query
                    ->with(['customer:id,name,email'])
                    ->select([
                        'id',
                        'wallet_id',
                        'customer_id',
                        'currency_code',
                        'type',
                        'status',
                        'amount',
                        'balance_before',
                        'balance_after',
                        'reference_type',
                        'reference_id',
                        'description',
                        'created_at',
                    ])
                    ->latest('created_at');

                if ($this->walletId) {
                    $q->where('wallet_id', $this->walletId);
                }

                return $q;
            });
    }

    protected function getTableColumns(): array
    {
        $columns = [
            IdColumn::make(),
        ];

        if (! $this->walletId) {
            $columns[] = FormattedColumn::make('customer.name')
                ->title(trans('plugins/ecommerce::customer.name'))
                ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->customer?->name ?? '—');
        }

        return array_merge($columns, [
            EnumColumn::make('type')
                ->title(trans('plugins/e-wallet::e-wallet.transaction.type')),
            EnumColumn::make('status')
                ->title(trans('plugins/e-wallet::e-wallet.transaction.status')),
            FormattedColumn::make('amount')
                ->title(trans('plugins/e-wallet::e-wallet.transaction.amount'))
                ->renderUsing(function (FormattedColumn $column) {
                    $transaction = $column->getItem();

                    return Html::tag('span', $transaction->formatted_amount, [
                        'class' => ($transaction->isCredit() ? 'text-success' : 'text-danger') . ' fw-bold',
                    ]);
                })
                ->alignEnd(),
            FormattedColumn::make('balance_after')
                ->title(trans('plugins/e-wallet::e-wallet.transaction.balance_after'))
                ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->formatted_balance_after)
                ->alignEnd(),
            Column::make('description')
                ->title(trans('plugins/e-wallet::e-wallet.transaction.description'))
                ->limit(50),
            FormattedColumn::make('created_at')
                ->title(trans('core/base::tables.created_at'))
                ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->created_at?->format('Y-m-d H:i')),
        ]);
    }

    public function getDefaultButtons(): array
    {
        return ['reload'];
    }
}
