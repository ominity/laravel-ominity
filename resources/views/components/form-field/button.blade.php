<div class="form-field-button">
    @if ($field->defaultValue == 'submit' && $recaptchaConfig['enabled'] && $recaptchaConfig['version'] == 'v2')
        <div class="g-recaptcha" data-sitekey="{{ $recaptchaConfig['site_key'] }}"></div>
    @endif

    <button type="{{ $field->defaultValue }}" class="{{ $field->isInline ? 'form-inline' : '' }} {{ $field->css->classes ?? 'btn btn-primary' }}" style="{{ $style }}" id="{{ $id }}">{{ $field->label }}</button>
</div>
