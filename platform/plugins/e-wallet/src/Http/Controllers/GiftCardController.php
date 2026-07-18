<?php

namespace Botble\EWallet\Http\Controllers;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Forms\GiftCardBulkForm;
use Botble\EWallet\Forms\GiftCardForm;
use Botble\EWallet\Http\Requests\GiftCardBulkRequest;
use Botble\EWallet\Http\Requests\GiftCardRequest;
use Botble\EWallet\Models\GiftCard;
use Botble\EWallet\Services\GiftCardService;
use Botble\EWallet\Tables\GiftCardsTable;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GiftCardController extends BaseWalletController
{
    public function __construct(protected GiftCardService $giftCardService)
    {
        parent::__construct();
    }

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/e-wallet::gift-card.list'), route('e-wallet.gift-cards.index'));
    }

    public function index(GiftCardsTable $table)
    {
        $this->pageTitle(trans('plugins/e-wallet::gift-card.list'));

        return $table->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/e-wallet::gift-card.create'));

        return GiftCardForm::create()->renderForm();
    }

    public function store(GiftCardRequest $request, BaseHttpResponse $response)
    {
        $valueCents = (int) ($request->input('value') * 100);
        $expiresAt = $request->input('expires_at')
            ? Carbon::parse($request->input('expires_at'))
            : null;

        $giftCard = $this->giftCardService->generate(
            $valueCents,
            $request->input('custom_code'),
            $request->input('customer_id'),
            auth()->id(),
            $expiresAt,
            $request->input('note')
        );

        return $response
            ->setNextUrl(route('e-wallet.gift-cards.show', $giftCard->id))
            ->setMessage(trans('plugins/e-wallet::gift-card.messages.created'));
    }

    public function show(int|string $id)
    {
        $giftCard = GiftCard::query()
            ->with(['customer', 'redeemedBy', 'issuedByUser', 'transactions'])
            ->findOrFail($id);

        $orders = Order::query()
            ->whereHas('orderMetadata', function ($query) use ($id) {
                $query->where('meta_key', 'gift_card_id')
                    ->where('meta_value', $id);
            })
            ->with('user')
            ->latest()
            ->get();

        $this->pageTitle(trans('plugins/e-wallet::gift-card.detail', ['code' => $giftCard->masked_code]));

        return view('plugins/e-wallet::gift-cards.show', compact('giftCard', 'orders'));
    }

    public function edit(int|string $id)
    {
        $giftCard = GiftCard::query()->findOrFail($id);

        $this->pageTitle(trans('plugins/e-wallet::gift-card.edit'));

        return GiftCardForm::createFromModel($giftCard)->renderForm();
    }

    public function update(int|string $id, GiftCardRequest $request, BaseHttpResponse $response)
    {
        $giftCard = GiftCard::query()->findOrFail($id);

        $giftCard->fill([
            'customer_id' => $request->input('customer_id'),
            'expires_at' => $request->input('expires_at') ? Carbon::parse($request->input('expires_at')) : null,
            'note' => $request->input('note'),
        ]);

        $giftCard->save();

        return $response
            ->setNextUrl(route('e-wallet.gift-cards.show', $giftCard->id))
            ->setMessage(trans('plugins/e-wallet::gift-card.messages.updated'));
    }

    public function destroy(int|string $id, BaseHttpResponse $response)
    {
        $giftCard = GiftCard::query()->findOrFail($id);

        $giftCard->delete();

        return $response
            ->setNextUrl(route('e-wallet.gift-cards.index'))
            ->setMessage(trans('plugins/e-wallet::gift-card.messages.deleted'));
    }

    public function cancel(int|string $id, BaseHttpResponse $response)
    {
        try {
            $this->giftCardService->cancel($id);

            return $response->setMessage(trans('plugins/e-wallet::gift-card.messages.cancelled'));
        } catch (\Exception $e) {
            return $response
                ->setError()
                ->setMessage($e->getMessage());
        }
    }

    public function bulkCreate()
    {
        $this->pageTitle(trans('plugins/e-wallet::gift-card.bulk.title'));

        return GiftCardBulkForm::create()->renderForm();
    }

    public function bulkStore(GiftCardBulkRequest $request, BaseHttpResponse $response)
    {
        $valueCents = (int) ($request->input('value') * 100);
        $count = (int) $request->input('quantity');
        $expiresAt = $request->input('expires_at')
            ? Carbon::parse($request->input('expires_at'))
            : null;

        $this->giftCardService->generateBatch(
            $valueCents,
            $count,
            auth()->id(),
            $expiresAt,
            $request->input('note')
        );

        return $response
            ->setNextUrl(route('e-wallet.gift-cards.index'))
            ->setMessage(trans('plugins/e-wallet::gift-card.bulk.success', ['count' => $count]));
    }

    public function export(): StreamedResponse
    {
        $fileName = 'gift-cards-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Code',
                'Initial Value',
                'Balance',
                'Currency',
                'Status',
                'Customer',
                'Redeemed By',
                'Issued By',
                'Created At',
                'Activated At',
                'Redeemed At',
                'Expires At',
                'Note',
            ]);

            GiftCard::query()
                ->with(['customer', 'redeemedBy', 'issuedByUser'])
                ->orderBy('created_at', 'desc')
                ->chunk(100, function ($giftCards) use ($handle): void {
                    foreach ($giftCards as $giftCard) {
                        fputcsv($handle, [
                            $giftCard->id,
                            $giftCard->code,
                            $giftCard->initial_value / 100,
                            $giftCard->balance / 100,
                            $giftCard->currency_code,
                            $giftCard->status->value,
                            $giftCard->customer?->name ?? '',
                            $giftCard->redeemedBy?->name ?? '',
                            $giftCard->issuedByUser?->name ?? '',
                            $giftCard->created_at?->toDateTimeString(),
                            $giftCard->activated_at?->toDateTimeString(),
                            $giftCard->redeemed_at?->toDateTimeString(),
                            $giftCard->expires_at?->toDateTimeString(),
                            $giftCard->note ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
