<div class="bank-transfer-info">
    <div class="bank-transfer-info__content">
        <div class="bank-transfer-info__icon">
            <x-core::icon name="ti ti-info-circle" />
        </div>
        <div class="bank-transfer-info__details">
            <div class="bank-transfer-info__text">
                {!! BaseHelper::clean($bankInfo) !!}
            </div>
            <p class="bank-transfer-info__amount">{!! BaseHelper::clean(
                trans('plugins/e-wallet::e-wallet.topup.bank_transfer_amount', ['amount' => format_price($topupAmount / 100, $topupCurrency)]),
            ) !!}</p>
            <p class="bank-transfer-info__description">{!! BaseHelper::clean(
                trans('plugins/e-wallet::e-wallet.topup.bank_transfer_description', ['code' => $topupCode]),
            ) !!}</p>
        </div>
    </div>
</div>
