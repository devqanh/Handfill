class WithdrawalManagement {
    init() {
        $(document).on('click', '.btn-trigger-approve-withdrawal', (event) => {
            event.preventDefault()
            $('#confirm-approve-withdrawal-button').data('target', $(event.currentTarget).data('target'))
            $('#approve-withdrawal-modal').modal('show')
        })

        $(document).on('click', '#confirm-approve-withdrawal-button', (event) => {
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
                    $('#approve-withdrawal-modal').modal('hide')
                })
        })

        $(document).on('click', '.btn-trigger-reject-withdrawal', (event) => {
            event.preventDefault()
            $('#confirm-reject-withdrawal-button').data('target', $(event.currentTarget).data('target'))
            $('#reject-withdrawal-modal').modal('show')
        })

        $(document).on('click', '#confirm-reject-withdrawal-button', (event) => {
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
                    $('#reject-withdrawal-modal').modal('hide')
                })
        })
    }
}

$(() => {
    new WithdrawalManagement().init()
})
