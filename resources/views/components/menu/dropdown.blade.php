<li class="nav-item dropdown {{ $class ?? '' }} {{ $item->style->classes ?? '' }}">
    <a class="nav-link dropdown-toggle" href="#">
        @if($item->icon)
            <i class="nav-icon {{ $item->icon }}"></i> 
        @endif
        {{ $item->label}}
    </a>
    <ul class="dropdown-menu">
        @foreach($item->children as $child)
            <x-ominity::menu-item :item="$child" class="dropdown-item" />
        @endforeach
    </ul>
</li>