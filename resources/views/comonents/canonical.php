@foreach($routes as $lang => $route)
    @if($lang == app()->getLocale()
    <link rel="canonical" href="{{ Ominity::router()->route($route, $lang, $keepQuery) }}">
    @else
    <link rel="alternate" href="{{ Ominity::router()->route($route, $lang, $keepQuery) }}" hreflang="{{ $lang }}">
    @endif
@endforeach