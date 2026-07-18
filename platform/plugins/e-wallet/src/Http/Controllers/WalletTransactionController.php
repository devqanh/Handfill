<?php

namespace Botble\EWallet\Http\Controllers;

use Botble\Base\Supports\Breadcrumb;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Tables\WalletTransactionsTable;

class WalletTransactionController extends BaseWalletController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/e-wallet::e-wallet.transaction.all_transactions'), route('e-wallet.transactions.index'));
    }
    public function index(WalletTransactionsTable $table, ?int $walletId = null)
    {
        if ($walletId) {
            $wallet = Wallet::query()
                ->with('customer:id,name,email')
                ->findOrFail($walletId);
            $this->pageTitle(trans('plugins/e-wallet::e-wallet.transaction.history_for', ['name' => $wallet->customer?->name ?? 'N/A']));
            $table->forWallet($walletId);
        } else {
            $this->pageTitle(trans('plugins/e-wallet::e-wallet.transaction.all_transactions'));
        }

        return $table->renderTable();
    }

    public function show(int $id)
    {
        $transaction = WalletTransaction::query()
            ->with(['customer:id,name,email', 'wallet', 'reference'])
            ->findOrFail($id);

        $this->pageTitle(trans('plugins/e-wallet::e-wallet.transaction.detail', ['id' => $transaction->id]));

        return view('plugins/e-wallet::transactions.show', compact('transaction'));
    }
}
