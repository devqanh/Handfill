<?php

namespace Botble\EWallet\Tables;

use Botble\Base\Facades\Html;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Models\GiftCard;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Actions\ViewAction;
use Botble\Table\BulkChanges\SelectBulkChange;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\HeaderActions\HeaderAction;
use Illuminate\Database\Eloquent\Builder;

class GiftCardsTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(GiftCard::class)
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('code')
                    ->title(trans('plugins/e-wallet::gift-card.table.code'))
                    ->searchable()
                    ->renderUsing(fn (FormattedColumn $column) => Html::link(
                        route('e-wallet.gift-cards.show', $column->getItem()->id),
                        $column->getItem()->code
                    )->toHtml()),
                FormattedColumn::make('initial_value')
                    ->title(trans('plugins/e-wallet::gift-card.table.value'))
                    ->alignEnd()
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->formatted_initial_value),
                FormattedColumn::make('balance')
                    ->title(trans('plugins/e-wallet::gift-card.table.balance'))
                    ->alignEnd()
                    ->renderUsing(fn (FormattedColumn $column) => Html::tag(
                        'span',
                        $column->getItem()->formatted_balance,
                        ['class' => $column->getItem()->balance > 0 ? 'text-success fw-bold' : 'text-muted']
                    )),
                FormattedColumn::make('status')
                    ->title(trans('plugins/e-wallet::gift-card.table.status'))
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->status->toHtml()),
                FormattedColumn::make('customer.name')
                    ->title(trans('plugins/e-wallet::gift-card.table.customer'))
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->customer?->name ?? '—'),
                FormattedColumn::make('redeemed_by')
                    ->title(trans('plugins/e-wallet::gift-card.table.redeemed_by'))
                    ->renderUsing(fn (FormattedColumn $column) => $column->getItem()->redeemedBy?->name ?? '—'),
                FormattedColumn::make('expires_at')
                    ->title(trans('plugins/e-wallet::gift-card.table.expires_at'))
                    ->renderUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();
                        if (! $item->expires_at) {
                            return '—';
                        }

                        $class = $item->expires_at->isPast() ? 'text-danger' : '';

                        return Html::tag('span', $item->expires_at->format('Y-m-d'), ['class' => $class]);
                    }),
                CreatedAtColumn::make(),
            ])
            ->addHeaderAction(
                HeaderAction::make('create')
                    ->label(trans('plugins/e-wallet::gift-card.actions.create'))
                    ->url(route('e-wallet.gift-cards.create'))
                    ->icon('ti ti-plus')
                    ->permission('e-wallet.gift-cards.create')
            )
            ->addHeaderAction(
                HeaderAction::make('bulk_create')
                    ->label(trans('plugins/e-wallet::gift-card.actions.bulk_create'))
                    ->url(route('e-wallet.gift-cards.bulk.create'))
                    ->icon('ti ti-stack')
                    ->permission('e-wallet.gift-cards.create')
            )
            ->addHeaderAction(
                HeaderAction::make('export')
                    ->label(trans('plugins/e-wallet::gift-card.actions.export'))
                    ->url(route('e-wallet.gift-cards.export'))
                    ->icon('ti ti-download')
                    ->permission('e-wallet.gift-cards.export')
            )
            ->addActions([
                ViewAction::make()
                    ->route('e-wallet.gift-cards.show')
                    ->permission('e-wallet.gift-cards.index'),
                EditAction::make()
                    ->route('e-wallet.gift-cards.edit')
                    ->permission('e-wallet.gift-cards.edit'),
                DeleteAction::make()
                    ->route('e-wallet.gift-cards.destroy')
                    ->permission('e-wallet.gift-cards.destroy'),
            ])
            ->addBulkChange(
                SelectBulkChange::make()
                    ->name('status')
                    ->title(trans('plugins/e-wallet::gift-card.table.status'))
                    ->choices(GiftCardStatusEnum::labels())
            )
            ->queryUsing(function (Builder $query) {
                return $query
                    ->with(['customer:id,name,email', 'redeemedBy:id,name'])
                    ->select([
                        'id',
                        'code',
                        'initial_value',
                        'balance',
                        'currency_code',
                        'status',
                        'customer_id',
                        'redeemed_by_customer_id',
                        'expires_at',
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
