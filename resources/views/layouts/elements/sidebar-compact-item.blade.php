{{-- Einzelner Eintrag im kompakten Seitenmenü. Erwartet: $item, $groupLabel, $admin --}}
<li class="nav-item" x-show="match(@js($item['name']), @js($groupLabel))"
    @if($item['unread'] > 0)
        onclick="markNotificationAsRead('{{ url('markNotificationAsRead') }}', @js($item['name']))"
    @endif>
    <a href="{{ $item['url'] }}" data-nav-item
       @if($item['external']) target="_blank" rel="noopener" @endif
       @if($item['active']) aria-current="page" @endif
       class="nav-link sidebar-nav-link sidebar-compact-link @if($admin) sidebar-admin-link @endif @if($item['active']) sidebar-active @endif">
        <i class="{{ $item['icon'] }} sidebar-compact-icon" aria-hidden="true"
           @if($admin) style="color: var(--color-sidebar-admin-icon, inherit)" @endif></i>
        <span class="sidebar-compact-label">{{ $item['name'] }}</span>
        @if($item['external'])
            <i class="fas fa-external-link-alt sidebar-compact-external" aria-hidden="true"></i>
            <span class="sr-only">(öffnet in neuem Tab)</span>
        @endif
        @if($item['unread'] > 0)
            <span class="sidebar-compact-badge" style="background-color: var(--color-badge-bg);">{{ $item['unread'] }}</span>
        @endif
    </a>
</li>
