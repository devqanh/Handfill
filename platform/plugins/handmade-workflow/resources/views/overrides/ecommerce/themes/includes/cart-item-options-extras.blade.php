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
    @if (! empty($handmade['note']))
        <p class="mb-1">
            <small class="text-muted">{{ $handmade['note'] }}</small>
        </p>
    @endif

    @if (! empty($handmade['images']))
        <div class="d-flex flex-wrap gap-2 mt-1">
            @foreach ($handmade['images'] as $image)
                <a
                    href="{{ RvMedia::getImageUrl($image) }}"
                    class="hw-photo"
                    data-hw-photo="{{ RvMedia::getImageUrl($image) }}"
                    title="{{ trans('plugins/handmade-workflow::handmade-workflow.view_photo') }}"
                >
                    {{ RvMedia::image($image, '', attributes: [
                        'class' => 'rounded border',
                        'style' => 'width:64px;height:64px;object-fit:cover;cursor:zoom-in',
                    ]) }}
                </a>
            @endforeach
        </div>
    @endif
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
        document.addEventListener('click', function (event) {
            const link = event.target.closest('.hw-photo')
            if (!link) return

            event.preventDefault()

            document.getElementById('hw-photo-modal-image').src = link.dataset.hwPhoto
            bootstrap.Modal.getOrCreateInstance(document.getElementById('hw-photo-modal')).show()
        })
    </script>
@endonce
