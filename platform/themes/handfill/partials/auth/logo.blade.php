@php
    $authLogo = theme_option('auth_logo') ?: theme_option('logo');
@endphp

@if ($authLogo)
    {!! RvMedia::image($authLogo, theme_option('site_title', config('app.name')), attributes: ['loading' => false]) !!}
@else
    <img
        src="{{ Theme::asset()->url('images/logo.png') }}"
        alt="{{ theme_option('site_title', config('app.name')) }}"
        width="180"
        height="60"
    >
@endif
