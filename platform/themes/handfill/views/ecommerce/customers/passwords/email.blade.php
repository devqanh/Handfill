@php
    use Botble\Base\Facades\Html;

    Theme::layout('without-layout');
    Theme::set('pageTitle', __('Forgot Password'));

    $subheading = __('Remembered it? :link', [
        'link' => Html::link(route('customer.login'), __('Back to login'))->toHtml(),
    ]);
@endphp

{!! $form
    ->template(Theme::getThemeNamespace('views.ecommerce.customers.forms.auth'))
    ->setFormOption('authPanel', 'password')
    ->setFormOption('authHeading', __('Forgot password'))
    ->setFormOption('authSubheading', $subheading)
    ->renderForm() !!}
