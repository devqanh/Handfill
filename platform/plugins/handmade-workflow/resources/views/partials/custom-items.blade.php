@php
    use Botble\HandmadeWorkflow\Enums\CustomerGroupEnum;
    use Botble\HandmadeWorkflow\Services\CustomOrderService;

    $group = $order->getOrderMetadata(CustomOrderService::META_CUSTOMER_GROUP);
    $expectedDate = $order->getOrderMetadata(CustomOrderService::META_EXPECTED_DATE);
@endphp

<div class="card border-0 mb-4">
    <div class="card-body">
        <h5 class="card-title h6 mb-3">
            <x-core::icon name="ti ti-brush" class="me-1" />
            {{ trans('plugins/handmade-workflow::handmade-workflow.custom_order.heading') }}
        </h5>

        <div class="mb-3">
            @if ($group)
                <span class="me-3">
                    <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.customer_group') }}:</span>
                    {!! CustomerGroupEnum::of($group)->toHtml() !!}
                </span>
            @endif

            @if ($expectedDate)
                <span>
                    <span class="text-muted">{{ trans('plugins/handmade-workflow::handmade-workflow.expected_date') }}:</span>
                    <strong>{{ \Illuminate\Support\Carbon::parse($expectedDate)->format('d/m/Y') }}</strong>
                </span>
            @endif
        </div>

        @foreach ($items as $index => $item)
            <div @class(['pt-3 mt-3 border-top' => $index > 0])>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <strong>{{ $item['name'] }}</strong>
                    <span class="text-muted">x{{ $item['qty'] }}</span>
                </div>

                @if ($item['note'])
                    <p class="text-muted small mb-2">{{ $item['note'] }}</p>
                @endif

                <div class="d-flex flex-wrap gap-2">
                    @foreach ($item['images'] as $image)
                        <a href="{{ RvMedia::getImageUrl($image) }}" target="_blank" rel="noopener">
                            {{ RvMedia::image($image, $item['name'], attributes: [
                                'class' => 'rounded border',
                                'style' => 'width:88px;height:88px;object-fit:cover',
                            ]) }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
