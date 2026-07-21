@php
    use Botble\Base\Facades\Html;

    Theme::layout('without-layout');
    Theme::set('pageTitle', __('Register'));

    $subheading = __('Already have an account? :link', [
        'link' => Html::link(route('customer.login'), __('Login'))->toHtml(),
    ]);
@endphp

{!! $form
    ->template(Theme::getThemeNamespace('views.ecommerce.customers.forms.auth'))
    ->setFormOption('authPanel', 'register')
    ->setFormOption('authHeading', __('Create an account'))
    ->setFormOption('authSubheading', $subheading)
    ->renderForm() !!}
