@php
    Theme::layout('without-layout');
    Theme::set('pageTitle', __('Reset Password'));
@endphp

{!! $form
    ->template(Theme::getThemeNamespace('views.ecommerce.customers.forms.auth'))
    ->setFormOption('authPanel', 'password')
    ->setFormOption('authHeading', __('Reset password'))
    ->setFormOption('authSubheading', __('Choose a new password for your account.'))
    ->renderForm() !!}
