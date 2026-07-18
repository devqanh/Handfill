<?php

namespace Botble\LarkWebhook\Tables;

use Botble\LarkWebhook\Models\LarkWebhookEvent;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\ViewAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\BulkChanges\CreatedAtBulkChange;
use Botble\Table\BulkChanges\SelectBulkChange;
use Botble\Table\BulkChanges\TextBulkChange;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\HeaderActions\HeaderAction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class LarkWebhookEventTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(LarkWebhookEvent::class)
            ->setView('plugins/lark-webhook::table')
            ->addActions([
                ViewAction::make()->route('lark-webhook.show'),
                DeleteAction::make()->route('lark-webhook.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('event_type')
                    ->title(trans('plugins/lark-webhook::lark-webhook.event_type'))
                    ->alignStart()
                    ->getValueUsing(fn (FormattedColumn $column) => sprintf('<code>%s</code>', e($column->getItem()->event_type))),
                FormattedColumn::make('status')
                    ->title(trans('plugins/lark-webhook::lark-webhook.status'))
                    ->getValueUsing(fn (FormattedColumn $column) => $this->renderStatus($column->getItem()->status)),
                Column::make('event_id')
                    ->title(trans('plugins/lark-webhook::lark-webhook.event_id'))
                    ->alignStart(),
                Column::make('ip_address')
                    ->title(trans('plugins/lark-webhook::lark-webhook.ip_address')),
                CreatedAtColumn::make()
                    ->title(trans('plugins/lark-webhook::lark-webhook.received_at')),
            ])
            ->addHeaderActions([
                HeaderAction::make('empty')
                    ->label(trans('plugins/lark-webhook::lark-webhook.delete_all'))
                    ->icon('ti ti-trash')
                    ->url('javascript:void(0)')
                    ->permission('lark-webhook.destroy')
                    ->attributes(['class' => 'empty-lark-webhook-events-button']),
            ])
            ->addBulkActions([
                DeleteBulkAction::make()->permission('lark-webhook.destroy'),
            ])
            ->addBulkChanges([
                TextBulkChange::make()
                    ->name('event_type')
                    ->title(trans('plugins/lark-webhook::lark-webhook.event_type')),
                SelectBulkChange::make()
                    ->name('status')
                    ->title(trans('plugins/lark-webhook::lark-webhook.status'))
                    ->choices($this->statusChoices())
                    ->type('customSelect')
                    ->validate(['required', Rule::in(array_keys($this->statusChoices()))]),
                CreatedAtBulkChange::make(),
            ])
            ->queryUsing(fn (Builder $query) => $query->select([
                'id',
                'event_id',
                'event_type',
                'status',
                'ip_address',
                'created_at',
            ]));
    }

    protected function statusChoices(): array
    {
        return [
            'received' => trans('plugins/lark-webhook::lark-webhook.statuses.received'),
            'verified' => trans('plugins/lark-webhook::lark-webhook.statuses.verified'),
            'rejected' => trans('plugins/lark-webhook::lark-webhook.statuses.rejected'),
        ];
    }

    protected function renderStatus(?string $status): string
    {
        $color = match ($status) {
            'rejected' => 'danger',
            'verified' => 'info',
            default => 'success',
        };

        $label = $this->statusChoices()[$status] ?? $status;

        return sprintf('<span class="badge bg-%s">%s</span>', $color, e($label));
    }
}
