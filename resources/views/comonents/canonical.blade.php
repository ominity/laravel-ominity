@foreach($routes as $lang => $route)
    @if($lang == app()->getLocale()
    <link rel="canonical" href="{{ \Ominity\Laravel\Facades\Ominity::router()->route($route, $lang, $keepQuery) }}">
    @else
    <link rel="alternate" href="{{ \Ominity\Laravel\Facades\Ominity::router()->route($route, $lang, $keepQuery) }}" hreflang="{{ $lang }}">
    @endif
@endforeach