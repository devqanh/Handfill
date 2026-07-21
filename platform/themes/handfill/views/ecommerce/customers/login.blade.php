@php
    use Botble\Base\Facades\Html;
    use Botble\Ecommerce\Facades\EcommerceHelper;

    Theme::layout('without-layout');
    Theme::set('pageTitle', __('Login'));

    $subheading = EcommerceHelper::isCustomerRegistrationEnabled()
        ? __("Don't have an account? :link", [
            'link' => Html::link(route('customer.register'), __('Sign up for free'))->toHtml(),
        ])
        : null;
@endphp

{!! $form
    ->template(Theme::getThemeNamespace('views.ecommerce.customers.forms.auth'))
    ->setFormOption('authPanel', 'login')
    ->setFormOption('authHeading', __('Login'))
    ->setFormOption('authSubheading', $subheading)
    ->renderForm() !!}
