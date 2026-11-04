{{--
    Kompaktes Seitenmenü (GeneralSetting::$nav_style / Nutzereinstellung 'nav_style' = 'compact').
    Gruppen und Zuordnung: config/navigation.php, Aufbau: App\Services\NavigationService.
    $navigation wird vom NavigationComposer bereitgestellt.

    Aufbau:
    - Bereichsumschalter „Übersicht“ / „Verwaltung“ (nur mit Verwaltungsrechten), damit
      Eltern- und Verwaltungsfunktionen nicht in einer langen Liste vermischt werden.
    - Gruppen als Abschnitte mit Überschrift, standardmäßig aufgeklappt; zugeklappte
      Abschnitte werden pro Browser gemerkt.
    - Filter durchsucht beide Bereiche (Tastenkürzel „/“).
--}}
@php
    $hasAdminNav = count($navigation['admin']) || count($navigation['admin_top']);

    $mainActive = collect($navigation['top'])->contains('active', true)
        || collect($navigation['groups'])->contains('active', true)
        || in_array(request()->path(), ['dashboard', '/'], true);
    $adminActive = collect($navigation['admin_top'])->contains('active', true)
        || collect($navigation['admin'])->contains('active', true);
    $navArea = $adminActive ? 'admin' : ($mainActive ? 'main' : null);

    $mainUnread = collect($navigation['top'])->sum('unread') + collect($navigation['groups'])->sum('unread');

    // Alle Einträge als [Name, Gruppe] für die Trefferprüfung des Filters
    $navEntries = collect([['Dashboard', 'Startseite Home']])
        ->merge(collect($navigation['top'])->map(fn ($i) => [$i['name'], '']))
        ->merge(collect($navigation['groups'])->flatMap(fn ($g) => collect($g['items'])->map(fn ($i) => [$i['name'], $g['label']])))
        ->merge(collect($navigation['admin_top'])->map(fn ($i) => [$i['name'], 'Verwaltung']))
        ->merge(collect($navigation['admin'])->flatMap(fn ($g) => collect($g['items'])->map(fn ($i) => [$i['name'], $g['label'].' Verwaltung'])))
        ->values()->all();

    $adminNames = collect($navigation['admin_top'])->pluck('name')
        ->merge(collect($navigation['admin'])->flatMap(fn ($g) => collect($g['items'])->pluck('name')))
        ->values()->all();
@endphp

