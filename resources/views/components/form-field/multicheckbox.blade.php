<div class="form-field-multicheckbox form-group {{ $field->isInline ? 'form-inline' : '' }} {{ $field->css->classes }}" style="{{ $style }}">
    <label class="{{ $field->isLabelVisible ? 'form-label' : 'sr-only' }}">
        {{ $field->label }}
        @if ($field->validation->isRequired)
            <span class="form-required-marker">*</span>
        @endif
    </label>

    <div class="input-group">
        @php
            $selectedValues = (array) $field->defaultValue;
        @endphp

        @foreach ($field->options as $option)
            <div class="form-check form-check-inline">
                <input
                    type="checkbox"
                    name="{{ $field->name }}[]"
                    class="form-check-input"
                    id="{{ $id }}_{{ $loop->index }}"
                    value="{{ $option->value }}"
                    @if (in_array($option->value, $selectedValues)) checked @endif
                    @if ($field->validation->isRequired && $loop->first) required @endif
                >
                <label class="form-check-label" for="{{ $id }}_{{ $loop->index }}">
                    {{ $option->label }}
                </label>
            </div>
        @endforeach
    </div>

    @if ($field->helper)
        <small class="form-text text-muted">{{ $field->helper }}</small>
    @endif

    @if ($field->validation->message)
        <div class="invalid-feedback">{{ $field->validation->message }}</div>
    @endif
</div>
