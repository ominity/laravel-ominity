<div class="form-field-checkbox form-group {{ $field->isInline ? 'form-inline' : '' }} {{ $field->css->classes }}" style="{{ $style }}">
    <div class="form-check">
        <input
            type="checkbox"
            name="{{ $field->name }}"
            class="form-check-input"
            id="{{ $id }}"
            value="1"
            @if ($field->defaultValue) checked @endif
            @if ($field->validation->isRequired) required @endif
        >
        <label class="form-check-label" for="{{ $id }}">
            {{ $field->label }}
            @if ($field->validation->isRequired)
                <span class="form-required-marker">*</span>
            @endif
        </label>
    </div>

    @if ($field->helper)
        <small class="form-text text-muted">{{ $field->helper }}</small>
    @endif

    @if ($field->validation->message)
        <div class="invalid-feedback">{{ $field->validation->message }}</div>
    @endif
</div>
