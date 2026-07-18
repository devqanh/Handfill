<?php

namespace Botble\EWallet\Providers;

use Botble\Base\Facades\Html;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Forms\EWalletPaymentMethodForm;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Models\GiftCard;
use Botble\EWallet\Models\WalletTopUp;
use Botble\EWallet\Services\GiftCardCheckoutService;
use Botble\EWallet\Services\TopUpService;
use Botble\EWallet\Services\WalletPaymentService;
use Botble\EWallet\Services\WalletService;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Payment\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPaymentMethodEnum();
        $this->registerPaymentMethod();
        $this->registerPaymentMethodSettings();
        $this->registerCheckoutHooks();
        $this->registerPaymentProcessingHook();
        $this->registerOrderDetailHooks();
        $this->registerRefundHooks();
        $this->registerFrontendAssets();
        $this->registerTopUpPaymentData();
        $this->registerTopUpRedirectUrl();
        $this->registerTopUpPaymentStorage();
        $this->registerTopUpPaymentCompletion();
        $this->registerTopUpPaymentMethodFilter();
        $this->registerGiftCardCheckoutHooks();
    }

    protected function registerPaymentMethodEnum(): void
    {
        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class === PaymentMethodEnum::class) {
                $values['WALLET'] = E_WALLET_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 99, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class === PaymentMethodEnum::class && $value === E_WALLET_PAYMENT_METHOD_NAME) {
                return trans('plugins/e-wallet::e-wallet.checkout.pay_with_wallet');
            }

            return $value;
        }, 99, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class === PaymentMethodEnum::class && $value === E_WALLET_PAYMENT_METHOD_NAME) {
                return Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )->toHtml();
            }

            return $value;
        }, 99, 2);
    }

    protected function registerPaymentMethod(): void
    {
        if (! defined('PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS')) {
            return;
        }

        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function ($html, $data) {
            if (get_payment_setting('status', E_WALLET_PAYMENT_METHOD_NAME) != 1) {
                return $html;
            }

            $helper = app(WalletHelper::class);

            if (! $helper->isEnabled()) {
                return $html;
            }

            $currentRoute = request()->route()?->getName();

            if ($currentRoute && str_starts_with($currentRoute, 'customer.e-wallet.topup')) {
                return $html;
            }

            $customer = Auth::guard('customer')->user();
            $walletService = app(WalletService::class);
            $wallet = $customer ? $walletService->getOrCreateWallet($customer->id) : null;
            $balance = $wallet?->balance ?? 0;
            $orderAmount = (int) (($data['amount'] ?? 0) * 100);

            PaymentMethods::method(E_WALLET_PAYMENT_METHOD_NAME, [
                'html' => view('plugins/e-wallet::themes.checkout.payment-method', array_merge($data, [
                    'balance' => $balance,
                    'formattedBalance' => $wallet?->formatted_balance ?? format_price(0),
                    'canPay' => $customer && $balance >= $orderAmount,
                    'orderAmount' => $orderAmount,
                    'isLoggedIn' => (bool) $customer,
                ]))->render(),
                'priority' => 1,
            ]);

            return $html;
        }, 1, 2);
    }

    protected function registerPaymentMethodSettings(): void
    {
        if (! defined('PAYMENT_METHODS_SETTINGS_PAGE')) {
            return;
        }

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $settings) {
            return $settings . EWalletPaymentMethodForm::create()->renderForm();
        }, 50);
    }

    protected function registerCheckoutHooks(): void
    {
    }

    protected function registerPaymentProcessingHook(): void
    {
        if (! defined('PAYMENT_FILTER_AFTER_POST_CHECKOUT')) {
            return;
        }

        add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, function (array $data, Request $request) {
            if ($data['type'] !== E_WALLET_PAYMENT_METHOD_NAME) {
                return $data;
            }

            $helper = app(WalletHelper::class);
            if (! $helper->isEnabled()) {
                $data['error'] = true;
                $data['message'] = trans('plugins/e-wallet::e-wallet.errors.wallet_disabled');

                return $data;
            }

            $customer = Auth::guard('customer')->user();
            if (! $customer) {
                $data['error'] = true;
                $data['message'] = trans('plugins/e-wallet::e-wallet.errors.customer_required');

                return $data;
            }

            $orderIds = $request->input('order_id');
            $orders = Order::query()->whereIn('id', (array) $orderIds)->get();

            if ($orders->isEmpty() || ! $orders->first()->user_id) {
                $data['error'] = true;
                $data['message'] = trans('plugins/e-wallet::e-wallet.errors.customer_required');

                return $data;
            }

            $amountCents = (int) round(($data['amount'] ?? 0) * 100);
            $paymentService = app(WalletPaymentService::class);

            try {
                $transactionIds = [];

                foreach ($orders as $order) {
                    $orderAmount = (int) round($order->amount * 100);
                    $transaction = $paymentService->processOrderPayment($order, $orderAmount);
                    $transactionIds[] = $transaction->id;
                }

                $chargeId = 'wallet_' . implode('_', $transactionIds);
                $data['charge_id'] = $chargeId;
                $data['error'] = false;
                $data['message'] = trans('plugins/e-wallet::e-wallet.checkout.payment_success');

                do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'charge_id' => $chargeId,
                    'order_id' => $orderIds,
                    'customer_id' => $customer->id,
                    'customer_type' => get_class($customer),
                    'payment_channel' => E_WALLET_PAYMENT_METHOD_NAME,
                    'status' => PaymentStatusEnum::COMPLETED,
                ]);

            } catch (InsufficientBalanceException $e) {
                $data['error'] = true;
                $data['message'] = $e->getMessage();
            } catch (\Exception $e) {
                $data['error'] = true;
                $data['message'] = trans('plugins/e-wallet::e-wallet.errors.payment_failed');
                report($e);
            }

            return $data;
        }, 999, 2);
    }

    protected function registerOrderDetailHooks(): void
    {
        add_filter('ecommerce_order_detail_sidebar_bottom', function (?string $html, $order) {
            $helper = app(WalletHelper::class);

            if (! $helper->isEnabled()) {
                return $html;
            }

            $walletService = app(WalletService::class);
            $transactions = $walletService->getTransactionsByOrder($order);

            if ($transactions->isEmpty()) {
                return $html;
            }

            $html .= view('plugins/e-wallet::admin.orders.wallet-info', [
                'order' => $order,
                'transactions' => $transactions,
            ])->render();

            return $html;
        }, 99, 2);

        add_filter('ecommerce_order_detail_sidebar_bottom', function (?string $html, $order) {
            if (! function_exists('gift_cards_enabled') || ! gift_cards_enabled()) {
                return $html;
            }

            $giftCardId = $order->getOrderMetadata('gift_card_id');
            $giftCardCode = $order->getOrderMetadata('gift_card_code');
            $giftCardDiscount = $order->getOrderMetadata('gift_card_discount');

            if (! $giftCardId || ! $giftCardDiscount) {
                return $html;
            }

            $giftCard = GiftCard::query()->find($giftCardId);

            $html .= view('plugins/e-wallet::admin.orders.gift-card-info', [
                'order' => $order,
                'giftCardId' => $giftCardId,
                'giftCardCode' => $giftCardCode,
                'giftCardDiscount' => $giftCardDiscount,
                'giftCardCurrency' => $giftCard?->gift_card_currency,
            ])->render();

            return $html;
        }, 100, 2);
    }

    protected function registerRefundHooks(): void
    {
        add_filter('ecommerce_order_refund_form_after', function (?string $html, $order) {
            $helper = app(WalletHelper::class);

            if (! $helper->isEnabled()) {
                return $html;
            }

            if (! $order->user_id) {
                return $html;
            }

            $html .= view('plugins/e-wallet::admin.refund.wallet-info', [
                'order' => $order,
            ])->render();

            return $html;
        }, 10, 2);

        if (! defined('ACTION_AFTER_POST_ORDER_REFUNDED_ECOMMERCE')) {
            return;
        }

        add_filter(ACTION_AFTER_POST_ORDER_REFUNDED_ECOMMERCE, function (BaseHttpResponse $response, Order $order, Request $request) {
            $helper = app(WalletHelper::class);

            if (! $helper->isEnabled()) {
                return $response;
            }

            if (get_wallet_setting('refund_to_wallet', 'wallet') !== 'wallet') {
                return $response;
            }

            if (! $order->user_id) {
                return $response;
            }

            $refundAmount = (float) $request->input('refund_amount', 0);

            if ($refundAmount <= 0) {
                return $response;
            }

            $amountCents = (int) round($refundAmount * 100);
            $idempotencyKey = 'order_refund_' . $order->id . '_' . now()->timestamp;

            $walletService = app(WalletService::class);

            $walletService->credit(
                customerId: $order->user_id,
                amountCents: $amountCents,
                type: TransactionTypeEnum::REFUND,
                referenceType: Order::class,
                referenceId: $order->id,
                description: trans('plugins/e-wallet::e-wallet.transaction.order_refund', [
                    'code' => $order->code,
                ]),
                idempotencyKey: $idempotencyKey,
                metadata: [
                    'order_code' => $order->code,
                    'order_id' => $order->id,
                    'refund_amount' => $refundAmount,
                    'refund_note' => $request->input('refund_note'),
                ]
            );

            return $response;
        }, 10, 3);
    }

    protected function registerFrontendAssets(): void
    {
        if (! defined('THEME_FRONT_HEADER')) {
            return;
        }

        add_filter(THEME_FRONT_HEADER, function (?string $html) {
            if (! request()->is('customer/e-wallet*') && ! request()->is('*/customer/e-wallet*')) {
                return $html;
            }

            $walletCss = asset('vendor/core/plugins/e-wallet/css/wallet.css');

            $html .= sprintf('<link rel="stylesheet" href="%s">', $walletCss);

            return $html;
        }, 99);
    }

    protected function registerTopUpPaymentData(): void
    {
        if (! defined('PAYMENT_FILTER_PAYMENT_DATA')) {
            return;
        }

        add_filter(PAYMENT_FILTER_PAYMENT_DATA, function (array $data, Request $request) {
            $topupId = $request->input('wallet_topup_id') ?: session('wallet_topup_id');

            if (! $topupId && ! session('wallet_topup_processing')) {
                return $data;
            }

            if (! $topupId) {
                return $data;
            }

            $topup = WalletTopUp::query()->find($topupId);

            if (! $topup) {
                return $data;
            }

            $customer = Auth::guard('customer')->user();

            if (! $customer || $topup->customer_id !== $customer->id) {
                return $data;
            }

            session()->forget('wallet_topup_processing');

            $amount = (float) format_price($topup->amount / 100, null, true);

            return [
                'amount' => $amount,
                'currency' => get_application_currency()->title,
                'description' => trans('plugins/e-wallet::e-wallet.topup.payment_description', [
                    'code' => $topup->code,
                ]),
                'order_id' => [],
                'customer_id' => $customer->id,
                'customer_type' => get_class($customer),
                'return_url' => route('customer.e-wallet.topup.callback', $topup->code),
                'callback_url' => route('customer.e-wallet.topup.callback', $topup->code),
                'checkout_token' => 'topup_' . $topup->code,
                'address' => [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone ?? '',
                    'country' => '',
                    'state' => '',
                    'city' => '',
                    'address' => '',
                    'zip_code' => '',
                ],
                'products' => [
                    [
                        'id' => 'topup_' . $topup->id,
                        'name' => trans('plugins/e-wallet::e-wallet.topup.wallet_credit'),
                        'image' => null,
                        'price' => $amount,
                        'price_per_order' => $amount,
                        'qty' => 1,
                    ],
                ],
                'orders' => collect([]),
                'is_wallet_topup' => true,
            ];
        }, 1, 2);
    }

    protected function registerTopUpRedirectUrl(): void
    {
        if (! defined('PAYMENT_FILTER_REDIRECT_URL')) {
            return;
        }

        add_filter(PAYMENT_FILTER_REDIRECT_URL, function ($checkoutToken, $default) {
            $topup = $this->resolveTopUpFromTokenOrSession($checkoutToken);

            if (! $topup) {
                return $checkoutToken;
            }

            session()->forget('wallet_topup_id');

            return route('customer.e-wallet.topup.success', $topup->code);
        }, 999, 2);

        add_filter(PAYMENT_FILTER_CANCEL_URL, function ($checkoutToken, $default) {
            $topup = $this->resolveTopUpFromTokenOrSession($checkoutToken);

            if (! $topup) {
                return $checkoutToken;
            }

            return route('customer.e-wallet.topup.checkout', $topup->code);
        }, 999, 2);
    }

    protected function resolveTopUpFromTokenOrSession(?string $checkoutToken): ?WalletTopUp
    {
        if ($checkoutToken) {
            $decodedToken = urldecode($checkoutToken);
            $prefix = preg_quote(strtoupper(get_wallet_setting('topup_code_prefix', 'TU')), '/');

            if (preg_match('/topup_(' . $prefix . '-[A-Z0-9]+)/i', $decodedToken, $matches)) {
                $topupCode = strtoupper($matches[1]);

                $topup = WalletTopUp::query()->where('code', $topupCode)->first();

                if ($topup) {
                    return $topup;
                }
            }

            if (preg_match('/\/(' . $prefix . '-[A-Z0-9]+)\/?/i', $decodedToken, $matches)) {
                $topupCode = strtoupper($matches[1]);

                $topup = WalletTopUp::query()->where('code', $topupCode)->first();

                if ($topup) {
                    return $topup;
                }
            }
        }

        $topupId = session('wallet_topup_id');

        if (! $topupId) {
            return null;
        }

        return WalletTopUp::query()->find($topupId);
    }

    protected function registerTopUpPaymentStorage(): void
    {
        if (! defined('PAYMENT_ACTION_PAYMENT_PROCESSED')) {
            return;
        }

        // Create payment record for wallet topups with PENDING status
        // Priority 1 to run before ecommerce's handler (priority 123)
        add_action(PAYMENT_ACTION_PAYMENT_PROCESSED, function (array $data): void {
            $orderIds = $data['order_id'] ?? [];

            // Skip if this is an order payment (has order_id)
            if (! empty($orderIds)) {
                return;
            }

            // Check if this is a wallet topup by looking at session or charge_id pattern
            $topupId = session('wallet_topup_id');
            if (! $topupId) {
                // Try to find topup by charge_id (for webhook calls)
                $chargeId = $data['charge_id'] ?? null;
                if ($chargeId) {
                    $topup = WalletTopUp::query()
                        ->where('payment_id', $chargeId)
                        ->first();
                    $topupId = $topup?->id;
                }
            }

            if (! $topupId) {
                return;
            }

            $topup = WalletTopUp::query()->find($topupId);

            if (! $topup) {
                return;
            }

            // Create payment record for wallet topup
            $existingPayment = Payment::query()
                ->where('charge_id', $data['charge_id'])
                ->where('payment_channel', $data['payment_channel'])
                ->first();

            if ($existingPayment) {
                // Update status if changed
                if ($existingPayment->status->getValue() !== ($data['status']?->getValue() ?? $data['status'])) {
                    $existingPayment->update(['status' => $data['status']]);
                }

                return;
            }

            // Create new payment record
            Payment::query()->create([
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'charge_id' => $data['charge_id'],
                'order_id' => null,
                'customer_id' => $data['customer_id'],
                'customer_type' => $data['customer_type'],
                'payment_channel' => $data['payment_channel'],
                'status' => $data['status'],
            ]);
        }, 1);
    }

    protected function registerTopUpPaymentCompletion(): void
    {
        if (! defined('PAYMENT_ACTION_PAYMENT_PROCESSED')) {
            return;
        }

        add_action(PAYMENT_ACTION_PAYMENT_PROCESSED, function (array $data): void {
            $status = $data['status'] ?? null;
            if ($status !== PaymentStatusEnum::COMPLETED) {
                return;
            }

            $topup = $this->resolveTopUpFromPaymentData($data);

            if (! $topup) {
                return;
            }

            if (! in_array($topup->status->getValue(), [TopUpStatusEnum::PENDING, TopUpStatusEnum::PROCESSING])) {
                return;
            }

            $chargeId = $data['charge_id'] ?? null;
            $paymentChannel = $data['payment_channel'] ?? null;

            $topUpService = app(TopUpService::class);
            $topUpService->completeTopUp($topup, $chargeId, $paymentChannel);
        }, 1);
    }

    protected function resolveTopUpFromPaymentData(array $data): ?WalletTopUp
    {
        // 1. Try to find from session (browser-based flow)
        $topupId = session('wallet_topup_id');
        if ($topupId) {
            $topup = WalletTopUp::query()->find($topupId);
            if ($topup) {
                return $topup;
            }
        }

        // Skip if this is an order payment
        $orderId = $data['order_id'] ?? null;
        if (! empty($orderId)) {
            return null;
        }

        // 2. Try to find by charge_id matching payment_id (most reliable for webhooks)
        $chargeId = $data['charge_id'] ?? null;
        if ($chargeId) {
            $topup = WalletTopUp::query()
                ->where('payment_id', $chargeId)
                ->whereIn('status', ['pending', 'processing'])
                ->first();

            if ($topup) {
                return $topup;
            }
        }

        // 3. Try to find customer_id from data or auth
        $customerId = $data['customer_id'] ?? null;
        if (! $customerId) {
            $customer = Auth::guard('customer')->user();
            $customerId = $customer?->id;
        }

        // 4. Try to find via charge_id → Payment → customer_id (webhook flow)
        if (! $customerId && $chargeId && class_exists(Payment::class)) {
            $payment = Payment::query()->where('charge_id', $chargeId)->first();
            if ($payment && $payment->customer_id) {
                $customerId = $payment->customer_id;
            }
        }

        if (! $customerId) {
            return null;
        }

        // Fallback: find latest pending topup for customer
        return WalletTopUp::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', ['pending', 'processing'])
            ->latest()
            ->first();
    }

    protected function registerTopUpPaymentMethodFilter(): void
    {
        add_filter('payment_methods_excluded', function (array $excludedMethods) {
            // Only apply filter on top-up pages
            $currentRoute = request()->route()?->getName();
            if (! $currentRoute || ! str_starts_with($currentRoute, 'customer.e-wallet.topup')) {
                return $excludedMethods;
            }

            $allowedMethods = get_allowed_topup_payment_methods();

            foreach (PaymentMethodEnum::toArray() as $method) {
                if (! in_array($method, $allowedMethods) && ! in_array($method, $excludedMethods)) {
                    $excludedMethods[] = $method;
                }
            }

            return $excludedMethods;
        }, 99);
    }

    protected function registerGiftCardCheckoutHooks(): void
    {
        if (! function_exists('gift_cards_enabled') || ! gift_cards_enabled()) {
            return;
        }

        if (unified_discount_field_enabled()) {
            $this->registerUnifiedDiscountFormHook();
        } else {
            add_filter('ecommerce_checkout_form_before_payment_form', function (?string $html) {
                if (! gift_cards_enabled()) {
                    return $html;
                }

                return $html . view('plugins/e-wallet::themes.checkout.gift-card-input')->render();
            }, 60);
        }

        add_filter('ecommerce_cart_raw_total', function ($total) {
            if (! gift_cards_enabled()) {
                return $total;
            }

            $discount = (float) session('gift_card_discount', 0);

            if ($discount <= 0) {
                return $total;
            }

            return max(0, $total - $discount);
        }, 998);

        add_filter('ecommerce_checkout_after_subtotal', function (?string $html) {
            if (! gift_cards_enabled()) {
                return $html;
            }

            $discount = (float) session('gift_card_discount', 0);
            $applied = app(GiftCardCheckoutService::class)->getApplied();

            if ($discount <= 0 || ! $applied) {
                return $html;
            }

            $html .= sprintf(
                '<div class="row"><div class="col-6"><p>%s:</p></div><div class="col-6"><p class="price-text gift-card-discount-text text-success">-%s</p></div></div>',
                trans('plugins/e-wallet::gift-card.checkout.discount_label') . ' <code>' . $applied['code'] . '</code>',
                $applied['formatted_discount'] ?? format_price($discount)
            );

            return $html;
        }, 98);

        add_filter('ecommerce_thank_you_total_info', function (?string $html, $order) {
            if (! gift_cards_enabled()) {
                return $html;
            }

            $giftCardId = $order->getOrderMetadata('gift_card_id');
            $giftCardDiscount = $order->getOrderMetadata('gift_card_discount');
            $giftCardCode = $order->getOrderMetadata('gift_card_code');

            if (! $giftCardDiscount || $giftCardDiscount <= 0) {
                return $html;
            }

            $giftCard = $giftCardId ? GiftCard::query()->find($giftCardId) : null;

            $html .= sprintf(
                '<div class="row"><div class="col-6"><p>%s <code>%s</code>:</p></div><div class="col-6 float-end"><p class="price-text text-success">-%s</p></div></div>',
                trans('plugins/e-wallet::gift-card.checkout.discount_label'),
                $giftCardCode ?? '****',
                format_price($giftCardDiscount / 100, $giftCard?->gift_card_currency)
            );

            return $html;
        }, 98, 2);

        add_filter('ecommerce_thank_you_customer_info', function (?string $html, $order) {
            if (! gift_cards_enabled()) {
                return $html;
            }

            $giftCardId = $order->getOrderMetadata('gift_card_id');
            $giftCardDiscount = $order->getOrderMetadata('gift_card_discount');
            $giftCardCode = $order->getOrderMetadata('gift_card_code');

            if (! $giftCardDiscount || $giftCardDiscount <= 0) {
                return $html;
            }

            $giftCard = $giftCardId ? GiftCard::query()->find($giftCardId) : null;
            $formattedDiscount = format_price($giftCardDiscount / 100, $giftCard?->gift_card_currency);

            $html .= '<div class="order-gift-card-info mt-3 mt-md-4 mb-0 mb-sm-4">';
            $html .= '<div class="gift-card-info-card" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px;">';
            $html .= '<div class="gift-card-info-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; color: #166534; font-weight: 600;">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"></path><path d="M12 8v-2a2 2 0 0 1 2 -2h0a2 2 0 0 1 2 2v2"></path><path d="M12 8v-2a2 2 0 0 0 -2 -2h0a2 2 0 0 0 -2 2v2"></path><path d="M12 8v12"></path></svg>';
            $html .= sprintf('<span>%s</span>', trans('plugins/e-wallet::gift-card.thank_you.title'));
            $html .= '</div>';
            $html .= '<div class="gift-card-info-body">';
            $html .= '<div class="gift-card-info-row" style="display: flex; justify-content: space-between; align-items: center;">';
            $html .= '<div class="gift-card-info-label" style="display: flex; align-items: center; gap: 6px; color: #166534;">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 15l6-6"/><circle cx="9.5" cy="9.5" r=".5" fill="currentColor"/><circle cx="14.5" cy="14.5" r=".5" fill="currentColor"/><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-3-2-2 2-2-2-2 2-2-2-3 2z"/></svg>';
            $html .= sprintf('<span>%s</span>', trans('plugins/e-wallet::gift-card.thank_you.code_used'));
            $html .= '</div>';
            $html .= sprintf('<div class="gift-card-info-value" style="font-weight: 600;"><code style="background: #dcfce7; padding: 2px 8px; border-radius: 4px; color: #166534;">%s</code></div>', $giftCardCode ?? '****');
            $html .= '</div>';
            $html .= '<div class="gift-card-info-row" style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">';
            $html .= '<div class="gift-card-info-label" style="display: flex; align-items: center; gap: 6px; color: #166534;">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M16 8l-8 8"/><path d="M8 8l8 8"/></svg>';
            $html .= sprintf('<span>%s</span>', trans('plugins/e-wallet::gift-card.thank_you.discount_applied'));
            $html .= '</div>';
            $html .= sprintf('<div class="gift-card-info-value" style="font-weight: 600; color: #166534; font-size: 1.1em;">-%s</div>', $formattedDiscount);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        }, 98, 2);

        if (defined('FILTER_ECOMMERCE_PROCESS_PAYMENT')) {
            add_filter(FILTER_ECOMMERCE_PROCESS_PAYMENT, function (array $data, $request) {
                if (! gift_cards_enabled()) {
                    return $data;
                }

                $giftCardDiscount = (float) session('gift_card_discount', 0);

                if ($giftCardDiscount <= 0) {
                    return $data;
                }

                $applied = app(GiftCardCheckoutService::class)->getApplied();

                if (! $applied) {
                    return $data;
                }

                $orderAmount = (float) ($data['amount'] ?? 0);
                $actualPaymentAmount = max(0, $orderAmount - $giftCardDiscount);

                $data['amount'] = $actualPaymentAmount;

                if ($actualPaymentAmount <= 0) {
                    $token = $request->input('token') ?? session('tracked_start_checkout');
                    $orders = Order::query()->where('token', $token)->get();

                    foreach ($orders as $order) {
                        try {
                            $giftCardService = app(GiftCardCheckoutService::class);
                            $giftCardService->deductForOrder($order);
                        } catch (\Exception $e) {
                            report($e);
                        }
                    }

                    $data['error'] = false;
                    $data['charge_id'] = 'gift_card_' . ($applied['id'] ?? uniqid());
                    $data['message'] = trans('plugins/e-wallet::gift-card.checkout.payment_covered');
                }

                return $data;
            }, 1, 2);
        }

        if (defined('ORDER_COMPLETED_ACTION')) {
            add_action(ORDER_COMPLETED_ACTION, function ($order): void {
                if (! gift_cards_enabled()) {
                    return;
                }

                try {
                    $giftCardService = app(GiftCardCheckoutService::class);
                    $giftCardService->deductForOrder($order);
                } catch (\Exception $e) {
                    report($e);
                }
            }, 20);
        }

        add_action(PAYMENT_ACTION_PAYMENT_PROCESSED, function (array $data): void {
            if (! gift_cards_enabled()) {
                return;
            }

            $status = $data['status'] ?? null;
            if ($status !== PaymentStatusEnum::COMPLETED) {
                return;
            }

            $orderIds = $data['order_id'] ?? [];
            if (empty($orderIds)) {
                return;
            }

            $orders = Order::query()->whereIn('id', (array) $orderIds)->get();

            foreach ($orders as $order) {
                try {
                    $giftCardService = app(GiftCardCheckoutService::class);
                    $giftCardService->deductForOrder($order);
                } catch (\Exception $e) {
                    report($e);
                }
            }
        }, 15);

        if (defined('HANDLE_PROCESS_POST_CHECKOUT_ORDER_DATA_ECOMMERCE')) {
            add_filter(HANDLE_PROCESS_POST_CHECKOUT_ORDER_DATA_ECOMMERCE, function ($products, $request, $token, $sessionData, $response) {
                if (! gift_cards_enabled()) {
                    return $products;
                }

                $giftCardService = app(GiftCardCheckoutService::class);
                $applied = $giftCardService->getApplied();

                if (! $applied) {
                    return $products;
                }

                $orders = Order::query()->where('token', $token)->get();

                foreach ($orders as $order) {
                    try {
                        $giftCardService->deductForOrder($order);
                    } catch (\Exception $e) {
                        report($e);
                    }
                }

                $giftCardService->remove();

                return $products;
            }, 120, 5);
        }
    }

    protected function registerUnifiedDiscountFormHook(): void
    {
        add_filter('ecommerce_checkout_discount_form_html', function (string $html, array $data) {
            $discounts = $data['discounts'] ?? collect();

            return view('plugins/e-wallet::themes.checkout.unified-discount-form', [
                'discounts' => $discounts,
                'isMobile' => $data['isMobile'] ?? false,
            ])->render();
        }, 20, 2);

    }
}
