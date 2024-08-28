<form action="{{ route('ominity.form.submit') }}" method="POST" class="{{ $class }}">
    @csrf
    <input type="hidden" name="_form" value="{{ $form->id }}">
    @if(isset($above))
        {{ $above }}
    @endif
    @foreach($form->fields() as $field)
        <x-ominity::form-field :field="$field" />
    @endforeach
    @if(isset($below))
        {{ $below }}
    @endif
</form>