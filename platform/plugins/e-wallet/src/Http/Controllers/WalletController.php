<?php

namespace Botble\EWallet\Http\Controllers;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Forms\WalletForm;
use Botble\EWallet\Http\Requests\WalletRequest;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Services\WalletService;
use Botble\EWallet\Tables\WalletsTable;
use Botble\EWallet\Tables\WalletTransactionsTable;

class WalletController extends BaseWalletController
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

    public function index(WalletsTable $table)
    {
        $this->pageTitle(trans('plugins/e-wallet::e-wallet.wallet.list'));

        return $table->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/e-wallet::e-wallet.wallet.create'));

        return WalletForm::create()->renderForm();
    }

    public function store(WalletRequest $request, BaseHttpResponse $response)
    {
        $wallet = $this->walletService->getOrCreateWallet($request->input('customer_id'));

        $initialBalance = (float) $request->input('initial_balance', 0);

        if ($initialBalance > 0) {
            $amountCents = (int) ($initialBalance * 100);
            $reason = $request->input('reason') ?: trans('plugins/e-wallet::e-wallet.wallet.initial_balance_credit');

            $this->walletService->credit(
                $wallet->customer_id,
                $amountCents,
                TransactionTypeEnum::ADMIN_ADJUSTMENT,
                null,
                null,
                $reason,
                null,
                ['created_by' => auth()->id()]
            );
        }

        return $response
            ->setNextUrl(route('e-wallet.wallets.show', $wallet->id))
            ->setMessage(trans('plugins/e-wallet::e-wallet.wallet.created_successfully'));
    }

    public function show(int $id, WalletTransactionsTable $transactionsTable)
    {
        $wallet = Wallet::query()
            ->with('customer:id,name,email')
            ->findOrFail($id);

        $transactionsTable->forWallet($id);

        if (request()->has('draw')) {
            return $transactionsTable->render('core/table::base-table');
        }

        $this->pageTitle(trans('plugins/e-wallet::e-wallet.wallet.view_for', ['name' => $wallet->customer?->name ?? 'N/A']));

        return view('plugins/e-wallet::wallets.show', compact('wallet', 'transactionsTable'));
    }
}
