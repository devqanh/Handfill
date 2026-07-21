@php
    /**
     * Dark brand panel shown beside the auth forms.
     *
     * @var string $panel  'login' | 'register' | 'password'
     */
    $panel = $panel ?? 'login';

    $title = $panel === 'register'
        ? (theme_option('auth_register_title') ?: __('Start selling<br>[accent]hand embroidery worldwide[/accent]<br>today.'))
        : (theme_option('auth_login_title') ?: __('Welcome back,<br>[accent]Handfill partner![/accent]'));

    $description = $panel === 'register'
        ? (theme_option('auth_register_description') ?: __('Create a free account and get access to Handfill\'s made-to-order (HOD) system — no stock, no risk.'))
        : (theme_option('auth_login_description') ?: __('Sign in to manage your orders, follow production and discover the latest products from our Hanoi hand-embroidery workshop.'));

    // Only <br> and [accent]…[/accent] are meaningful; everything else is escaped.
    $renderTitle = fn (string $value) => str_replace(
        ['&lt;br&gt;', '&lt;br /&gt;', '&lt;br/&gt;', '[accent]', '[/accent]'],
        ['<br>', '<br>', '<br>', '<span class="hf-accent">', '</span>'],
        e($value)
    );

    $perks = $panel === 'register'
        ? [
            ['ti ti-diamond', __('Access to the exclusive hand-embroidery catalogue')],
            ['ti ti-bolt', __('Instant quotes, production from a single piece')],
            ['ti ti-world', __('Global fulfillment to 50+ countries')],
            ['ti ti-shield-check', __('Two-layer QC, 100% refund on production faults')],
        ]
        : [
            ['ti ti-diamond', __('Exclusive hand-embroidery catalogue, updated continuously')],
            ['ti ti-bolt', __('Made-to-order with no stock, from a single piece')],
            ['ti ti-world', __('Shipping to 50+ countries with transparent tracking')],
            ['ti ti-shield-check', __('100% refund on production faults')],
        ];

    $stats = $panel === 'login'
        ? [
            ['12K+', __('Sellers')],
            ['26K+', __('Orders')],
            ['50+', __('Countries')],
            ['4.9★', __('Rating')],
        ]
        : [];

    $testimonial = theme_option('auth_testimonial_content')
        ?: __('Handfill helped me scale from 50 to 500 orders a month without hiring anyone. Transparent tracking, consistent quality.');
    $testimonialName = theme_option('auth_testimonial_name') ?: 'Minh Tú';
    $testimonialRole = theme_option('auth_testimonial_role') ?: 'Etsy Seller · 500+ đơn/tháng';
@endphp

<aside class="hf-auth__brand">
    <span class="hf-auth__glow hf-auth__glow--top" aria-hidden="true"></span>
    <span class="hf-auth__glow hf-auth__glow--bottom" aria-hidden="true"></span>

    <div class="hf-auth__brand-top">
        <a class="hf-auth__brand-logo" href="{{ route('public.index') }}">
            @include(Theme::getThemeNamespace('partials.auth.logo'))
        </a>

        <h2 class="hf-auth__brand-title">{!! $renderTitle($title) !!}</h2>

        <p class="hf-auth__brand-lead">{{ $description }}</p>

        <ul class="hf-auth__perks">
            @foreach ($perks as [$icon, $text])
                <li class="hf-auth__perk">
                    <span class="hf-auth__perk-icon"><x-core::icon :name="$icon" /></span>
                    <span>{{ $text }}</span>
                </li>
            @endforeach
        </ul>

        @if ($stats)
            <div class="hf-auth__stats">
                @foreach ($stats as [$value, $label])
                    <div class="hf-auth__stat">
                        <p class="hf-auth__stat-value">{{ $value }}</p>
                        <p class="hf-auth__stat-label">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <figure class="hf-auth__quote">
        <blockquote>&ldquo;{{ $testimonial }}&rdquo;</blockquote>
        <figcaption class="hf-auth__quote-author">
            <span class="hf-auth__avatar">{{ mb_substr($testimonialName, 0, 1) }}</span>
            <span>
                <span class="hf-auth__quote-name">{{ $testimonialName }}</span>
                <span class="hf-auth__quote-role">{{ $testimonialRole }}</span>
            </span>
        </figcaption>
    </figure>
</aside>
