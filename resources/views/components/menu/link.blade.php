
<li class="nav-item {{ $item->style->classes ?? '' }}">
    <a href="{{ $href }}" class="nav-link">
        @if($item->icon)
        <i class="nav-icon {{ $item->icon }}"></i> 
        @endif
        {{ $item->label}}
    </a>
</li>