@props(['color' => 'gray', 'icon' => null])
@php
    // Vollständige Klassennamen, damit Tailwind sie beim Build findet
    $classes = match ($color) {
        'green' => 'bg-green-100 text-green-800',
        'teal' => 'bg-teal-100 text-teal-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'red' => 'bg-red-100 text-red-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'slate' => 'bg-slate-200 text-slate-700',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap '.$classes]) }}>
    @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot }}
</span>
