{{--
    Kompaktes Seitenmenü (GeneralSetting::$nav_style / Nutzereinstellung 'nav_style' = 'compact').
    Gruppen und Zuordnung: config/navigation.php, Aufbau: App\Services\NavigationService.
    $navigation wird vom NavigationComposer bereitgestellt.
--}}
@php
    $navGroupsOpen = collect($navigation['groups'])->merge($navigation['admin'])
        ->where('active', true)->pluck('key')->values()->all();

    // Alle Einträge als [Name, Gruppe] für die Trefferprüfung des Filters
    $navEntries = collect([['Dashboard', 'Startseite Home']])
        ->merge(collect($navigation['top'])->map(fn ($i) => [$i['name'], '']))
        ->merge(collect($navigation['groups'])->flatMap(fn ($g) => collect($g['items'])->map(fn ($i) => [$i['name'], $g['label']])))
        ->merge(collect($navigation['admin_top'])->map(fn ($i) => [$i['name'], 'Verwaltung']))
        ->merge(collect($navigation['admin'])->flatMap(fn ($g) => collect($g['items'])->map(fn ($i) => [$i['name'], $g['label'].' Verwaltung'])))
        ->values()->all();
@endphp

<script>
    window.compactNav = window.compactNav || function (activeGroups, entries) {
        const storageKey = 'nav-compact-open';
        const normalize = (value) => (value || '').toString().toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '');

        let stored = [];
        try {
            stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
            if (!Array.isArray(stored)) stored = [];
        } catch (e) {
            stored = [];
        }

        return {
            q: '',
            open: Array.from(new Set([...stored, ...activeGroups])),
            isOpen(key) {
                return this.q !== '' || this.open.includes(key);
            },
            toggle(key) {
                if (this.q !== '') return;
                this.open = this.open.includes(key)
                    ? this.open.filter((k) => k !== key)
                    : [...this.open, key];
                try {
                    localStorage.setItem(storageKey, JSON.stringify(this.open));
                } catch (e) {}
            },
            match(name, groupLabel) {
                if (this.q === '') return true;
                const needle = normalize(this.q.trim());
                return normalize(name).includes(needle) || normalize(groupLabel).includes(needle);
            },
            groupVisible(names, groupLabel) {
                return names.some((name) => this.match(name, groupLabel));
            },
            hasResults() {
                return entries.some(([name, groupLabel]) => this.match(name, groupLabel));
            },
            openFirstMatch() {
                const first = Array.from(this.$root.querySelectorAll('[data-nav-item]'))
                    .find((el) => el.offsetParent !== null);
                if (first) first.click();
            },
        };
    };
</script>

<div class="sidebar-wrapper overflow-y-auto sidebar-nav-scroll sidebar-compact" id="sidebar"
     x-data="compactNav(@js($navGroupsOpen), @js($navEntries))">

    {{-- Filter --}}
    <div class="sidebar-compact-filter px-2 pt-3 pb-2">
        <label for="sidebarFilter" class="sr-only">Menü durchsuchen</label>
        <div class="relative">
            <i class="fas fa-filter sidebar-compact-filter-icon" aria-hidden="true"></i>
            <input type="search" id="sidebarFilter" x-model="q" autocomplete="off"
                   placeholder="Menü durchsuchen…"
                   @keydown.escape.prevent="q = ''"
                   @keydown.enter.prevent="openFirstMatch()"
                   class="sidebar-compact-filter-input">
        </div>
    </div>

    <ul class="nav flex-column px-2 pb-3 sidebar-compact-list">
        {{-- Dashboard --}}
        @php $dashboardActive = in_array(request()->path(), ['dashboard', '/'], true); @endphp
        <li class="nav-item" x-show="match('Dashboard', 'Startseite Home')">
            <a href="{{ url('/dashboard') }}" data-nav-item
               class="nav-link sidebar-nav-link sidebar-compact-link @if($dashboardActive) sidebar-active @endif"
               @if($dashboardActive) aria-current="page" @endif>
                <i class="fas fa-home sidebar-compact-icon" aria-hidden="true"></i>
                <span class="sidebar-compact-label">Dashboard</span>
            </a>
        </li>

        @foreach($navigation['top'] as $item)
            @include('layouts.elements.sidebar-compact-item', ['item' => $item, 'groupLabel' => '', 'admin' => false])
        @endforeach

        @foreach($navigation['groups'] as $group)
            @include('layouts.elements.sidebar-compact-group', ['group' => $group, 'admin' => false])
        @endforeach

        @if(count($navigation['admin']) || count($navigation['admin_top']))
            @php
                $adminNames = collect($navigation['admin_top'])->pluck('name')
                    ->merge(collect($navigation['admin'])->flatMap(fn ($g) => collect($g['items'])->pluck('name')))
                    ->values()->all();
            @endphp
            <li class="sidebar-compact-section" x-show="groupVisible(@js($adminNames), 'Verwaltung')">
                <div class="flex items-center gap-2">
                    <div class="flex-1 border-t" style="border-color: var(--color-sidebar-admin-border, rgba(255,255,255,0.15))"></div>
                    <span class="text-[9px] font-bold uppercase tracking-widest flex items-center gap-1.5 px-1"
                          style="color: var(--color-sidebar-admin-label, rgba(255,255,255,0.4))">
                        <i class="fas fa-shield-alt text-[9px]" aria-hidden="true"></i>
                        Verwaltung
                    </span>
                    <div class="flex-1 border-t" style="border-color: var(--color-sidebar-admin-border, rgba(255,255,255,0.15))"></div>
                </div>
            </li>

            @foreach($navigation['admin_top'] as $item)
                @include('layouts.elements.sidebar-compact-item', ['item' => $item, 'groupLabel' => 'Verwaltung', 'admin' => true])
            @endforeach

            @foreach($navigation['admin'] as $group)
                @include('layouts.elements.sidebar-compact-group', ['group' => $group, 'admin' => true])
            @endforeach
        @endif

        <li x-show="q !== '' && !hasResults()" x-cloak class="px-3 py-4 text-center text-sm sidebar-compact-empty">
            Kein Menüpunkt gefunden.
        </li>
    </ul>
</div>
