<div class="form-group {{ $field->isInline ? 'form-inline' : '' }} {{ $field->css->classes }}" style="{{ $style }}">
    <label for="{{ $id }}" class="{{ $field->isLabelVisible ? 'form-label' : 'sr-only' }}">{{ $field->label }}</label>
    <div class="input-group">
        <textarea name="{{ $field->id }}" type="phone" class="form-control" id="{{ $id }}" @if($field->placeholder) placeholder="{{ $field->placeholder }}" @endif @if($field->validation->isRequired) required="" @endif rows="3">{{ $field->default }}</textarea>
        @if($field->helper)
        <small class="form-text text-muted">{{ $field->helper }}</small>
        @endif
        @if($field->validation->message)
        <div class="invalid-feedback">{{ $field->validation->message }}</div>
        @endif
    </div>
</div>