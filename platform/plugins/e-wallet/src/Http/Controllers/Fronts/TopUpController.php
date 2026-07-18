<?php

namespace Botble\EWallet\Http\Controllers\Fronts;

use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Http\Requests\TopUpRequest;
use Botble\EWallet\Models\WalletTopUp;
use Botble\EWallet\Services\TopUpService;
use Botble\EWallet\Services\WalletService;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Payment\Models\Payment;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use FriendsOfBotble\PayFS\PayFS;
use FriendsOfBotble\SePay\Services\BankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class TopUpController extends BaseFrontController
{
    public function __construct(
        protected TopUpService $topUpService,
        protected WalletService $walletService,
        protected WalletHelper $helper
    ) {
        parent::__construct();
    }

    public function create()
    {
        abort_unless($this->helper->isTopUpEnabled(), 404);

        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        SeoHelper::setTitle(trans('plugins/e-wallet::e-wallet.topup.title'));

        Theme::breadcrumb()
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.home'), route('public.index'))
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.my_account'), route('customer.overview'))
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.my_wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.topup'));

        $wallet = $this->walletService->getOrCreateWallet($customer->id);
        $minAmount = $this->helper->getMinTopUp() / 100;
        $maxAmount = $this->helper->getMaxTopUp() / 100;
        $predefinedAmounts = $this->generatePredefinedAmounts($minAmount, $maxAmount);

        $currencies = get_all_currencies()
            ->where('is_default', 1)
            ->merge(get_all_currencies()->where('is_default', 0))
            ->pluck('title', 'id');
        $defaultCurrency = cms_currency()->getDefaultCurrency();
        $walletCurrency = $wallet->wallet_currency ?? $defaultCurrency;

        return Theme::scope(
            'e-wallet.topup.form',
            compact('wallet', 'minAmount', 'maxAmount', 'predefinedAmounts', 'currencies', 'defaultCurrency', 'walletCurrency'),
            'plugins/e-wallet::themes.topup.form'
        )->render();
    }

    public function store(TopUpRequest $request)
    {
        $customer = Auth::guard('customer')->user();
        $amountCents = (int) ($request->input('amount') * 100);

        // Get selected currency or default (use display currency for wallets)
        $currencyId = $request->input('currency_id');
        $currency = $currencyId
            ? get_all_currencies()->firstWhere('id', $currencyId)
            : cms_currency()->getDefaultCurrency();

        $currencyCode = $currency?->title ?? cms_currency()->getDefaultCurrency()->title;
        $exchangeRate = $currency?->exchange_rate ?? 1.0;

        try {
            $topup = $this->topUpService->createTopUp(
                $customer->id,
                $amountCents,
                $currencyCode,
                $exchangeRate
            );
            session(['wallet_topup_id' => $topup->id]);

            return redirect()->route('customer.e-wallet.topup.checkout', $topup->code);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function checkout(string $code)
    {
        $customer = Auth::guard('customer')->user();
        $topup = WalletTopUp::query()
            ->where('code', $code)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'processing'])
            ->firstOrFail();

        if ($topup->status->getValue() === TopUpStatusEnum::PROCESSING) {
            $topup->update(['status' => TopUpStatusEnum::PENDING]);
        }

        session(['wallet_topup_id' => $topup->id]);

        SeoHelper::setTitle(trans('plugins/e-wallet::e-wallet.topup.checkout'));

        Theme::breadcrumb()
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.home'), route('public.index'))
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.my_wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::e-wallet.topup.checkout'));

        $selectedMethod = $topup->payment_method ?: PaymentMethods::getSelectingMethod();

        $paymentData = [
            'amount' => $topup->amount / 100,
            'currency' => $topup->currency_code,
            'name' => $customer->name,
            'selected' => $selectedMethod,
            'default' => PaymentMethods::getDefaultMethod(),
            'selecting' => $selectedMethod,
        ];

        $additionalMethodsHtml = apply_filters(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, null, $paymentData);
        $defaultMethodsHtml = PaymentMethods::render();

        $paymentMethodsHtml = '<ul class="list-group list_payment_method">' . $additionalMethodsHtml . $defaultMethodsHtml . '</ul>';

        $selectedPaymentMethod = $topup->payment_method;

        return Theme::scope(
            'e-wallet.topup.checkout',
            compact('topup', 'paymentMethodsHtml', 'selectedPaymentMethod'),
            'plugins/e-wallet::themes.topup.checkout'
        )->render();
    }

    public function processPayment(Request $request, string $code)
    {
        $customer = Auth::guard('customer')->user();
        $topup = WalletTopUp::query()
            ->where('code', $code)
            ->where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->firstOrFail();

        session(['wallet_topup_id' => $topup->id]);
        session(['wallet_topup_processing' => true]);

        $paymentMethod = $request->input('payment_method');

        $request->merge([
            'amount' => $topup->amount / 100,
            'currency' => $topup->currency_code,
            'name' => $customer->name,
            'email' => $customer->email,
            'wallet_topup_id' => $topup->id,
            'wallet_topup_code' => $topup->code,
        ]);

        $paymentData = [
            'error' => false,
            'message' => null,
            'amount' => $topup->amount / 100,
            'currency' => strtoupper($topup->currency_code),
            'type' => $paymentMethod,
            'charge_id' => null,
            'order_id' => null,
            'description' => trans('plugins/e-wallet::e-wallet.topup.payment_description', [
                'code' => $topup->code,
            ]),
            'customer_id' => $customer->id,
            'customer_type' => $customer::class,
            'return_url' => route('customer.e-wallet.topup.callback', $topup->code),
            'callback_url' => route('customer.e-wallet.topup.callback', $topup->code),
        ];

        $paymentData = apply_filters(PAYMENT_FILTER_AFTER_POST_CHECKOUT, $paymentData, $request);

        if (! empty($paymentData['checkoutUrl'])) {
            $topup->update([
                'payment_method' => $paymentMethod,
                'status' => 'processing',
            ]);

            return redirect($paymentData['checkoutUrl']);
        }

        if (! empty($paymentData['error']) && $paymentData['error'] === true) {
            $topup->update(['payment_method' => $paymentMethod]);

            return back()->withErrors([
                'payment' => $paymentData['message'] ?? trans('plugins/e-wallet::e-wallet.errors.payment_failed'),
            ]);
        }

        if (! empty($paymentData['charge_id'])) {
            // Check if this is a bank transfer method that requires webhook confirmation
            $pendingPaymentMethods = [
                PaymentMethodEnum::BANK_TRANSFER,
            ];

            // Add PayFS if plugin is active
            if (defined('PAYFS_PAYMENT_METHOD_NAME')) {
                $pendingPaymentMethods[] = PAYFS_PAYMENT_METHOD_NAME;
            }

            // Add SePay if plugin is active
            if (defined('SEPAY_PAYMENT_METHOD_NAME')) {
                $pendingPaymentMethods[] = SEPAY_PAYMENT_METHOD_NAME;
            }

            if (in_array($paymentMethod, $pendingPaymentMethods)) {
                // For bank transfer methods, don't complete - wait for webhook
                $topup->update([
                    'payment_method' => $paymentMethod,
                    'payment_id' => $paymentData['charge_id'],
                    'status' => TopUpStatusEnum::PENDING,
                ]);

                return redirect()->route('customer.e-wallet.topup.success', $topup->code)
                    ->with('pending', true);
            }

            // For instant payment methods, complete immediately
            $this->topUpService->completeTopUp($topup, $paymentData['charge_id'], $paymentMethod);

            return redirect()->route('customer.e-wallet.topup.success', $topup->code);
        }

        $topup->update(['payment_method' => $paymentMethod]);

        return redirect()->route('customer.e-wallet.topup.checkout', $topup->code);
    }

    public function callback(Request $request, string $code)
    {
        $customer = Auth::guard('customer')->user();
        $topup = WalletTopUp::query()
            ->where('code', $code)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        session()->forget('wallet_topup_id');

        if ($topup->isCompleted()) {
            return redirect()->route('customer.e-wallet.topup.success', $topup->code);
        }

        if ($topup->status->getValue() === TopUpStatusEnum::PROCESSING) {
            if ($topup->payment_id || $request->filled('charge_id')) {
                return redirect()->route('customer.e-wallet.topup.success', $topup->code)
                    ->with('pending', true);
            }

            return redirect()->route('customer.e-wallet.topup.checkout', $topup->code);
        }

        return redirect()->route('customer.e-wallet.topup.checkout', $topup->code)
            ->withErrors(['payment' => trans('plugins/e-wallet::e-wallet.errors.payment_failed')]);
    }

    protected function generatePredefinedAmounts(float $minAmount, float $maxAmount): array
    {
        $amounts = [];
        $baseAmounts = [10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000, 100000];

        foreach ($baseAmounts as $amount) {
            if ($amount >= $minAmount && $amount <= $maxAmount && count($amounts) < 6) {
                $amounts[] = $amount;
            }
        }

        if (empty($amounts)) {
            $amounts[] = (int) $minAmount;
            $step = ($maxAmount - $minAmount) / 5;

            for ($i = 1; $i < 5; $i++) {
                $amount = (int) ($minAmount + $step * $i);
                if ($amount <= $maxAmount) {
                    $amounts[] = $amount;
                }
            }

            if ((int) $maxAmount != (int) $minAmount) {
                $amounts[] = (int) $maxAmount;
            }

            $amounts = array_unique($amounts);
        }

        return array_values($amounts);
    }

    public function success(string $code, Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $topup = WalletTopUp::query()
            ->where('code', $code)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['completed', 'processing', 'pending'])
            ->firstOrFail();

        if ($topup->isPending() && ! $topup->payment_method && ! $request->filled('charge_id')) {
            return redirect()->route('customer.e-wallet.topup.checkout', $topup->code);
        }

        if ($topup->isPending() && $request->filled('charge_id')) {
            $chargeId = $request->input('charge_id');
            $paymentMethod = null;

            if (class_exists(Payment::class)) {
                $payment = Payment::query()
                    ->where('charge_id', $chargeId)
                    ->first();

                $paymentMethod = $payment?->payment_channel;
            }

            $this->topUpService->completeTopUp($topup, $chargeId, $paymentMethod);
            $topup->refresh();
        }

        SeoHelper::setTitle(trans('plugins/e-wallet::e-wallet.topup.success'));

        Theme::breadcrumb()
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.home'), route('public.index'))
            ->add(trans('plugins/e-wallet::e-wallet.breadcrumbs.my_wallet'), route('customer.e-wallet.index'))
            ->add(trans('plugins/e-wallet::e-wallet.topup.success'));

        $wallet = $this->walletService->getOrCreateWallet($customer->id);

        // Check if payment is pending (not yet completed)
        $isPending = session('pending', false)
            || $topup->status->getValue() === TopUpStatusEnum::PROCESSING
            || ($topup->status->getValue() === TopUpStatusEnum::PENDING && $topup->payment_method);

        $bankTransferInfo = $this->getTopUpBankInfo($topup);
        $payfsInfo = $this->getTopUpPayFSInfo($topup);
        $sepayInfo = $this->getTopUpSePayInfo($topup);

        return Theme::scope(
            'e-wallet.topup.success',
            compact('topup', 'wallet', 'isPending', 'bankTransferInfo', 'payfsInfo', 'sepayInfo'),
            'plugins/e-wallet::themes.topup.success'
        )->render();
    }

    protected function getTopUpBankInfo(WalletTopUp $topup): ?string
    {
        if (! is_plugin_active('payment')) {
            return null;
        }

        if ($topup->payment_method !== PaymentMethodEnum::BANK_TRANSFER) {
            return null;
        }

        if ($topup->isCompleted()) {
            return null;
        }

        $bankInfo = get_payment_setting('description', PaymentMethodEnum::BANK_TRANSFER);

        if (empty($bankInfo)) {
            return null;
        }

        return view(
            'plugins/e-wallet::themes.topup.partials.bank-transfer-info',
            [
                'bankInfo' => $bankInfo,
                'topupAmount' => $topup->amount,
                'topupCode' => $topup->code,
                'topupCurrency' => $topup->topup_currency,
            ]
        )->render();
    }

    protected function getTopUpPayFSInfo(WalletTopUp $topup): ?array
    {
        if (! is_plugin_active('payment') || ! is_plugin_active('fob-payfs')) {
            return null;
        }

        if (! defined('PAYFS_PAYMENT_METHOD_NAME')) {
            return null;
        }

        if ($topup->payment_method !== PAYFS_PAYMENT_METHOD_NAME) {
            return null;
        }

        if ($topup->isCompleted()) {
            return null;
        }

        // Get charge_id from topup's payment_id field or find from Payment record
        $chargeId = $topup->payment_id;
        $payment = null;

        if (! $chargeId) {
            // Fallback: find the payment record
            $payment = Payment::query()
                ->where('customer_id', $topup->customer_id)
                ->where('payment_channel', PAYFS_PAYMENT_METHOD_NAME)
                ->where('status', PaymentStatusEnum::PENDING)
                ->latest()
                ->first();

            if (! $payment) {
                return null;
            }

            $chargeId = $payment->charge_id;
        } else {
            // Find payment record for status checking
            $payment = Payment::query()->where('charge_id', $chargeId)->first();
        }

        if (! $chargeId) {
            return null;
        }
        $topupAmount = $topup->amount / 100;

        // Convert to VND if needed
        $vndAmount = $topupAmount;
        $originalAmount = $topupAmount;
        $originalCurrency = $topup->currency_code;

        if ($originalCurrency !== 'VND') {
            $vndCurrency = get_all_currencies()->firstWhere('title', 'VND');
            if ($vndCurrency) {
                $vndAmount = round($topupAmount * $vndCurrency->exchange_rate);
            }
        }

        return [
            'payment' => $payment,
            'chargeId' => $chargeId,
            'orderAmount' => $vndAmount,
            'originalAmount' => $originalAmount,
            'originalCurrency' => $originalCurrency,
            'imageUrl' => PayFS::getQRCodeUrl($vndAmount, $chargeId),
            'bank' => PayFS::getBankById(get_payment_setting('bank', PAYFS_PAYMENT_METHOD_NAME)),
            'bankAccountNumber' => get_payment_setting('account_number', PAYFS_PAYMENT_METHOD_NAME),
            'bankAccountHolder' => get_payment_setting('account_holder', PAYFS_PAYMENT_METHOD_NAME),
        ];
    }

    protected function getTopUpSePayInfo(WalletTopUp $topup): ?array
    {
        if (! is_plugin_active('payment') || ! is_plugin_active('fob-sepay')) {
            return null;
        }

        if (! defined('SEPAY_PAYMENT_METHOD_NAME')) {
            return null;
        }

        if ($topup->payment_method !== SEPAY_PAYMENT_METHOD_NAME) {
            return null;
        }

        if ($topup->isCompleted()) {
            return null;
        }

        // Get charge_id from topup's payment_id field or find from Payment record
        $chargeId = $topup->payment_id;
        $payment = null;

        if (! $chargeId) {
            // Fallback: find the payment record
            $payment = Payment::query()
                ->where('customer_id', $topup->customer_id)
                ->where('payment_channel', SEPAY_PAYMENT_METHOD_NAME)
                ->where('status', PaymentStatusEnum::PENDING)
                ->latest()
                ->first();

            if (! $payment) {
                return null;
            }

            $chargeId = $payment->charge_id;
        } else {
            // Find payment record for status checking
            $payment = Payment::query()->where('charge_id', $chargeId)->first();
        }

        if (! $chargeId) {
            return null;
        }

        $topupAmount = $topup->amount / 100;

        // SePay only supports VND
        if ($topup->currency_code !== 'VND') {
            $vndCurrency = get_all_currencies()->firstWhere('title', 'VND');
            if ($vndCurrency) {
                $topupAmount = round($topupAmount * $vndCurrency->exchange_rate);
            }
        }

        $bankService = new BankService();
        $bankInfo = $bankService->getBankInfo();

        // Validate required bank settings are configured
        if (empty($bankInfo['bankAccountNumber']) || empty($bankInfo['bankShortName'])) {
            return null;
        }

        return [
            'payment' => $payment,
            'chargeId' => $chargeId,
            'orderAmount' => $topupAmount,
            'qrCodeUrl' => $bankService->getQrCodeUrl(
                $bankInfo['bankAccountNumber'],
                $bankInfo['bankShortName'],
                $topupAmount,
                $chargeId
            ),
            'bankInfo' => $bankInfo,
        ];
    }
}
