<div class="mb-3">
    <button type="button" class="btn btn-info" id="lark-test-push" data-url="{{ route('lark-webhook.test-push') }}">
        <x-core::icon name="ti ti-send" />
        {{ trans('plugins/lark-webhook::lark-webhook.settings.test_push') }}
    </button>
    <span class="text-muted ms-2">{{ trans('plugins/lark-webhook::lark-webhook.settings.test_push_helper') }}</span>
    <div id="lark-test-push-result" class="mt-2"></div>
</div>

<script>
    $(() => {
        $('#lark-test-push').on('click', function () {
            const _self = $(this)
            const $result = $('#lark-test-push-result')
            $result.html('')

            $httpClient
                .make()
                .withButtonLoading(_self)
                .post(_self.data('url'))
                .then(({ data }) => {
                    $result.html('<div class="alert alert-success mb-0">' + data.message + '</div>')
                })
                .catch((error) => {
                    const msg = error.response?.data?.message || 'Error'
                    $result.html('<div class="alert alert-danger mb-0">' + msg + '</div>')
                })
        })
    })
</script>
