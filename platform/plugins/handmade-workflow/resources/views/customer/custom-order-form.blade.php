@extends(EcommerceHelper::viewPath('customers.master'))

@section('title', trans('plugins/handmade-workflow::handmade-workflow.custom_order.title'))

@section('content')
    {{-- Card shell copied from edit-account: the theme only styles .bb-customer-card
         when it sits inside .bb-customer-card-list.account-settings-cards. --}}
    <div class="bb-customer-card-list account-settings-cards d-flex flex-column gap-3">
        <div class="bb-customer-card">
            <div class="bb-customer-card-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <x-core::icon name="ti ti-brush" class="text-primary" />
                    </div>
                    <div>
                        <h3 class="bb-customer-card-title h5 mb-1">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.custom_order.title') }}
                        </h3>
                        <p class="text-muted small mb-0">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.custom_order.description') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <x-core::form
            :url="route('customer.custom-orders.store')"
            method="POST"
            :files="true"
            id="handmade-custom-order-form"
            class="d-flex flex-column gap-3"
        >
            <div class="bb-customer-card">
                <div class="bb-customer-card-header">
                    <h3 class="bb-customer-card-title h5 mb-0">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.general_info') }}
                    </h3>
                </div>
                <div class="bb-customer-card-body">
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
                                <span class="text-danger">*</span>
                            </label>

                            @if ($addresses->isEmpty())
                                <div class="alert alert-warning mb-0">
                                    <p class="mb-2">{{ trans('plugins/handmade-workflow::handmade-workflow.no_address') }}</p>
                                    <a href="{{ route('customer.address.create') }}" class="btn btn-sm btn-warning">
                                        <x-core::icon name="ti ti-plus" class="me-1" />
                                        {{ trans('plugins/handmade-workflow::handmade-workflow.add_address') }}
                                    </a>
                                </div>
                            @else
                                <select name="address_id" id="address_id" class="form-select" required>
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
                            <textarea name="note" id="note" class="form-control" rows="3"
                                placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.general_note_placeholder') }}">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div id="handmade-items" class="d-flex flex-column gap-3"></div>

            <div class="bb-customer-card">
                <div class="bb-customer-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary" id="handmade-add-item">
                        <x-core::icon name="ti ti-plus" class="me-1" />
                        {{ trans('plugins/handmade-workflow::handmade-workflow.add_item') }}
                    </button>
                    <small class="text-muted">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.max_items_hint', ['max' => $maxItems]) }}
                    </small>
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-primary">
                    <x-core::icon name="ti ti-send" class="me-1" />
                    {{ trans('plugins/handmade-workflow::handmade-workflow.custom_order.submit') }}
                </button>
            </div>
        </x-core::form>
    </div>

    <template id="handmade-item-template">
        <div class="bb-customer-card handmade-item">
            <div class="bb-customer-card-header d-flex justify-content-between align-items-center">
                <h3 class="bb-customer-card-title h6 mb-0">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.item') }}
                    <span class="handmade-item-index"></span>
                </h3>
                <button type="button" class="btn btn-sm btn-outline-danger handmade-remove-item"
                    title="{{ trans('plugins/handmade-workflow::handmade-workflow.remove_item') }}">
                    <x-core::icon name="ti ti-trash" />
                </button>
            </div>
            <div class="bb-customer-card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.item_name') }}
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" data-name="name" required
                            placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.item_name_placeholder') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.item_qty') }}
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" data-name="qty" value="1" min="1" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.item_images') }}
                            <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control handmade-item-images" data-name="images"
                            accept="image/jpeg,image/png,image/webp" multiple required>
                        <small class="text-muted">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.item_images_help', ['max' => $maxImages]) }}
                        </small>
                        <div class="handmade-item-preview d-flex flex-wrap gap-2 mt-2"></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.item_note') }}
                        </label>
                        <textarea class="form-control" rows="2" data-name="note"
                            placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.item_note_placeholder') }}"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </template>
    {{-- Inline on purpose: the theme layout has no @stack('footer'), so a @push would be dropped. --}}
    <script>
        (function () {
            const wrapper = document.getElementById('handmade-items')
            const template = document.getElementById('handmade-item-template')
            const addButton = document.getElementById('handmade-add-item')
            const maxItems = {{ (int) $maxItems }}
            const maxImages = {{ (int) $maxImages }}

            // Field names carry the row index, so they are rewritten whenever rows change.
            function reindex() {
                wrapper.querySelectorAll('.handmade-item').forEach((item, index) => {
                    item.querySelector('.handmade-item-index').textContent = index + 1

                    item.querySelectorAll('[data-name]').forEach((field) => {
                        const key = field.dataset.name
                        field.name = key === 'images'
                            ? `items[${index}][images][]`
                            : `items[${index}][${key}]`
                    })
                })

                addButton.disabled = wrapper.querySelectorAll('.handmade-item').length >= maxItems
            }

            function addItem() {
                if (wrapper.querySelectorAll('.handmade-item').length >= maxItems) {
                    return
                }

                wrapper.appendChild(template.content.cloneNode(true))
                reindex()
            }

            wrapper.addEventListener('click', (event) => {
                if (!event.target.closest('.handmade-remove-item')) {
                    return
                }

                const items = wrapper.querySelectorAll('.handmade-item')

                // Always keep at least one row so the form stays submittable.
                if (items.length <= 1) {
                    return
                }

                event.target.closest('.handmade-item').remove()
                reindex()
            })

            wrapper.addEventListener('change', (event) => {
                const input = event.target
                if (!input.classList.contains('handmade-item-images')) {
                    return
                }

                const preview = input.closest('.handmade-item').querySelector('.handmade-item-preview')
                preview.innerHTML = ''

                if (input.files.length > maxImages) {
                    input.value = ''
                    preview.innerHTML = `<span class="text-danger small">{{ trans('plugins/handmade-workflow::handmade-workflow.too_many_images', ['max' => '__MAX__']) }}</span>`.replace('__MAX__', maxImages)
                    return
                }

                Array.from(input.files).forEach((file) => {
                    const img = document.createElement('img')
                    img.src = URL.createObjectURL(file)
                    img.className = 'rounded border'
                    img.style.cssText = 'width:72px;height:72px;object-fit:cover'
                    img.onload = () => URL.revokeObjectURL(img.src)
                    preview.appendChild(img)
                })
            })

            addButton.addEventListener('click', addItem)

            addItem()
        })()
    </script>
@endsection
