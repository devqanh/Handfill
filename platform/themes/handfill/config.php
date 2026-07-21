<?php

use Botble\Theme\Theme;
use Illuminate\View\View;

return [
    /*
     * Handfill inherits everything from Shofy: layouts, partials, shortcodes, widgets,
     * Bootstrap 5.3.3, the compiled theme.css/theme.js and every ecommerce view.
     * Only the files that actually differ live in this folder — anything not found here
     * falls back to platform/themes/shofy automatically.
     */
    'inherit' => 'shofy',

    'events' => [
        'beforeRenderTheme' => function (Theme $theme): void {
            $version = '1.0.0';

            // Loaded after 'theme' (Shofy) so Handfill tokens win.
            $theme->asset()->usePath()->add('handfill', 'css/handfill.css', ['theme'], version: $version);

            $theme->asset()
                ->container('footer')
                ->usePath()
                ->add('handfill', 'js/handfill.js', ['theme'], attributes: ['defer'], version: $version);

            $theme->partialComposer('header.*', function (View $view): void {
                $headerTopBackgroundColor = theme_option('header_top_background_color', '#0b1120');
                $headerTopTextColor = theme_option('header_top_text_color', '#fff');
                $headerMainBackgroundColor = theme_option('header_main_background_color', '#fff');
                $headerMainTextColor = theme_option('header_main_text_color', '#0f172a');

                $view->with(compact(
                    'headerTopBackgroundColor',
                    'headerTopTextColor',
                    'headerMainBackgroundColor',
                    'headerMainTextColor'
                ));
            });
        },
    ],
];
