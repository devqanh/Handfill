@extends('core/table::table')

@section('content')
    <x-core::card class="mb-3">
        <x-core::card.body>
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div class="flex-grow-1" style="min-width: 260px;">
                    <h5 class="mb-1">
                        <x-core::icon name="ti ti-webhook" />
                        {{ trans('plugins/lark-webhook::lark-webhook.webhook_url') }}
                    </h5>
                    <p class="text-muted mb-2">
                        {{ trans('plugins/lark-webhook::lark-webhook.webhook_url_helper') }}
                    </p>
                    <div class="input-group">
                        <input
                            type="text"
                            class="form-control"
                            id="lark-webhook-url"
                            value="{{ $webhookUrl }}"
                            readonly
                            onclick="this.select()"
                        >
                        <button type="button" class="btn btn-secondary" id="lark-webhook-copy">
                            <x-core::icon name="ti ti-copy" />
                            {{ trans('plugins/lark-webhook::lark-webhook.copy') }}
                        </button>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('lark-webhook.settings') }}" class="btn btn-primary">
                        <x-core::icon name="ti ti-settings" />
                        {{ trans('plugins/lark-webhook::lark-webhook.settings.name') }}
                    </a>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#regenerate-token-modal">
                        <x-core::icon name="ti ti-refresh" />
                        {{ trans('plugins/lark-webhook::lark-webhook.regenerate_token') }}
                    </button>
                </div>
            </div>
        </x-core::card.body>
    </x-core::card>

    @parent
@endsection

@push('footer')
    <x-core::modal.action
        id="modal-confirm-delete-records"
        type="danger"
        :title="trans('plugins/lark-webhook::lark-webhook.empty_events')"
        :description="trans('plugins/lark-webhook::lark-webhook.confirm_empty_events')"
        :submit-button-label="trans('core/base::tables.delete')"
        :submit-button-attrs="['class' => 'button-delete-records', 'data-url' => route('lark-webhook.empty')]"
        :close-button-label="trans('core/base::tables.cancel')"
    />

    <form method="POST" action="{{ route('lark-webhook.regenerate-token') }}">
        @csrf
        <x-core::modal id="regenerate-token-modal" :title="trans('plugins/lark-webhook::lark-webhook.regenerate_token')" type="warning">
            <p>{{ trans('plugins/lark-webhook::lark-webhook.regenerate_token_confirm') }}</p>

            <x-slot:footer>
                <x-core::button type="button" data-bs-dismiss="modal">
                    {{ trans('core/base::tables.cancel') }}
                </x-core::button>
                <x-core::button type="submit" color="warning">
                    {{ trans('plugins/lark-webhook::lark-webhook.regenerate_token') }}
                </x-core::button>
            </x-slot:footer>
        </x-core::modal>
    </form>

    <script>
        $(() => {
            $('#lark-webhook-copy').on('click', function () {
                const input = document.getElementById('lark-webhook-url')
                input.select()
                navigator.clipboard?.writeText(input.value)
                Botble.showSuccess('{{ trans('plugins/lark-webhook::lark-webhook.copied') }}')
            })

            $(document).on('click', '.empty-lark-webhook-events-button', function (event) {
                event.preventDefault()
                const $modal = $('#modal-confirm-delete-records')
                $modal.modal('show')

                $modal.off('click', '.button-delete-records').on('click', '.button-delete-records', (event) => {
                    event.preventDefault()
                    const _self = $(event.currentTarget)
                    $httpClient
                        .make()
                        .withButtonLoading(_self)
                        .delete(_self.data('url'))
                        .then(({ data }) => {
                            _self.closest('.modal').modal('hide')
                            $('.table').DataTable().draw()
                            Botble.showSuccess(data.message)
                        })
                })
            })
        })
    </script>
@endpush
