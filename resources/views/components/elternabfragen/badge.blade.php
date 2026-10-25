@props(['color' => 'gray', 'icon' => null, 'dot' => false])
@php
    // Vollständige Klassennamen, damit Tailwind sie beim Build findet
    $classes = match ($color) {
        'green' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-900/40 dark:text-emerald-300',
        'teal' => 'bg-teal-100 text-teal-800 ring-teal-600/20 dark:bg-teal-900/40 dark:text-teal-300',
        'amber' => 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-900/40 dark:text-amber-300',
        'red' => 'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-900/40 dark:text-red-300',
        'blue' => 'bg-blue-100 text-blue-800 ring-blue-600/20 dark:bg-blue-900/40 dark:text-blue-300',
        'slate' => 'bg-slate-200 text-slate-700 ring-slate-500/20 dark:bg-slate-700/60 dark:text-slate-200',
        default => 'bg-gray-100 text-gray-700 ring-gray-500/20 dark:bg-gray-700/60 dark:text-gray-200',
    };
    $dotClass = match ($color) {
        'green' => 'bg-emerald-500',
        'teal' => 'bg-teal-500',
        'amber' => 'bg-amber-500',
        'red' => 'bg-red-500',
        'blue' => 'bg-blue-500',
        'slate' => 'bg-slate-500',
        default => 'bg-gray-400',
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap ring-1 ring-inset '.$classes]) }}>
    @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@elseif($dot)<span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
