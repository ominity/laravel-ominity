<a href="{{ $href }}" class="nav-link {{ $item->style->classes ?? '' }}">
    @if($item->icon)
    <i class="nav-icon {{ $item->icon }}"></i> 
    @endif
    {{ $item->label}}
</a>