@php
    // Kept in a variable: Blade's directive parser chokes on a multi-line @json([...]).
    $passwordStrengthLabels = [
        1 => __('Weak'),
        2 => __('Fair'),
        3 => __('Good'),
        4 => __('Strong'),
    ];
@endphp

<div class="hf-password-strength" data-score="0" data-hf-password-strength="#password">
    <div class="hf-password-strength__bars">
        @for ($i = 0; $i < 4; $i++)
            <span class="hf-password-strength__bar"></span>
        @endfor
    </div>
    <span class="hf-password-strength__label"></span>
</div>

@push('footer')
    <script>
        window.handfillPasswordStrengthLabels = {!! json_encode($passwordStrengthLabels) !!};
    </script>
@endpush
