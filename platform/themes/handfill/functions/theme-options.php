<?php

use Botble\Theme\Events\RenderingThemeOptionSettings;
use Botble\Theme\Facades\ThemeOption;
use Botble\Theme\ThemeOption\Fields\MediaImageField;
use Botble\Theme\ThemeOption\Fields\TextareaField;
use Botble\Theme\ThemeOption\Fields\TextField;

/*
 * Content of the dark brand panel shown next to the login / register forms.
 * Runs after Shofy's theme-options.php (inherited themes are autoloaded first),
 * so this only *adds* a section — nothing from Shofy is redefined.
 */
app('events')->listen(RenderingThemeOptionSettings::class, function (): void {
    ThemeOption::getFacadeRoot()->setSection([
        'title' => __('Login & Register'),
        'id' => 'opt-text-subsection-auth',
        'subsection' => true,
        'icon' => 'ti ti-login',
        'fields' => [
            MediaImageField::make()
                ->name('auth_logo')
                ->label(__('Logo on auth pages'))
                ->helperText(__('Falls back to the site logo when empty.')),
            TextField::make()
                ->name('auth_login_title')
                ->label(__('Login panel title'))
                ->helperText(__('Wrap a part in [accent]…[/accent] to colour it with the brand green.'))
                ->defaultValue('Chào mừng trở lại,<br>[accent]đối tác Handfill![/accent]'),
            TextareaField::make()
                ->name('auth_login_description')
                ->label(__('Login panel description'))
                ->defaultValue('Đăng nhập để quản lý đơn hàng, theo dõi sản xuất và khám phá sản phẩm mới nhất từ xưởng thêu tay Hà Nội.'),
            TextField::make()
                ->name('auth_register_title')
                ->label(__('Register panel title'))
                ->defaultValue('Bắt đầu bán hàng<br>[accent]thêu tay toàn cầu[/accent]<br>ngay hôm nay.'),
            TextareaField::make()
                ->name('auth_register_description')
                ->label(__('Register panel description'))
                ->defaultValue('Tạo tài khoản miễn phí và truy cập hệ thống sản xuất theo yêu cầu (HOD) của Handfill — không tồn kho, không rủi ro.'),
            TextareaField::make()
                ->name('auth_testimonial_content')
                ->label(__('Testimonial'))
                ->defaultValue('Handfill giúp tôi scale từ 50 lên 500 đơn/tháng mà không cần thuê thêm nhân sự. Hệ thống tracking minh bạch, chất lượng ổn định.'),
            TextField::make()
                ->name('auth_testimonial_name')
                ->label(__('Testimonial author'))
                ->defaultValue('Minh Tú'),
            TextField::make()
                ->name('auth_testimonial_role')
                ->label(__('Testimonial author role'))
                ->defaultValue('Etsy Seller · 500+ đơn/tháng'),
        ],
    ]);
});
