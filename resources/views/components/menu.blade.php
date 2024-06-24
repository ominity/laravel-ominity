<ul class="{{ $class }}">
    @foreach($menu->rendered as $item)
    <x-ominity::menu-item :item="$item" />
    @endforeach
</ul>