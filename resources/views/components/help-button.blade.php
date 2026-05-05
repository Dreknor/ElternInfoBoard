@props([
    'variant' => 'navbar', // navbar | mobile | inline
    'label'   => 'Hilfe',
])

@php
    $route = optional(\Illuminate\Support\Facades\Route::current())->getName();
    $uri   = request()->path();
    $payload = json_encode(['route' => $route, 'uri' => $uri]);
@endphp

@if($variant === 'mobile')
    <div class="mobile-bottom-nav_item flex-1">
        <div class="mobile-bottom-nav_item-content">
            <a href="#"
               x-data
               @click.prevent="$dispatch('help:open', {{ $payload }})"
               class="flex flex-col items-center justify-center gap-0.5 py-2 text-gray-600 hover:text-blue-600 active:text-blue-700 transition-all duration-200 group">
                <div class="relative">
                    <i class="fas fa-question-circle text-2xl group-hover:scale-110 transition-transform duration-200"></i>
                </div>
                <span class="text-[10px] font-semibold mt-0.5">{{ $label }}</span>
            </a>
        </div>
    </div>
@elseif($variant === 'inline')
    <button type="button"
            x-data
            @click="$dispatch('help:open', {{ $payload }})"
            {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors']) }}>
        <i class="fas fa-question-circle"></i>
        <span>{{ $label }}</span>
    </button>
@else
    <button type="button"
            x-data
            @click="$dispatch('help:open', {{ $payload }})"
            title="Hilfe & Anleitung"
            aria-label="Hilfe öffnen"
            class="relative inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200">
        <i class="fas fa-question-circle text-2xl"></i>
    </button>
@endif
