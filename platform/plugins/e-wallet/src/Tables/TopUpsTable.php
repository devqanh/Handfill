<?php

namespace Botble\EWallet\Tables;

use Botble\Base\Facades\Html;
use Botble\EWallet\Models\WalletTopUp;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\ViewAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\EnumColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;

class TopUpsTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(WalletTopUp::class)
            ->addColumns([
                IdColumn::make(),
                Column::make('code')
                    ->title(trans('plugins/e-wallet::e-wallet.topup.code')),
                FormattedColumn::make('customer.name')
                    ->title(trans('plugins/ecommerce::customer.name'))
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->customer?->name ?? '—'),
                FormattedColumn::make('amount')
                    ->title(trans('plugins/e-wallet::e-wallet.topup.amount'))
                    ->renderUsing(fn (FormattedColumn $column) => Html::tag('span', $column->getItem()->formatted_amount, [
                        'class' => 'text-success fw-bold',
                    ]))
                    ->alignEnd(),
                EnumColumn::make('status')
                    ->title(trans('plugins/e-wallet::e-wallet.transaction.status')),
                FormattedColumn::make('payment_method')
                    ->title(trans('plugins/e-wallet::e-wallet.topup.payment_method'))
                    ->renderUsing(function (FormattedColumn $column) {
                        $method = $column->getItem()->payment_method;

                        if (! $method) {
                            return '—';
                        }

                        return Html::tag('span', ucfirst(str_replace('_', ' ', $method)), [
                            'class' => 'badge bg-secondary-lt',
                        ]);
                    }),
                FormattedColumn::make('created_at')
                    ->title(trans('core/base::tables.created_at'))
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->created_at?->format('Y-m-d H:i')),
            ])
            ->addActions([
                ViewAction::make()
                    ->url(fn (ViewAction $action) => route('e-wallet.topups.show', $action->getItem()->id))
                    ->permission('e-wallet.topups.index'),
            ])
            ->addBulkAction(DeleteBulkAction::make()->permission('e-wallet.topups.destroy'))
            ->queryUsing(function (Builder $query) {
                return $query
                    ->with(['customer:id,name,email'])
                    ->select([
                        'id',
                        'customer_id',
                        'code',
                        'amount',
                        'currency_code',
                        'converted_amount',
                        'wallet_currency_code',
                        'status',
                        'payment_method',
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
