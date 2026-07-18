<?php

namespace Botble\EWallet\Http\Requests;

use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Models\Wallet;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class WalletRequest extends Request
{
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                Rule::exists(Customer::class, 'id'),
                Rule::unique(Wallet::class, 'customer_id'),
            ],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => trans('plugins/e-wallet::e-wallet.wallet.customer'),
            'initial_balance' => trans('plugins/e-wallet::e-wallet.wallet.initial_balance'),
            'reason' => trans('plugins/e-wallet::e-wallet.wallet.reason'),
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.unique' => trans('plugins/e-wallet::e-wallet.wallet.customer_already_has_wallet'),
        ];
    }
}
