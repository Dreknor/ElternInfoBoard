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
               class="flex flex-col items-center justify-center gap-0.5 py-2 transition-all duration-200 group"
               style="color: var(--color-mobile-nav-text);"
               onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary')"
               onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-mobile-nav-text')">
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
            {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors border']) }}
            style="color: var(--color-primary); background-color: var(--color-primary-light); border-color: color-mix(in srgb, var(--color-primary) 30%, transparent);">
        <i class="fas fa-question-circle"></i>
        <span>{{ $label }}</span>
    </button>
@else
    <button type="button"
            x-data
            @click="$dispatch('help:open', {{ $payload }})"
            title="Hilfe & Anleitung"
            aria-label="Hilfe öffnen"
            class="relative inline-flex items-center justify-center p-2 rounded-lg transition-all duration-200"
            style="color: var(--color-mobile-nav-text);"
            onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary');this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary-light')"
            onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-mobile-nav-text');this.style.backgroundColor=''">
        <i class="fas fa-question-circle text-2xl"></i>
    </button>
@endif
