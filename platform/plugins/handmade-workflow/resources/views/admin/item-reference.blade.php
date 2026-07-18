{{-- Reference photos + note shown inline with the order line they belong to. --}}
<div class="mt-2">
    @if ($note)
        <div class="text-muted small mb-2">
            <x-core::icon name="ti ti-message-2" class="me-1" />
            {{ $note }}
        </div>
    @endif

    @if ($images)
        <div class="d-flex flex-wrap gap-2">
            @foreach ($images as $image)
                <a href="{{ RvMedia::getImageUrl($image) }}" target="_blank" rel="noopener">
                    {{ RvMedia::image($image, '', attributes: [
                        'class' => 'rounded border',
                        'style' => 'width:56px;height:56px;object-fit:cover',
                    ]) }}
                </a>
            @endforeach
        </div>
    @endif
</div>
