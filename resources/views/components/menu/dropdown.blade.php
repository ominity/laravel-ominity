<li class="nav-item nav-dropdown {{ $item->style->classes ?? '' }}">
    <a class="nav-link nav-dropdown-toggle" href="#">
        @if($item->icon)
            <i class="nav-icon {{ $item->icon }}"></i> 
        @endif
        {{ $item->label}}
    </a>
    <ul class="nav-dropdown-items">
        @foreach($item->children as $child)
            <x-ominity::menu-item :item="$child" />
        @endforeach
    </ul>
</li>