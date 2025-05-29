<form action="{{ route('ominity.form.submit') }}" method="POST" {{ $attributes->merge([
    'id' => 'form-' . $form->id,
    'class' => 'ominity-form ' . ($class ?? ''),
    'data-form' => $form->id,
    'data-role' => isset($ajax) && $ajax ? 'ajax' : null,
    'data-recaptcha' => config('ominity.forms.recaptcha.enabled') ? config('ominity.forms.recaptcha.version') : null,
]) }} novalidate>
    <input type="hidden" name="_token" value="">
    <input type="hidden" name="_form" value="{{ $form->id }}">
    <input type="hidden" name="_locale" value="{{ app()->getLocale() }}">

    @if (isset($above))
        {{ $above }}
    @endif

    @foreach ($form->fields() as $field)
        <x-ominity::form-field :field="$field" />
    @endforeach

    @if (isset($below))
        {{ $below }}
    @endif

    <script>
        document.querySelector('#form-{{ $form->id }} input[name="_token"]').value = document.querySelector('meta[name="csrf-token"]').content;
    </script>
</form>