<script>
    window.compactNav = window.compactNav || function (entries, activeArea, hasAdmin) {
        const collapsedKey = 'nav-compact-collapsed';
        const areaKey = 'nav-compact-area';
        const normalize = (value) => (value || '').toString().toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '');
        const read = (key, fallback) => {
            try {
                const value = JSON.parse(localStorage.getItem(key) || 'null');
                return value ?? fallback;
            } catch (e) {
                return fallback;
            }
        };
        const write = (key, value) => {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {}
        };

        const storedArea = read(areaKey, 'main');
        const collapsed = read(collapsedKey, []);

        return {
            q: '',
            area: hasAdmin ? (activeArea || (storedArea === 'admin' ? 'admin' : 'main')) : 'main',
            collapsed: Array.isArray(collapsed) ? collapsed : [],
            init() {
                this.$watch('area', (value) => write(areaKey, value));
            },
            setArea(value) {
                this.area = value;
                this.$nextTick(() => this.$root.scrollTo({ top: 0 }));
            },
            areaVisible(value) {
                return this.q !== '' || this.area === value;
            },
            isOpen(key, active) {
                return this.q !== '' || active || !this.collapsed.includes(key);
            },
            toggle(key) {
                if (this.q !== '') return;
                this.collapsed = this.collapsed.includes(key)
                    ? this.collapsed.filter((k) => k !== key)
                    : [...this.collapsed, key];
                write(collapsedKey, this.collapsed);
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
            focusFilter(event) {
                const target = event.target;
                if (target.closest('input, textarea, select, [contenteditable="true"]')) return;
                event.preventDefault();
                if (window.innerWidth < 992) {
                    document.documentElement.classList.add('nav-open');
                }
                this.$refs.filter.focus();
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
     x-data="compactNav(@js($navEntries), @js($navArea), @js((bool) $hasAdminNav))"
     @keydown.slash.window="focusFilter($event)">

    <div class="sidebar-compact-head">
        {{-- Bereichsumschalter --}}
        @if($hasAdminNav)
            <div class="sidebar-compact-switch" role="tablist" aria-label="Menübereich">
                <button type="button" role="tab" id="nav-tab-main" aria-controls="nav-area-main"
                        class="sidebar-compact-switch-btn"
                        :class="{ 'is-active': area === 'main' }"
                        :aria-selected="(area === 'main').toString()"
                        aria-selected="{{ $navArea !== 'admin' ? 'true' : 'false' }}"
                        @click="setArea('main')">
                    <i class="fas fa-home" aria-hidden="true"></i>
                    <span>Übersicht</span>
                    @if($mainUnread > 0)
                        <span class="sidebar-compact-dot" x-show="area !== 'main'" style="background-color: var(--color-badge-bg);"
                              title="{{ $mainUnread }} ungelesen"></span>
                    @endif
                </button>
                <button type="button" role="tab" id="nav-tab-admin" aria-controls="nav-area-admin"
                        class="sidebar-compact-switch-btn"
                        :class="{ 'is-active': area === 'admin' }"
                        :aria-selected="(area === 'admin').toString()"
                        aria-selected="{{ $navArea === 'admin' ? 'true' : 'false' }}"
                        @click="setArea('admin')">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    <span>Verwaltung</span>
                </button>
            </div>
        @endif

        {{-- Filter --}}
        <div class="sidebar-compact-filter">
            <label for="sidebarFilter" class="sr-only">Menü durchsuchen</label>
            <i class="fas fa-search sidebar-compact-filter-icon" aria-hidden="true"></i>
            <input type="search" id="sidebarFilter" x-model="q" x-ref="filter" autocomplete="off"
                   placeholder="Menü durchsuchen"
                   @keydown.escape.prevent="q = ''; $el.blur()"
                   @keydown.enter.prevent="openFirstMatch()"
                   class="sidebar-compact-filter-input">
            <kbd class="sidebar-compact-kbd" x-show="q === ''" aria-hidden="true">/</kbd>
        </div>
    </div>

    {{-- Bereich: Übersicht --}}
    <ul class="nav flex-column sidebar-compact-list" id="nav-area-main"
        @if($hasAdminNav) role="tabpanel" aria-labelledby="nav-tab-main" @endif
        x-show="areaVisible('main')"
        @if($navArea === 'admin') style="display: none;" @endif>
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
    </ul>

    {{-- Bereich: Verwaltung --}}
    @if($hasAdminNav)
        <ul class="nav flex-column sidebar-compact-list" id="nav-area-admin"
            role="tabpanel" aria-labelledby="nav-tab-admin"
            x-show="areaVisible('admin')"
            @if($navArea !== 'admin') style="display: none;" @endif>
            <li class="sidebar-compact-area-title" x-show="q !== '' && groupVisible(@js($adminNames), 'Verwaltung')" x-cloak>
                <i class="fas fa-shield-alt" aria-hidden="true"></i> Verwaltung
            </li>

            @foreach($navigation['admin_top'] as $item)
                @include('layouts.elements.sidebar-compact-item', ['item' => $item, 'groupLabel' => 'Verwaltung', 'admin' => true])
            @endforeach

            @foreach($navigation['admin'] as $group)
                @include('layouts.elements.sidebar-compact-group', ['group' => $group, 'admin' => true])
            @endforeach
        </ul>
    @endif

    <p x-show="q !== '' && !hasResults()" x-cloak class="sidebar-compact-empty">
        <i class="fas fa-search" aria-hidden="true"></i>
        Kein Menüpunkt zu „<span x-text="q"></span>“ gefunden.
    </p>
</div>
