@props([
    'title',
    'subText' => null,
    'value',
    'valueColor' => 'gray'
])
 
<div class="flex mx-2 space-x-1">
    <span class="font-bold">{{ $title }}</span>
    @if($subText)
    <i class="text-gray">{{ $subText }}</i>
    @endif
    <span class="flex-1 content-repeat-[.] text-gray"></span>
    <span class="font-bold text-{{ $valueColor }}">{{ $value }}</span>
</div>