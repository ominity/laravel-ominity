@foreach($field->options as $option)
    @if($option == 'page_title')
        <input type="hidden" name="{{ $field->name }}[{{ $option }}]" value="" id="{{ $id }}-{{ $option }}">
        <script>
            document.getElementById('{{ $id }}-{{ $option }}').value = document.title;
        </script>
    @elseif($option == 'page_url') 
        <input type="hidden" name="{{ $field->name }}[{{ $option }}]" value="" id="{{ $id }}-{{ $option }}">
        <script>
            document.getElementById('{{ $id }}-{{ $option }}').value = window.location.href;
        </script>
    @endif
@endforeach