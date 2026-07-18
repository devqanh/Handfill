<?php

namespace Botble\EWallet\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Supports\Breadcrumb;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Models\WalletTopUp;
use Botble\EWallet\Services\TopUpService;
use Botble\EWallet\Tables\TopUpsTable;
use Exception;

class TopUpManagementController extends BaseWalletController
{
    public function __construct(protected TopUpService $topUpService)
    {
        parent::__construct();
    }

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/e-wallet::e-wallet.topup.list'), route('e-wallet.topups.index'));
    }

    public function index(TopUpsTable $table)
    {
        $this->pageTitle(trans('plugins/e-wallet::e-wallet.topup.list'));

        return $table->renderTable();
    }

    public function show(int $id)
    {
        $topup = WalletTopUp::query()
            ->with(['customer:id,name,email', 'wallet'])
            ->findOrFail($id);

        $this->pageTitle(trans('plugins/e-wallet::e-wallet.topup.detail', ['code' => $topup->code]));

        Assets::addScriptsDirectly('vendor/core/plugins/e-wallet/js/topup-management.js');

        return view('plugins/e-wallet::topups.show', compact('topup'));
    }

    public function complete(int $id)
    {
        $topup = WalletTopUp::query()->findOrFail($id);

        if (! in_array($topup->status->getValue(), [TopUpStatusEnum::PENDING, TopUpStatusEnum::PROCESSING])) {
            return $this->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/e-wallet::e-wallet.topup.cannot_complete'));
        }

        try {
            $this->topUpService->completeTopUp(
                $topup,
                'admin_' . auth()->id(),
                'admin_confirmation'
            );

            return $this->httpResponse()
                ->setMessage(trans('plugins/e-wallet::e-wallet.topup.completed_success'));
        } catch (Exception $e) {
            return $this->httpResponse()
                ->setError()
                ->setMessage($e->getMessage());
        }
    }

    public function cancel(int $id)
    {
        $topup = WalletTopUp::query()->findOrFail($id);

        if (! in_array($topup->status->getValue(), [TopUpStatusEnum::PENDING, TopUpStatusEnum::PROCESSING])) {
            return $this->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/e-wallet::e-wallet.topup.cannot_cancel'));
        }

        $this->topUpService->cancelTopUp($topup);

        return $this->httpResponse()
            ->setMessage(trans('plugins/e-wallet::e-wallet.topup.cancelled_success'));
    }
}
