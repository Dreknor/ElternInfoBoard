{{-- Abschnitt (einklappbar) im kompakten Seitenmenü. Erwartet: $group, $admin --}}
@php
    $groupLabel = $admin ? $group['label'].' Verwaltung' : $group['label'];
    $groupNames = collect($group['items'])->pluck('name')->values()->all();
    $panelId = 'nav-group-'.$group['key'];
@endphp
<li class="sidebar-compact-group" x-show="groupVisible(@js($groupNames), @js($groupLabel))">
    <button type="button"
            class="sidebar-compact-group-toggle @if($group['active']) sidebar-compact-group-toggle--active @endif"
            @click="toggle(@js($group['key']))"
            :aria-expanded="isOpen(@js($group['key']), @js($group['active'])).toString()"
            aria-expanded="true"
            aria-controls="{{ $panelId }}">
        <span class="sidebar-compact-group-label">{{ $group['label'] }}</span>
        @if($group['unread'] > 0)
            <span class="sidebar-compact-badge" style="background-color: var(--color-badge-bg);"
                  x-show="!isOpen(@js($group['key']), @js($group['active']))" x-cloak>{{ $group['unread'] }}</span>
        @endif
        <i class="fas fa-chevron-down sidebar-compact-chevron" aria-hidden="true"
           :class="{ 'is-collapsed':!isOpen(@js($group['key']), @js($group['active'])) }"></i>
    </button>
    <ul id="{{ $panelId }}" class="sidebar-compact-panel"
        x-show="isOpen(@js($group['key']), @js($group['active']))">
        @foreach($group['items'] as $item)
            @include('layouts.elements.sidebar-compact-item', ['item' => $item, 'groupLabel' => $groupLabel, 'admin' => $admin])
        @endforeach
    </ul>
</li>
