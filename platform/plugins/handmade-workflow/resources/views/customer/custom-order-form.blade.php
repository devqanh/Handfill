@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/handmade-workflow::handmade-workflow.custom_order.title'))

@section('content')
    {{-- Scoped to .hw-order so nothing here can leak into the rest of the account area.
         Inline rather than in the theme stylesheet: this page already has to inline its
         script (the layout has no @stack), and keeping both together means the whole
         screen is one file to reason about. --}}
    <style>
        .hw-order {
            --hw-line: #e5e7eb;
            --hw-muted: #6b7280;
            --hw-surface: #f9fafb;
        }

        .hw-order .hw-lead {
            color: var(--hw-muted);
            max-width: 60ch;
        }

        .hw-steps {
            display: grid;
            gap: .625rem;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            margin-bottom: 1.25rem;
        }

        .hw-step {
            display: flex;
            gap: .625rem;
            align-items: flex-start;
            padding: .75rem .875rem;
            border: 1px solid var(--hw-line);
            border-radius: 12px;
            background: #fff;
        }

        .hw-step span:first-child {
            flex: none;
            width: 26px;
            height: 26px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            font-size: .75rem;
            font-weight: 600;
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), .12);
        }

        .hw-section {
            border: 1px solid var(--hw-line);
            border-radius: 14px;
            background: #fff;
            margin-bottom: 1rem;
        }

        .hw-section__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 1.125rem;
            border-bottom: 1px solid var(--hw-line);
        }

        .hw-section__title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }

        .hw-section__body {
            padding: 1.125rem;
        }

        .hw-dropzone {
            border: 1.5px dashed rgba(var(--bs-primary-rgb), .45);
            border-radius: 12px;
            background: var(--hw-surface);
            padding: 1rem;
        }

        /* One row per item. Collapsed by default so a 50-line import reads as a list,
           not as fifty full-height forms stacked on top of each other. */
        .hw-item {
            border: 1px solid var(--hw-line);
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
        }

        .hw-item + .hw-item {
            margin-top: .625rem;
        }

        .hw-item__head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem .875rem;
            cursor: pointer;
            list-style: none;
        }

        .hw-item__head::-webkit-details-marker {
            display: none;
        }

        .hw-item__head:hover {
            background: var(--hw-surface);
        }

        .hw-item[open] .hw-item__head {
            background: var(--hw-surface);
            border-bottom: 1px solid var(--hw-line);
        }

        .hw-item__no {
            flex: none;
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            font-size: .8125rem;
            font-weight: 600;
            color: var(--hw-muted);
            background: var(--hw-line);
        }

        .hw-item__label {
            flex: 1 1 auto;
            min-width: 0;
        }

        .hw-item__name {
            font-weight: 600;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .hw-item__meta {
            font-size: .8125rem;
            color: var(--hw-muted);
        }

        .hw-item__chevron {
            flex: none;
            color: var(--hw-muted);
            transition: transform .15s ease;
        }

        .hw-item[open] .hw-item__chevron {
            transform: rotate(180deg);
        }

        .hw-item__body {
            padding: 1.125rem;
        }

        .hw-group {
            border: 1px solid #eef0f3;
            border-radius: 10px;
            padding: .875rem;
        }

        .hw-group + .hw-group {
            margin-top: .75rem;
        }

        .hw-group__title {
            font-size: .6875rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--hw-muted);
            margin-bottom: .75rem;
        }

        /* Big enough to actually judge a design from — these are the photos the item
           gets made from, so a postage-stamp preview is no use to anyone. */
        .hw-thumbs {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .hw-thumbs img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid var(--hw-line);
        }

        .hw-order .hw-links {
            font-size: .8125rem;
        }

        .hw-linkbar {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        /* A tile per link: the photo at a size worth looking at, with what we found
           when we opened it written underneath. */
        .hw-link {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            width: 134px;
            padding: .375rem;
            font-size: .6875rem;
            background: #fff;
            border: 1px solid var(--hw-line);
            border-radius: 10px;
        }

        .hw-link__frame {
            display: grid;
            place-items: center;
            width: 120px;
            height: 120px;
            overflow: hidden;
            border-radius: 8px;
            background: var(--hw-surface);
            color: var(--hw-muted);
            font-size: 1.75rem;
        }

        .hw-link__frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hw-link__tag {
            font-weight: 600;
        }

        .hw-link a {
            display: block;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .hw-link--page {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .hw-link--broken {
            border-color: #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .hw-link--broken .hw-link__frame {
            background: #fee2e2;
            color: #b91c1c;
        }

        .hw-link--page .hw-link__frame {
            background: #fef3c7;
            color: #92400e;
        }

        /* Keeps the submit button reachable no matter how long the item list gets. */
        .hw-submit-bar {
            position: sticky;
            bottom: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: .875rem 1.125rem;
            background: #fff;
            border: 1px solid var(--hw-line);
            border-radius: 14px;
            box-shadow: 0 -6px 16px rgb(0 0 0 / 6%);
        }
    </style>

    <div class="hw-order">
        <p class="hw-lead mb-3">
            {{ trans('plugins/handmade-workflow::handmade-workflow.custom_order.description') }}
        </p>

        <div class="hw-steps">
            @foreach (trans('plugins/handmade-workflow::handmade-workflow.custom_order.steps') as $index => $step)
                <div class="hw-step">
                    <span>{{ $index + 1 }}</span>
                    <span class="small">{{ $step }}</span>
                </div>
            @endforeach
        </div>

        {{-- Import sits OUTSIDE the order form: a nested <form> is invalid, and the
             upload is its own round trip that only fills the fields below. --}}
        <div class="hw-section" id="handmade-import">
            <div class="hw-section__head">
                <div>
                    <h2 class="hw-section__title">
                        <x-core::icon name="ti ti-file-spreadsheet" class="me-1 text-primary" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.import.title') }}
                    </h2>
                    <p class="text-muted small mb-0 mt-1">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.import.description') }}
                    </p>
                </div>
                <a href="{{ route('customer.custom-orders.template') }}" class="btn btn-outline-primary btn-sm">
                    <x-core::icon name="ti ti-download" class="me-1" />
                    {{ trans('plugins/handmade-workflow::handmade-workflow.import.download_template') }}
                </a>
            </div>
            <div class="hw-section__body">
                <div class="hw-dropzone">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold" for="handmade-import-file">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.import.file') }}
                            </label>
                            <input type="file" class="form-control" id="handmade-import-file"
                                accept=".xlsx,.ods,.csv,.txt">
                        </div>
                        <div class="col-md-4 d-grid align-self-end">
                            <button type="button" class="btn btn-primary" id="handmade-import-submit">
                                <x-core::icon name="ti ti-upload" class="me-1" />
                                {{ trans('plugins/handmade-workflow::handmade-workflow.import.submit') }}
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.import.file_help', ['max' => $maxItems]) }}
                        {{ trans('plugins/handmade-workflow::handmade-workflow.import.replace_warning') }}
                    </small>
                </div>

                <div id="handmade-import-message" class="mt-3 d-none"></div>

                <details class="mt-3">
                    <summary class="small text-muted" style="cursor:pointer">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.import.columns_hint') }}
                    </summary>
                    <ul class="small text-muted mt-2 mb-0 ps-3">
                        @foreach ($importColumns as $column)
                            <li class="mb-1">
                                <strong>{{ $column['label'] }}</strong>
                                @if ($column['required'])
                                    <span class="text-danger">*</span>
                                @endif
                                — {{ $column['description'] }}
                            </li>
                        @endforeach
                    </ul>
                </details>
            </div>
        </div>

        <x-core::form
            :url="route('customer.custom-orders.store')"
            method="POST"
            :files="true"
            id="handmade-custom-order-form"
        >
            <div class="hw-section">
                <div class="hw-section__head">
                    <h2 class="hw-section__title">
                        <x-core::icon name="ti ti-adjustments" class="me-1 text-primary" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.general_info') }}
                    </h2>
                </div>
                <div class="hw-section__body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="customer_group">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.customer_group') }}
                                <span class="text-danger">*</span>
                            </label>
                            <select name="customer_group" id="customer_group" class="form-select" required>
                                @foreach ($customerGroups as $value => $label)
                                    <option value="{{ $value }}" @selected(old('customer_group') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.customer_group_help') }}
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="expected_date">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.expected_date') }}
                            </label>
                            <input
                                type="date"
                                name="expected_date"
                                id="expected_date"
                                class="form-control"
                                value="{{ old('expected_date') }}"
                                min="{{ now()->addDay()->format('Y-m-d') }}"
                            >
                            <small class="text-muted">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.expected_date_help') }}
                            </small>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="address_id">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.shipping_address') }}
                            </label>

                            @if ($addresses->isEmpty())
                                <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 mb-0 py-2">
                                    <span class="small flex-grow-1">
                                        {{ trans('plugins/handmade-workflow::handmade-workflow.no_address') }}
                                    </span>
                                    <a href="{{ route('customer.address.create') }}" class="btn btn-sm btn-warning">
                                        <x-core::icon name="ti ti-plus" class="me-1" />
                                        {{ trans('plugins/handmade-workflow::handmade-workflow.add_address') }}
                                    </a>
                                </div>
                            @else
                                <select name="address_id" id="address_id" class="form-select">
                                    <option value="">
                                        {{ trans('plugins/handmade-workflow::handmade-workflow.shipping_address_none') }}
                                    </option>
                                    @foreach ($addresses as $address)
                                        <option value="{{ $address->getKey() }}"
                                            @selected(old('address_id', $addresses->firstWhere('is_default', true)?->getKey()) == $address->getKey())>
                                            {{ $address->name }}@if ($address->phone) — {{ $address->phone }}@endif — {{ $address->full_address }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">
                                    {{ trans('plugins/handmade-workflow::handmade-workflow.shipping_address_help') }}
                                    <a href="{{ route('customer.address') }}">{{ trans('plugins/handmade-workflow::handmade-workflow.manage_addresses') }}</a>
                                </small>
                            @endif
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="note">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.general_note') }}
                            </label>
                            <textarea name="note" id="note" class="form-control" rows="2"
                                placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.general_note_placeholder') }}">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hw-section">
                <div class="hw-section__head">
                    <h2 class="hw-section__title">
                        <x-core::icon name="ti ti-package" class="me-1 text-primary" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.items') }}
                    </h2>
                    <span class="text-muted small" id="handmade-items-summary"></span>
                </div>
                <div class="hw-section__body">
                    <div id="handmade-items"></div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="handmade-add-item">
                            <x-core::icon name="ti ti-plus" class="me-1" />
                            {{ trans('plugins/handmade-workflow::handmade-workflow.add_item') }}
                        </button>
                        <small class="text-muted">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.max_items_hint', ['max' => $maxItems]) }}
                        </small>
                    </div>
                </div>
            </div>

            <div class="hw-submit-bar">
                <small class="text-muted">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.custom_order.submit_help') }}
                </small>
                <button type="submit" class="btn btn-primary">
                    <x-core::icon name="ti ti-send" class="me-1" />
                    {{ trans('plugins/handmade-workflow::handmade-workflow.custom_order.submit') }}
                </button>
            </div>
        </x-core::form>
    </div>

    <template id="handmade-item-template">
        <details class="hw-item">
            <summary class="hw-item__head">
                <span class="hw-item__no"></span>
                <span class="hw-item__label">
                    <span class="hw-item__name"></span>
                    <span class="hw-item__meta"></span>
                </span>
                <button type="button" class="btn btn-sm btn-link text-danger p-1 handmade-remove-item"
                    title="{{ trans('plugins/handmade-workflow::handmade-workflow.remove_item') }}">
                    <x-core::icon name="ti ti-trash" />
                </button>
                <x-core::icon name="ti ti-chevron-down" class="hw-item__chevron" />
            </summary>

            <div class="hw-item__body">
                <div class="hw-group">
                    <p class="hw-group__title">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.group_product') }}
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_name') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" data-name="name" required
                                placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.item_name_placeholder') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_sku') }}
                            </label>
                            <input type="text" class="form-control" data-name="sku"
                                placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.item_sku_placeholder') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_qty') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" data-name="qty" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_marketplace_order_id') }}
                            </label>
                            <input type="text" class="form-control" data-name="marketplace_order_id"
                                placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.item_marketplace_order_id_placeholder') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_ordered_at') }}
                            </label>
                            <input type="date" class="form-control" data-name="ordered_at">
                        </div>
                        <div class="col-12">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_note') }}
                            </label>
                            <textarea class="form-control" rows="3" data-name="note"
                                placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.item_note_placeholder') }}"></textarea>
                        </div>
                    </div>
                </div>

                <div class="hw-group">
                    <p class="hw-group__title">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.group_photos') }}
                    </p>
                    <input type="file" class="form-control handmade-item-images" data-name="images" data-array
                        accept="image/jpeg,image/png,image/webp" multiple>
                    <small class="text-muted d-block mt-1">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.item_images_help', ['max' => $maxImages]) }}
                    </small>
                    <div class="hw-thumbs mt-2 handmade-item-preview"></div>

                    {{-- A plain textarea rather than hidden inputs: an imported link that
                         turns out to be dead has to be fixable right here. --}}
                    <label class="form-label mt-3">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.item_image_links') }}
                    </label>
                    <textarea class="form-control font-monospace hw-links" rows="2" data-name="image_links"
                        placeholder="https://..."></textarea>
                    <small class="text-muted d-block mt-1">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.item_image_links_help') }}
                    </small>
                    <div class="hw-linkbar mt-2 handmade-item-link-status"></div>

                    <label class="form-label mt-3">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.item_fabric_images') }}
                    </label>
                    <textarea class="form-control font-monospace hw-links" rows="2" data-name="fabric_image_links"
                        placeholder="https://..."></textarea>
                    <div class="hw-linkbar mt-2 handmade-item-fabric-status"></div>
                </div>

                <div class="hw-group">
                    <p class="hw-group__title">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.item_recipient') }}
                    </p>
                    <p class="text-muted small mb-3">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.item_recipient_help') }}
                    </p>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_recipient_name') }}
                            </label>
                            <input type="text" class="form-control" data-name="recipient_name">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_recipient_email') }}
                            </label>
                            <input type="email" class="form-control" data-name="recipient_email">
                        </div>
                        <div class="col-12">
                            <label class="form-label">
                                {{ trans('plugins/handmade-workflow::handmade-workflow.item_recipient_address') }}
                            </label>
                            <textarea class="form-control" rows="3" data-name="recipient_address"
                                placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.item_recipient_address_placeholder') }}"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </details>
    </template>
    {{-- Inline on purpose: the theme layout has no @stack('footer'), so a @push would be dropped. --}}
    <script>
        (function () {
            const form = document.getElementById('handmade-custom-order-form')
            const wrapper = document.getElementById('handmade-items')
            const template = document.getElementById('handmade-item-template')
            const addButton = document.getElementById('handmade-add-item')
            const summary = document.getElementById('handmade-items-summary')
            const maxItems = {{ (int) $maxItems }}
            const maxImages = {{ (int) $maxImages }}

            // The semicolons are load-bearing: a Blade directive eats the newline that
            // follows it, so without them the next statement lands on the same line and
            // the whole script dies with "Unexpected token 'const'".
            const UNNAMED = @json(trans('plugins/handmade-workflow::handmade-workflow.item_unnamed'));
            const QTY_LABEL = @json(trans('plugins/handmade-workflow::handmade-workflow.item_qty'));
            const SUMMARY = @json(trans('plugins/handmade-workflow::handmade-workflow.items_summary'));
            const LINK_STATUS = @json(trans('plugins/handmade-workflow::handmade-workflow.link_status'));
            const BROKEN_LINK_ALERT = @json(trans('plugins/handmade-workflow::handmade-workflow.link_broken_alert'));

            const TEXT_FIELDS = [
                'name', 'sku', 'qty', 'marketplace_order_id', 'ordered_at', 'note',
                'recipient_name', 'recipient_email', 'recipient_address',
            ]

            // Arrive from the importer as arrays, live in the form as one link per line.
            const LINK_FIELDS = ['image_links', 'fabric_image_links']

            const rows = () => wrapper.querySelectorAll('.hw-item')

            function describe(row, index) {
                const value = (key) => row.querySelector(`[data-name="${key}"]`)?.value.trim() || ''

                row.querySelector('.hw-item__no').textContent = index + 1
                row.querySelector('.hw-item__name').textContent = value('name') || UNNAMED

                const meta = [`${QTY_LABEL}: ${value('qty') || 1}`]
                if (value('sku')) meta.push(value('sku'))
                if (value('recipient_name')) meta.push(value('recipient_name'))

                row.querySelector('.hw-item__meta').textContent = meta.join(' · ')
            }

            // Field names carry the row index, so they are rewritten whenever rows change.
            function reindex() {
                let units = 0

                rows().forEach((row, index) => {
                    describe(row, index)

                    units += parseInt(row.querySelector('[data-name="qty"]')?.value, 10) || 0

                    row.querySelectorAll('[data-name]').forEach((field) => {
                        const key = field.dataset.name
                        field.name = 'array' in field.dataset
                            ? `items[${index}][${key}][]`
                            : `items[${index}][${key}]`
                    })
                })

                summary.textContent = SUMMARY
                    .replace(':items', rows().length)
                    .replace(':units', units)

                addButton.disabled = rows().length >= maxItems
            }

            // One tile per link: the photo itself when the link really is a photo, a
            // caution when it only opens a web page, red when it opens nothing at all.
            function linkChip(href, status) {
                const chip = document.createElement('span')
                chip.className = `hw-link hw-link--${status}`

                const frame = document.createElement('span')
                frame.className = 'hw-link__frame'

                if (status === 'image') {
                    const img = document.createElement('img')
                    img.src = href
                    img.loading = 'lazy'
                    frame.appendChild(img)
                } else {
                    // Nothing to show: a page has no thumbnail and a dead link has nothing.
                    frame.textContent = status === 'broken' ? '⚠' : '🔗'
                }

                chip.appendChild(frame)

                const tag = document.createElement('span')
                tag.className = 'hw-link__tag'
                tag.textContent = LINK_STATUS[status] || ''
                chip.appendChild(tag)

                const anchor = document.createElement('a')
                anchor.href = href
                anchor.target = '_blank'
                anchor.rel = 'noopener'
                anchor.textContent = href.replace(/^https?:\/\//, '')
                anchor.title = href
                chip.appendChild(anchor)

                return chip
            }

            function showLinkStatus(row, field, links, statuses) {
                const bar = row.querySelector(
                    field === 'image_links' ? '.handmade-item-link-status' : '.handmade-item-fabric-status'
                )

                bar.innerHTML = ''
                links.forEach((href) => bar.appendChild(linkChip(href, statuses[href] || 'page')))
            }

            function fill(row, data, statuses) {
                TEXT_FIELDS.forEach((key) => {
                    const field = row.querySelector(`[data-name="${key}"]`)
                    if (field && data[key] !== null && data[key] !== undefined) {
                        field.value = data[key]
                    }
                })

                LINK_FIELDS.forEach((key) => {
                    const links = data[key] || []
                    row.querySelector(`[data-name="${key}"]`).value = links.join('\n')
                    showLinkStatus(row, key, links, statuses || {})
                })
            }

            function addItem(data, open = true, statuses = {}) {
                if (rows().length >= maxItems) {
                    return null
                }

                wrapper.appendChild(template.content.cloneNode(true))
                const row = wrapper.lastElementChild
                row.open = open

                if (data) {
                    fill(row, data, statuses)
                }

                reindex()

                return row
            }

            wrapper.addEventListener('click', (event) => {
                const remove = event.target.closest('.handmade-remove-item')

                if (!remove) {
                    return
                }

                // The button lives inside <summary>; without this the click would
                // also toggle the row it is about to delete.
                event.preventDefault()

                // Always keep at least one row so the form stays submittable.
                if (rows().length <= 1) {
                    return
                }

                remove.closest('.hw-item').remove()
                reindex()
            })

            // Keep the collapsed header honest while the customer types.
            wrapper.addEventListener('input', (event) => {
                if (!event.target.matches('[data-name]')) {
                    return
                }

                // An edited link has not been checked yet, so drop the old verdict
                // rather than leave a stale red chip blocking a link just fixed.
                // The server checks again on submit either way.
                if (event.target.matches('.hw-links')) {
                    const field = event.target.dataset.name
                    showLinkStatus(event.target.closest('.hw-item'), field, [], {})
                }

                reindex()
            })

            wrapper.addEventListener('change', (event) => {
                const input = event.target
                if (!input.classList.contains('handmade-item-images')) {
                    return
                }

                const preview = input.closest('.hw-item').querySelector('.handmade-item-preview')
                preview.innerHTML = ''

                if (input.files.length > maxImages) {
                    input.value = ''
                    preview.innerHTML = `<span class="text-danger small">{{ trans('plugins/handmade-workflow::handmade-workflow.too_many_images', ['max' => '__MAX__']) }}</span>`.replace('__MAX__', maxImages)
                    return
                }

                Array.from(input.files).forEach((file) => {
                    const img = document.createElement('img')
                    img.src = URL.createObjectURL(file)
                    img.onload = () => URL.revokeObjectURL(img.src)
                    preview.appendChild(img)
                })
            })

            // A required field inside a closed <details> cannot be focused, and the
            // browser then refuses to submit without telling anyone why. Open the row
            // that holds the offending field instead.
            form.addEventListener('invalid', (event) => {
                const row = event.target.closest('.hw-item')

                if (row && !row.open) {
                    row.open = true
                    event.target.focus()
                }
            }, true)

            // Stop an order carrying a link we already know is dead. The server refuses
            // it too; this only saves the customer a round trip.
            form.addEventListener('submit', (event) => {
                const broken = wrapper.querySelector('.hw-link--broken')

                if (!broken) {
                    return
                }

                event.preventDefault()
                event.stopImmediatePropagation()

                const row = broken.closest('.hw-item')
                row.open = true
                row.scrollIntoView({ block: 'center' })

                window.alert(BROKEN_LINK_ALERT)
            }, true)

            addButton.addEventListener('click', () => addItem()?.scrollIntoView({ block: 'nearest' }))

            addItem()

            // ---- Spreadsheet import -------------------------------------------------

            const importUrl = @json(route('customer.custom-orders.import'));
            const fileInput = document.getElementById('handmade-import-file')
            const importButton = document.getElementById('handmade-import-submit')
            const messageBox = document.getElementById('handmade-import-message')
            const readingText = @json(trans('plugins/handmade-workflow::handmade-workflow.import.reading'));
            const warningsTitle = @json(trans('plugins/handmade-workflow::handmade-workflow.import.warnings_title'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content

            function showMessage(type, text, details) {
                messageBox.className = `alert alert-${type} mt-3`

                const paragraph = document.createElement('p')
                paragraph.className = details && details.length ? 'mb-2' : 'mb-0'
                paragraph.textContent = text

                messageBox.innerHTML = ''
                messageBox.appendChild(paragraph)

                if (details && details.length) {
                    const heading = document.createElement('strong')
                    heading.className = 'small d-block'
                    heading.textContent = warningsTitle
                    messageBox.appendChild(heading)

                    const list = document.createElement('ul')
                    list.className = 'small mb-0 ps-3'
                    details.forEach((line) => {
                        const li = document.createElement('li')
                        li.textContent = line
                        list.appendChild(li)
                    })
                    messageBox.appendChild(list)
                }
            }

            importButton.addEventListener('click', async () => {
                if (!fileInput.files.length) {
                    fileInput.focus()
                    return
                }

                const payload = new FormData()
                payload.append('file', fileInput.files[0])

                importButton.disabled = true
                showMessage('info', readingText)

                try {
                    const response = await fetch(importUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        body: payload,
                    })

                    const body = await response.json()

                    if (!response.ok || body.error) {
                        // Laravel answers a rejected upload with 422 and {errors: {...}}.
                        const errors = body.errors ? Object.values(body.errors).flat() : []
                        showMessage('danger', body.message || errors[0] || readingText, errors.slice(1))
                        return
                    }

                    wrapper.innerHTML = ''
                    // Only the first row is left open: an imported sheet is meant to be
                    // skimmed as a list, then opened where something looks wrong.
                    body.data.items.forEach((item, index) => addItem(item, index === 0, body.data.link_status))

                    if (!rows().length) {
                        addItem()
                    }

                    showMessage('success', body.message, body.data.warnings)
                    fileInput.value = ''
                } catch (error) {
                    showMessage('danger', error.message)
                } finally {
                    importButton.disabled = false
                }
            })
        })()
    </script>
@endsection
