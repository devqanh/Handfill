<?php

namespace Botble\EWallet\Tables;

use Botble\Base\Facades\Html;
use Botble\EWallet\Models\Wallet;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\ViewAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\HeaderActions\HeaderAction;
use Illuminate\Database\Eloquent\Builder;

class WalletsTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Wallet::class)
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('customer.name')
                    ->title(trans('plugins/ecommerce::customer.name'))
                    ->renderUsing(fn (FormattedColumn $column) => Html::link(
                        route('e-wallet.wallets.show', $column->getItem()->id),
                        $column->getItem()->customer?->name ?? '—'
                    )),
                FormattedColumn::make('customer.email')
                    ->title(trans('core/base::forms.email'))
                    ->searchable()
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->customer?->email ?? '—'),
                FormattedColumn::make('balance')
                    ->title(trans('plugins/e-wallet::e-wallet.wallet.balance'))
                    ->renderUsing(fn (FormattedColumn $column) => Html::tag(
                        'span',
                        $column->getItem()->formatted_balance,
                        ['class' => $column->getItem()->balance >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold']
                    ))
                    ->alignEnd(),
                Column::make('currency_code')
                    ->title(trans('plugins/e-wallet::e-wallet.wallet.currency'))
                    ->width(80),
                FormattedColumn::make('updated_at')
                    ->title(trans('core/base::tables.updated_at'))
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->updated_at?->diffForHumans()),
            ])
            ->addHeaderAction(
                HeaderAction::make('create')
                    ->label(trans('plugins/e-wallet::e-wallet.wallet.create'))
                    ->url(route('e-wallet.wallets.create'))
                    ->icon('ti ti-plus')
                    ->permission('e-wallet.wallets.create')
            )
            ->addActions([
                ViewAction::make()
                    ->route('e-wallet.wallets.show')
                    ->permission('e-wallet.wallets.index'),
            ])
            ->addBulkAction(DeleteBulkAction::make()->permission('e-wallet.wallets.destroy'))
            ->queryUsing(function (Builder $query) {
                return $query
                    ->with(['customer:id,name,email'])
                    ->select(['id', 'customer_id', 'balance', 'currency_code', 'updated_at'])
                    ->latest('updated_at');
            });
    }

    public function getDefaultButtons(): array
    {
        return ['reload'];
    }
}
