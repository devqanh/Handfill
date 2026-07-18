<?php

namespace Botble\EWallet\Http\Controllers;

use Botble\Base\Supports\Breadcrumb;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Http\Requests\WalletAdjustmentRequest;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Services\WalletService;

class WalletAdjustmentController extends BaseWalletController
{
    public function __construct(protected WalletService $walletService)
    {
        parent::__construct();
    }

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/e-wallet::e-wallet.wallet.list'), route('e-wallet.wallets.index'));
    }

    public function store(WalletAdjustmentRequest $request)
    {
        $walletId = $request->input('wallet_id');
        $wallet = Wallet::query()->findOrFail($walletId);

        $amountInDollars = (float) $request->input('amount');
        $amountCents = (int) round($amountInDollars * 100);
        $type = $request->input('adjustment_type');
        $reason = $request->input('reason');

        if ($type === 'debit') {
            $amountCents = -abs($amountCents);
        }

        try {
            $this->walletService->adjustBalance(
                customerId: $wallet->customer_id,
                amountCents: $amountCents,
                description: $reason,
                createdBy: auth()->id()
            );
        } catch (InsufficientBalanceException $e) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($e->getMessage());
        }

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/e-wallet::e-wallet.adjustment.success'));
    }
}
