@php
    /**
     * Handfill split-screen auth template.
     *
     * Replaces plugins/ecommerce::customers.forms.auth — the fields, validation and
     * every plugin filter are still Botble's, only the chrome around them is ours.
     * Applied by views/ecommerce/customers/{login,register}.blade.php via
     * $form->template(...).
     */
    $panel = Arr::get($formOptions, 'authPanel', 'login');
    $heading = Arr::get($formOptions, 'authHeading');
    $subheading = Arr::get($formOptions, 'authSubheading');

    // Options consumed by this template (or by the one it replaces) must never
    // reach Form::open(), or they'd be printed as <form> attributes.
    $openOptions = Arr::except($formOptions, [
        'template',
        'banner',
        'bannerDirection',
        'icon',
        'heading',
        'description',
        'has_wrapper',
        'authPanel',
        'authHeading',
        'authSubheading',
    ]);
@endphp

<div class="hf-auth">
    @include(Theme::getThemeNamespace('partials.auth.brand-panel'), ['panel' => $panel])

    <main class="hf-auth__panel">
        <a class="hf-auth__mobile-logo" href="{{ route('public.index') }}">
            @include(Theme::getThemeNamespace('partials.auth.logo'))
        </a>

        <div class="hf-auth__form">
            @if ($heading)
                <h1 class="hf-auth__heading">{{ $heading }}</h1>
            @endif

            @if ($subheading)
                <p class="hf-auth__subheading">{!! $subheading !!}</p>
            @endif

            @if (session()->has('status'))
                <div role="alert" class="alert alert-success">{{ session('status') }}</div>
            @elseif (session()->has('auth_error_message'))
                <div role="alert" class="alert alert-danger">{{ session('auth_error_message') }}</div>
            @elseif (session()->has('auth_success_message'))
                <div role="alert" class="alert alert-success">{{ session('auth_success_message') }}</div>
            @elseif (session()->has('auth_warning_message'))
                <div role="alert" class="alert alert-warning">{{ session('auth_warning_message') }}</div>
            @endif

            @if ($showStart)
                {!! Form::open($openOptions) !!}
            @endif

            @if ($showFields)
                {{ $form->getOpenWrapperFormColumns() }}

                @foreach ($fields as $field)
                    @continue(in_array($field->getName(), $exclude))

                    @php
                        $name = $field->getName();
                        $rendered = $field->render();
                        $hasContent = trim(strip_tags($rendered)) !== ''
                            || (bool) preg_match('/<(a|button|img|svg|iframe|input|select|textarea)\b/i', $rendered);
                    @endphp

                    {{-- The social-login buttons arrive through a filter and are often empty. --}}
                    @if ($name === 'filters')
                        @if ($hasContent)
                            <div class="hf-auth__divider">
                                <span>{{ $panel === 'register' ? __('or sign up with') : __('or continue with') }}</span>
                            </div>

                            {!! $rendered !!}
                        @endif

                        @continue
                    @endif

                    {!! $rendered !!}

                    @if ($name === 'password' && $panel === 'register')
                        @include(Theme::getThemeNamespace('partials.auth.password-strength'))
                    @endif

                    @if ($name === 'password_confirmation')
                        <p
                            class="invalid-feedback"
                            hidden
                            data-hf-password-confirm="#password"
                            data-hf-password-confirm-target="#password_confirmation"
                        >{{ __('Passwords do not match') }}</p>
                    @endif
                @endforeach

                {{ $form->getCloseWrapperFormColumns() }}
            @endif

            @if ($showEnd)
                {!! Form::close() !!}
            @endif

            @if ($form->getValidatorClass())
                @push('footer')
                    {!! $form->renderValidatorJs() !!}
                @endpush
            @endif
        </div>
    </main>
</div>
