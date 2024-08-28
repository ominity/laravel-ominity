@foreach($field->options as $option)
    @if($option == 'page_title')
        <input type="hidden" name="{{ $field->id }}[{{ $option }}]" value="" id="{{ $id }}_{{ $option }}">
        <script>
            document.getElementById('{{ $field->id }}_{{ $option }}').value = document.title;
        </script>
    @elseif($option == 'page_url') 
        <input type="hidden" name="{{ $field->id }}[{{ $option }}]" value="" id="{{ $id }}_{{ $option }}">
        <script>
            document.getElementById('{{ $field->id }}_{{ $option }}').value = window.location.href;
        </script>
    @endif
@endforeach