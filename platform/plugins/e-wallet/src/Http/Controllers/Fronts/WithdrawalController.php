<?php

namespace Botble\EWallet\Http\Controllers\Fronts;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\PayoutPaymentMethodsEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Enums\WithdrawalStatusEnum;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Http\Requests\WithdrawalRequest;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\Withdrawal;
use Botble\EWallet\Services\WalletService;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends BaseFrontController
{
    public function __construct(
        protected WalletHelper $walletHelper,
        protected WalletService $walletService
    ) {
        parent::__construct();
    }

    public function index()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        abort_unless($customer, 404);

        SeoHelper::setTitle(trans('plugins/e-wallet::withdrawal.title'));

        $wallet = Wallet::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'balance' => 0,
                'currency_code' => $this->walletHelper->getDefaultCurrency(),
            ]
        );

        $withdrawals = Withdrawal::query()
            ->where('customer_id', $customer->id)
            ->latest('created_at')
            ->paginate(20);

        $payoutMethods = PayoutPaymentMethodsEnum::payoutMethodsEnabled();

        $minimumAmount = (int) get_wallet_setting('min_withdrawal', 10);
        $maximumAmount = (int) get_wallet_setting('max_withdrawal', 100000000);

        Theme::breadcrumb()
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.home'), route('public.index'))
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.my_account'), route('customer.overview'))
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.my_wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::withdrawal.title'));

        return Theme::scope(
            'e-wallet.withdrawals',
            compact('wallet', 'withdrawals', 'customer', 'payoutMethods', 'minimumAmount', 'maximumAmount'),
            'plugins/e-wallet::themes.withdrawals'
        )->render();
    }

    public function store(WithdrawalRequest $request, BaseHttpResponse $response)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::withdrawal.customer_required'));
        }

        $wallet = Wallet::query()->where('customer_id', $customer->id)->first();

        if (! $wallet) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::e-wallet.errors.wallet_not_found', ['id' => $customer->id]));
        }

        $amount = (int) ($request->input('amount') * 100);

        $minimumAmount = (int) get_wallet_setting('min_withdrawal', 10) * 100;
        if ($amount < $minimumAmount) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::withdrawal.minimum_amount', [
                    'amount' => format_price($minimumAmount / 100),
                ]));
        }

        $maximumAmount = (int) get_wallet_setting('max_withdrawal', 100000000) * 100;
        if ($amount > $maximumAmount) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::withdrawal.maximum_amount', [
                    'amount' => format_price($maximumAmount / 100),
                ]));
        }

        if ($wallet->balance < $amount) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/e-wallet::withdrawal.insufficient_balance'));
        }

        try {
            DB::beginTransaction();

            $paymentChannel = $request->input('payment_channel');
            $bankInfo = [];

            if ($paymentChannel === PayoutPaymentMethodsEnum::BANK_TRANSFER) {
                $bankInfo = ['bank_info' => $request->input('bank_info')];
            } elseif ($paymentChannel === PayoutPaymentMethodsEnum::PAYPAL) {
                $bankInfo = ['paypal_id' => $request->input('paypal_id')];
            }

            $withdrawal = Withdrawal::query()->create([
                'wallet_id' => $wallet->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'currency_code' => $wallet->currency_code,
                'status' => WithdrawalStatusEnum::PENDING,
                'payment_channel' => $paymentChannel,
                'payment_details' => $request->input('payment_details'),
                'bank_info' => $bankInfo,
            ]);

            $this->walletService->debit(
                $customer->id,
                $amount,
                TransactionTypeEnum::WITHDRAWAL,
                Withdrawal::class,
                $withdrawal->getKey(),
                trans('plugins/e-wallet::withdrawal.transaction_description', ['id' => $withdrawal->getKey()])
            );

            DB::commit();

            return $response
                ->setNextUrl(route('customer.e-wallet.withdrawals.index'))
                ->setMessage(trans('plugins/e-wallet::withdrawal.request_submitted'));
        } catch (\Throwable $th) {
            DB::rollBack();

            return $response
                ->setError()
                ->setMessage($th->getMessage());
        }
    }
}
