<?php

namespace Botble\EWallet\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\EWallet\Enums\WithdrawalStatusEnum;
use Botble\EWallet\Models\Withdrawal;
use Botble\EWallet\Plugin;
use Botble\EWallet\Services\WalletService;
use Botble\EWallet\Tables\WithdrawalTable;
use Illuminate\Http\Request;

class WithdrawalController extends BaseWalletController
{
    public function __construct(protected WalletService $walletService)
    {
        parent::__construct();
    }

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/e-wallet::withdrawal.name'), route('e-wallet.withdrawals.index'));
    }

    public function index(WithdrawalTable $table)
    {
        $this->pageTitle(trans('plugins/e-wallet::withdrawal.name'));

        return $table->renderTable();
    }

    public function show(int|string $id)
    {
        $withdrawal = Withdrawal::query()->with(['wallet', 'customer'])->findOrFail($id);

        $this->pageTitle(trans('plugins/e-wallet::withdrawal.view', ['id' => $withdrawal->getKey()]));

        $version = Plugin::ASSETS_VERSION;
        Assets::addScriptsDirectly("vendor/core/plugins/e-wallet/js/withdrawal-management.js?v={$version}");

        return view('plugins/e-wallet::withdrawals.show', compact('withdrawal'));
    }

    public function approve(int|string $id, BaseHttpResponse $response)
    {
        $withdrawal = Withdrawal::query()->findOrFail($id);

        if (! $withdrawal->canEditStatus()) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::withdrawal.cannot_approve'));
        }

        $withdrawal->update([
            'status' => WithdrawalStatusEnum::COMPLETED,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return $response
            ->setPreviousUrl(route('e-wallet.withdrawals.index'))
            ->setMessage(trans('plugins/e-wallet::withdrawal.approve_success'));
    }

    public function reject(int|string $id, Request $request, BaseHttpResponse $response)
    {
        $withdrawal = Withdrawal::query()->with('wallet')->findOrFail($id);

        if (! $withdrawal->canEditStatus()) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::withdrawal.cannot_reject'));
        }

        $withdrawal->update([
            'status' => WithdrawalStatusEnum::REJECTED,
            'notes' => $request->input('reason', ''),
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        $wallet = $withdrawal->wallet;
        if ($wallet) {
            $this->walletService->credit(
                $wallet->customer_id,
                $withdrawal->amount,
                'refund',
                Withdrawal::class,
                $withdrawal->getKey(),
                trans('plugins/e-wallet::withdrawal.refund_description', ['id' => $withdrawal->getKey()])
            );
        }

        return $response
            ->setPreviousUrl(route('e-wallet.withdrawals.index'))
            ->setMessage(trans('plugins/e-wallet::withdrawal.reject_success'));
    }
}
