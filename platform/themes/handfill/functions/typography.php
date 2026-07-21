<?php

use Botble\Theme\Facades\Theme;
use Botble\Theme\Typography\TypographyItem;

/*
 * Shofy registers the "primary" font family defaulting to Roboto. Handfill's
 * design uses Be Vietnam Pro, so re-register the same key with a new default —
 * font families are keyed by name, and this file is autoloaded after the
 * inherited theme's. Botble self-hosts the font, so no external request is made
 * and the admin can still override it under Theme options → Typography.
 */
app()->booted(function (): void {
    Theme::typography()->registerFontFamily(
        new TypographyItem('primary', __('Primary'), theme_option('primary_font', 'Be Vietnam Pro'))
    );
});
