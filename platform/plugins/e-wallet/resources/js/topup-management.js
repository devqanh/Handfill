class TopUpManagement {
    init() {
        $(document).on('click', '.btn-trigger-complete-topup', (event) => {
            event.preventDefault()
            $('#confirm-complete-topup-button').data('target', $(event.currentTarget).data('target'))
            $('#complete-topup-modal').modal('show')
        })

        $(document).on('click', '#confirm-complete-topup-button', (event) => {
            event.preventDefault()
            const _self = $(event.currentTarget)

            $httpClient
                .make()
                .withButtonLoading(_self)
                .post(_self.data('target'))
                .then(({ data }) => {
                    if (!data.error) {
                        Botble.showSuccess(data.message)
                        window.location.reload()
                    } else {
                        Botble.showError(data.message)
                    }
                    $('#complete-topup-modal').modal('hide')
                })
        })

        $(document).on('click', '.btn-trigger-cancel-topup', (event) => {
            event.preventDefault()
            $('#confirm-cancel-topup-button').data('target', $(event.currentTarget).data('target'))
            $('#cancel-topup-modal').modal('show')
        })

        $(document).on('click', '#confirm-cancel-topup-button', (event) => {
            event.preventDefault()
            const _self = $(event.currentTarget)

            $httpClient
                .make()
                .withButtonLoading(_self)
                .post(_self.data('target'))
                .then(({ data }) => {
                    if (!data.error) {
                        Botble.showSuccess(data.message)
                        window.location.reload()
                    } else {
                        Botble.showError(data.message)
                    }
                    $('#cancel-topup-modal').modal('hide')
                })
        })
    }
}

$(() => {
    new TopUpManagement().init()
})
