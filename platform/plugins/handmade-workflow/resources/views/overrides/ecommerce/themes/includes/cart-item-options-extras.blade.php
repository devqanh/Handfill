{{--
    COPY of plugins/ecommerce::themes.includes.cart-item-options-extras, overridden by
    handmade-workflow (View::prependNamespace) to show the customer's own reference
    photos and note under the line they belong to, instead of in a separate card.

    The original block is kept verbatim; only the handmade part is appended.
--}}
@if (($extras = Arr::get($options, 'extras', [])) && is_array($extras))
    @foreach ($extras as $extra)
        @if (!empty($extra['key']) && !empty($extra['value']))
            <p class="mb-0">
                <small>{{ $extra['key'] }}: <strong> {{ $extra['value'] }}</strong></small>
            </p>
        @endif
    @endforeach
@endif

@php $handmade = Arr::get($options, 'handmade'); @endphp

@if ($handmade && ! empty($handmade['is_custom']))
    {{-- The marketplace fields a fulfilment order is matched by. Rendered as one
         inline list so a long order does not turn into a wall of labels. --}}
    @php
        $hwFacts = array_filter([
            trans('plugins/handmade-workflow::handmade-workflow.item_sku') => $handmade['sku'] ?? null,
            trans('plugins/handmade-workflow::handmade-workflow.item_marketplace_order_id') => $handmade['marketplace_order_id'] ?? null,
            trans('plugins/handmade-workflow::handmade-workflow.item_ordered_at') => ($handmade['ordered_at'] ?? null)
                ? BaseHelper::formatDate($handmade['ordered_at'])
                : null,
        ]);
    @endphp

    @if ($hwFacts)
        <p class="mb-1 small">
            @foreach ($hwFacts as $label => $value)
                <span class="me-3 text-nowrap">{{ $label }}: <strong>{{ $value }}</strong></span>
            @endforeach
        </p>
    @endif

    @if (! empty($handmade['note']))
        {{-- Personalisation arrives from the sheet with real line breaks in it. --}}
        <p class="mb-1">
            <small class="text-muted" style="white-space:pre-line">{{ $handmade['note'] }}</small>
        </p>
    @endif

    @php $hwRecipient = array_filter($handmade['recipient'] ?? []); @endphp

    @if ($hwRecipient)
        <div class="mb-1 small">
            <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.item_recipient') }}:</span>
            <strong>{{ $hwRecipient['name'] ?? '' }}</strong>
            @if (! empty($hwRecipient['email']))
                <span class="text-muted">({{ $hwRecipient['email'] }})</span>
            @endif
            @if (! empty($hwRecipient['address']))
                <span class="d-block text-muted" style="white-space:pre-line">{{ $hwRecipient['address'] }}</span>
            @endif
        </div>
    @endif

    @php
        // Uploaded files and the customer's own links are shown side by side — from
        // here on there is no difference, both are just a picture to work from.
        // Photo 1 is already drawn as the line thumbnail, so that one is skipped.
        $hwGalleries = array_filter([
            'product' => array_merge(
                array_slice($handmade['images'] ?? [], 1),
                array_slice($handmade['image_links'] ?? [], empty($handmade['images']) ? 1 : 0)
            ),
            'fabric' => $handmade['fabric_links'] ?? [],
        ]);
    @endphp

    @foreach ($hwGalleries as $hwKind => $hwImages)
        <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
            @if ($hwKind === 'fabric')
                <small class="text-muted">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.item_fabric_images') }}:
                </small>
            @endif
            @foreach ($hwImages as $image)
                @php $hwSrc = RvMedia::getImageUrl($image); @endphp
                <a
                    href="{{ $hwSrc }}"
                    class="hw-photo"
                    data-hw-photo="{{ $hwSrc }}"
                    target="_blank"
                    rel="noopener"
                    title="{{ $image }}"
                >
                    {{-- A stored link may point at a share page rather than a file, and
                         may have expired since. If the image will not load, fall back to
                         showing the address so staff can still open it by hand. --}}
                    <img
                        src="{{ $hwSrc }}"
                        alt=""
                        class="rounded border"
                        style="width:64px;height:64px;object-fit:cover;cursor:zoom-in"
                        onerror="this.replaceWith(Object.assign(document.createElement('span'), {
                            className: 'badge bg-light text-muted border text-truncate',
                            style: 'max-width:12rem',
                            textContent: this.closest('a').title.replace(/^https?:\/\//, ''),
                        }))"
                    >
                </a>
            @endforeach
        </div>
    @endforeach
@endif

{{-- One lightweight viewer shared by every gallery on the page. Registered once. --}}
@once
    <div class="modal fade" id="hw-photo-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ trans('plugins/handmade-workflow::handmade-workflow.view_photo') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" alt="" id="hw-photo-modal-image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const FOLDER = '/{{ \Botble\HandmadeWorkflow\Services\CustomOrderService::MEDIA_FOLDER }}/'

            function open(src) {
                document.getElementById('hw-photo-modal-image').src = src
                bootstrap.Modal.getOrCreateInstance(document.getElementById('hw-photo-modal')).show()
            }

            document.addEventListener('click', function (event) {
                const link = event.target.closest('.hw-photo')

                if (link) {
                    // If the thumbnail failed to load it was swapped for the address,
                    // so there is no photo to enlarge — let the link open normally.
                    if (!link.querySelector('img')) {
                        return
                    }

                    event.preventDefault()
                    open(link.dataset.hwPhoto)
                    return
                }

                // The line thumbnail is drawn by core, so it has no marker of its own —
                // recognise it by the folder customer photos are stored in.
                const img = event.target.closest('img')

                if (img && img.src.includes(FOLDER)) {
                    event.preventDefault()
                    // Thumbnails are resized copies; strip the -WxH suffix for the full one.
                    open(img.src.replace(/-\d+x\d+(\.[a-z]+)$/i, '$1'))
                }
            })

            // Hint that core thumbnails are clickable too.
            document.querySelectorAll('img[src*="' + FOLDER + '"]').forEach(function (img) {
                img.style.cursor = 'zoom-in'
            })
        })()
    </script>
@endonce
