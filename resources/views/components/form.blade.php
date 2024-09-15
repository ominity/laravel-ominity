<form action="{{ route('ominity.form.submit') }}" method="POST" class="{{ $class }}" novalidate>
    <input type="hidden" name="_token" value="">
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

    <script>
        document.querySelector('input[name="_token"]').value = document.querySelector('meta[name="csrf-token"]').content;
    </script>
</form>