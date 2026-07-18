<?php

namespace Botble\EWallet\Tables;

use Botble\Base\Facades\Html;
use Botble\EWallet\Models\Withdrawal;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\ViewAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\EnumColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;

class WithdrawalTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Withdrawal::class)
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('customer.name')
                    ->title(trans('plugins/ecommerce::customer.name'))
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->customer?->name ?? '—'),
                FormattedColumn::make('amount')
                    ->title(trans('plugins/e-wallet::withdrawal.amount'))
                    ->renderUsing(fn (FormattedColumn $column) => Html::tag('span', $column->getItem()->formatted_amount, [
                        'class' => 'text-danger fw-bold',
                    ]))
                    ->alignEnd(),
                EnumColumn::make('status')
                    ->title(trans('plugins/e-wallet::withdrawal.status')),
                EnumColumn::make('payment_channel')
                    ->title(trans('plugins/e-wallet::withdrawal.payment_method')),
                FormattedColumn::make('created_at')
                    ->title(trans('core/base::tables.created_at'))
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->created_at?->format('Y-m-d H:i')),
            ])
            ->addActions([
                ViewAction::make()
                    ->url(fn (ViewAction $action) => route('e-wallet.withdrawals.show', $action->getItem()->id))
                    ->permission('e-wallet.withdrawals.index'),
            ])
            ->addBulkAction(DeleteBulkAction::make()->permission('e-wallet.withdrawals.destroy'))
            ->queryUsing(function (Builder $query) {
                return $query
                    ->with(['customer:id,name,email', 'wallet'])
                    ->select([
                        'id',
                        'wallet_id',
                        'customer_id',
                        'amount',
                        'currency_code',
                        'status',
                        'payment_channel',
                        'created_at',
                    ])
                    ->latest('created_at');
            });
    }

    public function getDefaultButtons(): array
    {
        return ['reload'];
    }
}
