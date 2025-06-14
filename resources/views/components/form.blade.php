<form
    action="{{ route('ominity.form.submit') }}"
    method="POST"
    id="form-{{ $form->id }}"
    class="ominity-form {{ $class ?? '' }}"
    data-form="{{ $form->id }}"
    @if(isset($ajax) && $ajax) data-role="ajax" @endif
    @if(config('ominity.forms.recaptcha.enabled')) data-recaptcha="{{ config('ominity.forms.recaptcha.version') }}" @endif
    novalidate
>
    <input type="hidden" name="_token" value="">
    <input type="hidden" name="_form" value="{{ $form->id }}">
    <input type="hidden" name="_locale" value="{{ app()->getLocale() }}">

    @if (isset($above))
        {{ $above }}
    @endif

    {{-- Render hidden form input fields --}}
    @foreach($form->fields as $field)
        @if (in_array($field->type, ['hidden', 'metadata', 'honeypot']))
            <x-ominity::form-field :field="$field" />
        @endif
    @endforeach

    {{-- Render form rows --}}
    @foreach ($rows as $row)
        <div class="form-row">
            @foreach ($row as $field)
                <x-ominity::form-field :field="$field" />
            @endforeach
        </div>
    @endforeach

    @if (isset($below))
        {{ $below }}
    @endif

    <script>
        document.querySelector('#form-{{ $form->id }} input[name="_token"]').value = document.querySelector('meta[name="csrf-token"]').content;
    </script>
</form>
