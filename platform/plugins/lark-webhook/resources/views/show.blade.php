@php
    $statusColors = ['received' => 'success', 'verified' => 'info', 'rejected' => 'danger'];
    $statusColor = $statusColors[$event->status] ?? 'secondary';
@endphp

<div class="row">
    <div class="col-12 mb-3">
        <a href="{{ route('lark-webhook.index') }}" class="btn btn-secondary">
            <x-core::icon name="ti ti-arrow-left" />
            {{ trans('plugins/lark-webhook::lark-webhook.back_to_list') }}
        </a>
    </div>

    <div class="col-md-5">
        <x-core::card>
            <x-core::card.header>
                <x-core::card.title>
                    {{ trans('plugins/lark-webhook::lark-webhook.event_info') }}
                </x-core::card.title>
            </x-core::card.header>
            <x-core::card.body>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th style="width: 40%;">ID</th>
                            <td>#{{ $event->getKey() }}</td>
                        </tr>
                        <tr>
                            <th>{{ trans('plugins/lark-webhook::lark-webhook.status') }}</th>
                            <td><span class="badge bg-{{ $statusColor }}">{{ trans('plugins/lark-webhook::lark-webhook.statuses.' . $event->status) }}</span></td>
                        </tr>
                        <tr>
                            <th>{{ trans('plugins/lark-webhook::lark-webhook.event_type') }}</th>
                            <td><code>{{ $event->event_type }}</code></td>
                        </tr>
                        <tr>
                            <th>{{ trans('plugins/lark-webhook::lark-webhook.event_id') }}</th>
                            <td>{{ $event->event_id ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ trans('plugins/lark-webhook::lark-webhook.schema_version') }}</th>
                            <td>{{ $event->schema_version ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>App ID</th>
                            <td>{{ $event->app_id ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>Tenant Key</th>
                            <td>{{ $event->tenant_key ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ trans('plugins/lark-webhook::lark-webhook.ip_address') }}</th>
                            <td>{{ $event->ip_address ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ trans('plugins/lark-webhook::lark-webhook.event_time') }}</th>
                            <td>{{ $event->event_created_at ? BaseHelper::formatDateTime($event->event_created_at) : '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ trans('plugins/lark-webhook::lark-webhook.received_at') }}</th>
                            <td>{{ BaseHelper::formatDateTime($event->created_at) }}</td>
                        </tr>
                        @if ($event->message)
                            <tr>
                                <th>{{ trans('plugins/lark-webhook::lark-webhook.message') }}</th>
                                <td>{{ $event->message }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </x-core::card.body>
        </x-core::card>
    </div>

    <div class="col-md-7">
        <x-core::card>
            <x-core::card.header>
                <x-core::card.title>
                    {{ trans('plugins/lark-webhook::lark-webhook.payload') }}
                </x-core::card.title>
            </x-core::card.header>
            <x-core::card.body>
                <pre class="p-3 bg-body-secondary rounded" style="max-height: 400px; overflow: auto;"><code>{{ $event->pretty_payload }}</code></pre>
            </x-core::card.body>
        </x-core::card>

        <x-core::card>
            <x-core::card.header>
                <x-core::card.title>
                    {{ trans('plugins/lark-webhook::lark-webhook.headers') }}
                </x-core::card.title>
            </x-core::card.header>
            <x-core::card.body>
                <pre class="p-3 bg-body-secondary rounded" style="max-height: 300px; overflow: auto;"><code>{{ $event->pretty_headers }}</code></pre>
            </x-core::card.body>
        </x-core::card>
    </div>
</div>
