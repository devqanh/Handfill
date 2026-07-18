@php
    use Botble\HandmadeWorkflow\Enums\ProductionStatusEnum;

    $flow = ProductionStatusEnum::flow();
    $totalSteps = count($flow);
    $doneCount = $isCanceled ? 0 : (int) $currentIndex + 1;
    $percent = $isCanceled ? 0 : (int) round($doneCount / $totalSteps * 100);
@endphp

<div class="bb-customer-card-list account-settings-cards mb-3">
    <div class="bb-customer-card hw-progress">
        <div class="bb-customer-card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                        <x-core::icon name="ti ti-progress-check" class="text-primary" />
                    </div>
                    <div>
                        <h3 class="bb-customer-card-title h5 mb-1">
                            {{ trans('plugins/handmade-workflow::handmade-workflow.production_progress') }}
                        </h3>
                        <p class="text-muted small mb-0">
                            @if ($isCanceled)
                                {{ trans('plugins/handmade-workflow::handmade-workflow.statuses.canceled') }}
                            @else
                                {{ ProductionStatusEnum::of($current)->label() }}
                            @endif
                        </p>
                    </div>
                </div>

                @unless ($isCanceled)
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                        {{ trans('plugins/handmade-workflow::handmade-workflow.step_of', [
                            'current' => $doneCount,
                            'total' => $totalSteps,
                        ]) }}
                    </span>
                @endunless
            </div>

            @unless ($isCanceled)
                <div class="progress hw-progress-bar mt-3" role="progressbar"
                    aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-primary" style="width: {{ $percent }}%"></div>
                </div>
            @endunless
        </div>

        <div class="bb-customer-card-body">
            @if ($isCanceled)
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-0">
                    <x-core::icon name="ti ti-circle-x" />
                    <span>{{ trans('plugins/handmade-workflow::handmade-workflow.order_canceled_note') }}</span>
                </div>
            @else
                <ol class="hw-timeline">
                    @foreach ($flow as $index => $status)
                        @php
                            $enum = ProductionStatusEnum::of($status);
                            $isDone = $index < $currentIndex;
                            $isCurrent = $index === $currentIndex;
                            $time = $stepTimes[$status] ?? null;
                        @endphp

                        <li @class([
                            'hw-step',
                            'is-done' => $isDone,
                            'is-current' => $isCurrent,
                        ])>
                            <span class="hw-step-marker">
                                <x-core::icon :name="$isDone ? 'ti ti-check' : $enum->getIcon()" />
                            </span>

                            <div class="hw-step-body">
                                <span class="hw-step-label">{{ $enum->label() }}</span>

                                @if ($time)
                                    <time class="hw-step-time" datetime="{{ $time->toIso8601String() }}">
                                        {{ $time->format('d/m/Y H:i') }}
                                    </time>
                                @elseif ($isCurrent)
                                    <span class="hw-step-time">
                                        {{ trans('plugins/handmade-workflow::handmade-workflow.in_progress') }}
                                    </span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</div>

<style>
    .hw-progress-bar { height: 6px; border-radius: 999px; background: #eef0f3; }

    .hw-timeline { list-style: none; margin: 0; padding: 0; }

    .hw-step {
        position: relative;
        display: flex;
        gap: 1rem;
        padding-bottom: 1.5rem;
    }

    .hw-step:last-child { padding-bottom: 0; }

    /* The connector is drawn from each marker down to the next one. */
    .hw-step:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 17px;
        top: 36px;
        bottom: 2px;
        width: 2px;
        background: #e5e7eb;
    }

    .hw-step.is-done:not(:last-child)::before { background: var(--bs-success, #16a34a); }

    .hw-step-marker {
        position: relative;
        z-index: 1;
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #9ca3af;
        border: 2px solid #e5e7eb;
    }

    .hw-step-marker svg { width: 18px; height: 18px; }

    .hw-step.is-done .hw-step-marker {
        background: var(--bs-success, #16a34a);
        border-color: var(--bs-success, #16a34a);
        color: #fff;
    }

    .hw-step.is-current .hw-step-marker {
        background: var(--bs-primary, #0d6efd);
        border-color: var(--bs-primary, #0d6efd);
        color: #fff;
        box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb, 13, 110, 253), .18);
    }

    .hw-step-body { padding-top: .35rem; min-width: 0; }

    .hw-step-label { display: block; color: #6b7280; }

    .hw-step.is-done .hw-step-label { color: #111827; }

    .hw-step.is-current .hw-step-label { color: #111827; font-weight: 600; }

    .hw-step-time {
        display: block;
        margin-top: .15rem;
        font-size: .8125rem;
        color: #9ca3af;
    }

    .hw-step.is-current .hw-step-time { color: var(--bs-primary, #0d6efd); }

    @media (max-width: 575.98px) {
        .hw-step { gap: .75rem; padding-bottom: 1.25rem; }
        .hw-step:not(:last-child)::before { left: 15px; top: 32px; }
        .hw-step-marker { width: 32px; height: 32px; }
    }
</style>
