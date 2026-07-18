class WalletAdjustment {
    init() {
        $(document).on('click', '[data-bb-toggle="adjust-balance"]', () => {
            $('#adjust-balance-modal').modal('show')
        })

        $(document).on('click', '#confirm-adjust-balance-button', (event) => {
            event.preventDefault()

            const _self = $(event.currentTarget)
            const form = _self.closest('.modal-dialog').find('form')
            const url = form.data('action')

            $httpClient
                .make()
                .withButtonLoading(_self)
                .post(url, form.serialize())
                .then(({ data }) => {
                    if (!data.error) {
                        Botble.showSuccess(data.message)
                        _self.closest('.modal').modal('hide')
                        setTimeout(() => window.location.reload(), 1500)
                    } else {
                        Botble.showError(data.message)
                    }
                })
        })
    }
}

$(() => {
    new WalletAdjustment().init()
})
