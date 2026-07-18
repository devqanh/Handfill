<?php

use Botble\Payment\Enums\PaymentMethodEnum;

if (! function_exists('get_wallet_setting')) {
    function get_wallet_setting(string $key, mixed $default = null): mixed
    {
        return setting('e_wallet_' . $key, $default);
    }
}

if (! function_exists('set_wallet_setting')) {
    function set_wallet_setting(string $key, mixed $value): void
    {
        setting()->set('e_wallet_' . $key, $value)->save();
    }
}

if (! function_exists('get_allowed_topup_payment_methods')) {
    function get_allowed_topup_payment_methods(): array
    {
        $saved = get_wallet_setting('topup_payment_methods');

        if (empty($saved)) {
            $methods = [];
            foreach (PaymentMethodEnum::toArray() as $value) {
                if ($value === E_WALLET_PAYMENT_METHOD_NAME) {
                    continue;
                }

                if (get_payment_setting('status', $value)) {
                    $methods[] = $value;
                }
            }

            return $methods;
        }

        if (is_string($saved)) {
            $methods = json_decode($saved, true) ?: [];
        } else {
            $methods = (array) $saved;
        }

        return array_values(array_filter($methods, function ($method) {
            return $method !== E_WALLET_PAYMENT_METHOD_NAME && get_payment_setting('status', $method);
        }));
    }
}

if (! function_exists('is_topup_payment_method_allowed')) {
    function is_topup_payment_method_allowed(string $method): bool
    {
        return in_array($method, get_allowed_topup_payment_methods());
    }
}

if (! function_exists('gift_cards_enabled')) {
    function gift_cards_enabled(): bool
    {
        return (bool) get_wallet_setting('gift_cards_enabled', true);
    }
}

if (! function_exists('get_gift_card_code_prefix')) {
    function get_gift_card_code_prefix(): string
    {
        return strtoupper(get_wallet_setting('gift_card_code_prefix', 'GC'));
    }
}

if (! function_exists('get_gift_card_default_expiry_days')) {
    function get_gift_card_default_expiry_days(): ?int
    {
        $days = get_wallet_setting('gift_card_default_expiry_days');

        return $days ? (int) $days : null;
    }
}

if (! function_exists('get_gift_card_min_value')) {
    function get_gift_card_min_value(): int
    {
        return (int) get_wallet_setting('gift_card_min_value', 100);
    }
}

if (! function_exists('get_gift_card_max_value')) {
    function get_gift_card_max_value(): int
    {
        return (int) get_wallet_setting('gift_card_max_value', 50000000);
    }
}

if (! function_exists('gift_card_public_balance_check_enabled')) {
    function gift_card_public_balance_check_enabled(): bool
    {
        return (bool) get_wallet_setting('gift_card_public_balance_check', true);
    }
}

if (! function_exists('gift_card_purchase_enabled')) {
    function gift_card_purchase_enabled(): bool
    {
        return gift_cards_enabled() && (bool) get_wallet_setting('gift_card_purchase_enabled', true);
    }
}

if (! function_exists('get_gift_card_predefined_values')) {
    function get_gift_card_predefined_values(): array
    {
        $values = get_wallet_setting('gift_card_predefined_values');

        if (empty($values)) {
            return [1000, 2500, 5000, 10000];
        }

        if (is_string($values)) {
            $values = json_decode($values, true);
        }

        return is_array($values) ? $values : [1000, 2500, 5000, 10000];
    }
}

if (! function_exists('gift_card_allow_partial_use')) {
    function gift_card_allow_partial_use(): bool
    {
        return (bool) get_wallet_setting('gift_card_allow_partial_use', true);
    }
}

if (! function_exists('unified_discount_field_enabled')) {
    function unified_discount_field_enabled(): bool
    {
        return gift_cards_enabled() && (bool) get_wallet_setting('unified_discount_field', true);
    }
}
