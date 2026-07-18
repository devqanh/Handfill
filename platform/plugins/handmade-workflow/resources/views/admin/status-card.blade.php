@php
    use Botble\HandmadeWorkflow\Enums\ProductionStatusEnum;
    use Botble\HandmadeWorkflow\Services\MilestonePaymentService;

    $currentEnum = ProductionStatusEnum::of($current);
    $currentIndex = ProductionStatusEnum::stepIndex($current);
    $flow = ProductionStatusEnum::flow();
    $totalSteps = count($flow);

    // The two paid steps are entered by the customer, never by staff.
    $customerDrivenNext = [
        ProductionStatusEnum::PENDING_APPROVAL => ProductionStatusEnum::DEPOSITED,
        ProductionStatusEnum::AWAITING_CONFIRMATION => ProductionStatusEnum::CONFIRMED,
    ];
    $waitingOnCustomer = array_key_exists($current, $customerDrivenNext);

    // Staff may still cancel while waiting on the customer.
    $staffOptions = array_values(array_filter(
        $allowed,
        fn (string $status) => ! $waitingOnCustomer || $status === ProductionStatusEnum::CANCELED
    ));
@endphp

<x-core::card class="mt-3">
    <x-core::card.header>
        <x-core::card.title>
            <x-core::icon name="ti ti-progress-check" />
            {{ trans('plugins/handmade-workflow::handmade-workflow.production_status') }}
        </x-core::card.title>
    </x-core::card.header>

    <x-core::card.body>
        <div class="mb-2">
            {!! $currentEnum->toHtml() !!}
            @if ($currentIndex !== null)
                <span class="text-muted ms-2">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.step_of', [
                        'current' => $currentIndex + 1,
                        'total' => $totalSteps,
                    ]) }}
                </span>
            @endif
        </div>

        @if ($order->production_status_updated_at)
            <p class="text-muted small mb-3">
                {{ trans('plugins/handmade-workflow::handmade-workflow.updated_at') }}:
                {{ \Illuminate\Support\Carbon::parse($order->production_status_updated_at)->format('d/m/Y H:i') }}
            </p>
        @endif

        {{-- What to do right now --}}
        <div class="alert alert-info py-2 px-3">
            <div class="fw-semibold mb-1">
                {{ trans('plugins/handmade-workflow::handmade-workflow.next_action') }}
            </div>
            <div class="small">
                {{ trans('plugins/handmade-workflow::handmade-workflow.guides.' . $current) }}
            </div>
        </div>

        @if ($waitingOnCustomer)
            <div class="alert alert-warning py-2 px-3 small">
                <x-core::icon name="ti ti-hourglass" class="me-1" />
                {{ trans('plugins/handmade-workflow::handmade-workflow.waiting_customer') }}
            </div>
        @endif

        {{-- Full flow so staff can see where this order sits --}}
        <details class="mb-3">
            <summary class="text-muted small" style="cursor: pointer;">
                {{ trans('plugins/handmade-workflow::handmade-workflow.flow_guide') }}
            </summary>
            <ol class="list-unstyled mt-2 mb-0 small">
                @foreach ($flow as $index => $status)
                    @php
                        $stepEnum = ProductionStatusEnum::of($status);
                        $isDone = $currentIndex !== null && $index < $currentIndex;
                        $isCurrent = $index === $currentIndex;
                        $byCustomer = in_array($status, [
                            ProductionStatusEnum::DEPOSITED,
                            ProductionStatusEnum::CONFIRMED,
                        ], true);
                    @endphp
                    <li @class(['py-1 d-flex gap-2', 'fw-semibold' => $isCurrent, 'text-muted' => ! $isDone && ! $isCurrent])>
                        <span style="width: 1.25rem;">
                            @if ($isDone)
                                <x-core::icon name="ti ti-circle-check" class="text-success" />
                            @elseif ($isCurrent)
                                <x-core::icon name="ti ti-arrow-right" class="text-primary" />
                            @else
                                <x-core::icon name="ti ti-circle" />
                            @endif
                        </span>
                        <span>
                            {{ $index + 1 }}. {{ $stepEnum->label() }}
                            <span class="badge bg-{{ $byCustomer ? 'warning' : 'secondary' }}-subtle text-{{ $byCustomer ? 'warning' : 'secondary' }} ms-1">
                                {{ $byCustomer
                                    ? trans('plugins/handmade-workflow::handmade-workflow.actors.customer')
                                    : trans('plugins/handmade-workflow::handmade-workflow.actors.staff') }}
                            </span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </details>

        @if (empty($staffOptions))
            <p class="text-muted mb-0">
                {{ trans('plugins/handmade-workflow::handmade-workflow.no_further_steps') }}
            </p>
        @else
            <x-core::form
                :url="route('handmade-workflow.update-status', $order->getKey())"
                method="POST"
                class="mb-0"
            >
                <div class="mb-2">
                    <label class="form-label" for="production_status">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.move_to') }}
                    </label>
                    <select name="production_status" id="production_status" class="form-select">
                        @foreach ($staffOptions as $status)
                            <option value="{{ $status }}">{{ ProductionStatusEnum::of($status)->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label" for="production_note">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.note') }}
                    </label>
                    <textarea name="note" id="production_note" class="form-control" rows="2"
                        placeholder="{{ trans('plugins/handmade-workflow::handmade-workflow.note_placeholder') }}"></textarea>
                </div>

                <x-core::button type="submit" color="primary" icon="ti ti-arrow-right">
                    {{ trans('plugins/handmade-workflow::handmade-workflow.update_status') }}
                </x-core::button>
            </x-core::form>
        @endif
    </x-core::card.body>
</x-core::card>
