{{--
    Kachelansicht der Anwesenheitsliste (Hortalltag, Tablet quer, Touch).

    - Lädt immer alle Kinder; Anzeigemodus (da / da + erwartet / alle), Gruppen-/Klassenfilter,
      Suche und Sortierung wirken im Browser (board_view-Skript unten).
    - #groups-container wird von refreshAttendanceList() (index.blade.php) ausgetauscht;
      Werkzeugleiste, Zähler und Leiste „Als Nächstes gehen“ liegen deshalb außerhalb.
    - Ein Tipp auf den Status-Knopf meldet an/ab: der Wechsel wird 5 Sekunden lang nur angezeigt
      und erst danach gespeichert, damit „Rückgängig“ keine Benachrichtigung an Eltern auslöst.
    - Ein Tipp auf Name/Initialen (.child-item) öffnet das bestehende Detailfenster.
--}}
@php
    $isClassOnlyMode = $groups->isEmpty() && $classes->isNotEmpty();
    $groupingField = $isClassOnlyMode ? 'class_id' : 'group_id';
    $displayGroups = ($isClassOnlyMode ? $classes : $groups)->sortBy('name')->values();
    $groupsAndClassesIdentical = ! $isClassOnlyMode
        && $groups->pluck('id')->sort()->values()->all() === $classes->pluck('id')->sort()->values()->all();
    $showClassFilter = ! $isClassOnlyMode && ! $groupsAndClassesIdentical && $classes->count() > 1;
    $classNames = $classes->pluck('name', 'id');
    $displayGroupIds = $displayGroups->pluck('id')->all();
    $otherChildren = $children->filter(fn ($child) => ! in_array($child->{$groupingField}, $displayGroupIds));
    $hideMode = (bool) $careSettings->hide_childs_when_absent;
    $mode = $hideMode ? ($attendanceMode ?? 'present') : 'all';
    $ferientag = (bool) ($isFerientag ?? false);

    $minutes = fn ($time) => $time ? ((int) $time->format('H')) * 60 + (int) $time->format('i') : null;
@endphp

<div class="w-full px-2 sm:px-3">
<div id="att-board" class="att-board att-density-compact"
     data-mode="{{ $mode }}"
     data-server-minutes="{{ now()->hour * 60 + now()->minute }}"
     data-url-anmelden="{{ url('care/anwesenheit/:childId/anmelden') }}"
     data-url-abmelden="{{ url('care/anwesenheit/:childId/abmelden') }}">

    {{-- Kopfzeile: Datum, Uhr, Zähler --}}
    <header class="att-head">
        <div class="att-clock-block">
            <div class="att-date">{{ now()->locale('de')->isoFormat('dddd, D. MMMM') }}</div>
            <div class="att-clock" id="att-clock" aria-live="off">{{ now()->format('H:i') }}</div>
            @if($ferientag)
                <span class="att-badge att-badge-holiday"><i class="fas fa-umbrella-beach" aria-hidden="true"></i> Ferientag</span>
            @endif
        </div>
        <div class="att-stats" role="group" aria-label="Übersicht">
            <div class="att-stat att-stat-present">
                <span class="att-stat-value" data-count="present">0</span>
                <span class="att-stat-label">da</span>
            </div>
            <button type="button" class="att-stat att-stat-expected" id="att-filter-missing" aria-pressed="false"
                    title="Nur Kinder anzeigen, die erwartet werden, aber noch nicht da sind">
                <span class="att-stat-value" data-count="expected">0</span>
                <span class="att-stat-label">fehlt noch</span>
            </button>
            <div class="att-stat att-stat-gone">
                <span class="att-stat-value" data-count="gone">0</span>
                <span class="att-stat-label">gegangen</span>
            </div>
            <div class="att-stat att-stat-sick">
                <span class="att-stat-value" data-count="sick">0</span>
                <span class="att-stat-label">krank</span>
            </div>
        </div>
    </header>

    {{-- Werkzeugleiste --}}
    <div class="att-toolbar">
        <div class="att-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="att-search" class="ea-input" placeholder="Kind suchen…" autocomplete="off"
                   aria-label="Kind suchen">
            <button type="button" id="att-search-clear" class="att-search-clear" aria-label="Suche leeren" hidden>
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        @if($hideMode)
            <div class="att-segment" role="group" aria-label="Anzeigemodus">
                <a href="{{ route('anwesenheit.index', ['showAll' => 'off']) }}"
                   class="att-segment-btn {{ $mode === 'present' ? 'is-active' : '' }}">Da</a>
                <a href="{{ route('anwesenheit.index', ['showAll' => 'expected']) }}"
                   class="att-segment-btn {{ $mode === 'expected' ? 'is-active' : '' }}">Da + erwartet</a>
                <a href="{{ route('anwesenheit.index', ['showAll' => 1]) }}"
                   class="att-segment-btn {{ $mode === 'all' ? 'is-active' : '' }}">Alle</a>
            </div>
        @endif

        <div class="att-segment" role="group" aria-label="Sortierung">
            <button type="button" class="att-segment-btn" data-sort="name"><i class="fas fa-sort-alpha-down" aria-hidden="true"></i> Name</button>
            <button type="button" class="att-segment-btn" data-sort="time"><i class="fas fa-clock" aria-hidden="true"></i> Schickzeit</button>
        </div>

        <button type="button" class="ea-icon-btn att-density-btn" id="att-density" title="Kachelgröße umschalten" aria-label="Kachelgröße umschalten">
            <i class="fas fa-th-large" aria-hidden="true"></i>
        </button>
    </div>

    @if($displayGroups->count() > 1 || $otherChildren->isNotEmpty())
        <div class="att-chips" role="group" aria-label="{{ $isClassOnlyMode ? 'Klassen' : 'Gruppen' }}">
            <button type="button" class="ea-chip" data-filter-group="">Alle {{ $isClassOnlyMode ? 'Klassen' : 'Gruppen' }}</button>
            @foreach($displayGroups as $group)
                <button type="button" class="ea-chip" data-filter-group="{{ $group->id }}">{{ $group->name }}</button>
            @endforeach
        </div>
    @endif
    @if($showClassFilter)
        <div class="att-chips" role="group" aria-label="Klassen">
            <button type="button" class="ea-chip" data-filter-class="">Alle Klassen</button>
            @foreach($classes->sortBy('name') as $class)
                <button type="button" class="ea-chip" data-filter-class="{{ $class->id }}">{{ $class->name }}</button>
            @endforeach
        </div>
    @endif

    {{-- Als Nächstes gehen (wird im Browser aus den Kacheln berechnet) --}}
    <section class="att-next" id="att-next" aria-label="Als Nächstes gehen">
        <div class="att-next-title"><i class="fas fa-walking" aria-hidden="true"></i> Als Nächstes gehen</div>
        <div class="att-next-list" id="att-next-list"></div>
        <div class="att-next-empty" id="att-next-empty">Keine Abholungen in der nächsten Stunde.</div>
    </section>

    {{-- Kinder (wird bei jeder Aktualisierung ausgetauscht) --}}
    <div id="groups-container" class="att-sections">
        @php
            $sections = $displayGroups->map(fn ($group) => [
                'id' => (string) $group->id,
                'name' => $group->name,
                'children' => $children->where($groupingField, $group->id),
            ]);
            if ($otherChildren->isNotEmpty()) {
                $sections->push(['id' => 'other', 'name' => 'Weitere Kinder', 'children' => $otherChildren]);
            }
        @endphp

        @foreach($sections as $section)
            @continue($section['children']->isEmpty() && $careSettings->hide_groups_when_empty)
            <section class="att-section" data-group="{{ $section['id'] }}">
                <h2 class="att-section-title">
                    <span>{{ $section['name'] }}</span>
                    <span class="att-section-count"><b data-section-present>0</b> da / <span data-section-total>0</span></span>
                </h2>
                <div class="att-grid">
                    @foreach($section['children']->sortBy([['last_name', 'asc'], ['first_name', 'asc']]) as $child)
                        @php
                            $checkIn = $child->checkIns->first();
                            $sick = $child->krankmeldungToday();
                            if ($child->checkedIn()) {
                                $status = 'present';
                            } elseif ($checkIn && $checkIn->checked_out) {
                                $status = 'gone';
                            } elseif ($sick) {
                                $status = 'sick';
                            } elseif ($child->should_be_today()) {
                                $status = 'expected';
                            } else {
                                $status = 'absent';
                            }

                            // Schickzeit heute: früheste Angabe zählt
                            $pickFrom = $pickDeadline = null;
                            $pickLabel = '';
                            foreach ($child->getSchickzeitenForToday() ?? [] as $sz) {
                                if ($sz->type === 'genau' && $sz->time) {
                                    $from = $deadline = $minutes($sz->time);
                                    $label = $sz->time->format('H:i').' Uhr';
                                } else {
                                    $from = $minutes($sz->time_ab);
                                    $deadline = $minutes($sz->time_spaet);
                                    $label = trim(($sz->time_ab ? 'ab '.$sz->time_ab->format('H:i') : '')
                                        .($sz->time_ab && $sz->time_spaet ? ' · ' : '')
                                        .($sz->time_spaet ? 'spät. '.$sz->time_spaet->format('H:i') : ''));
                                }
                                $sortKey = $from ?? $deadline;
                                $currentKey = $pickFrom ?? $pickDeadline;
                                if ($sortKey !== null && ($currentKey === null || $sortKey < $currentKey)) {
                                    $pickFrom = $from;
                                    $pickDeadline = $deadline;
                                    $pickLabel = $label;
                                }
                            }

                            $notice = $child->hasNotice()?->loadMissing('user');
                            $noticeNew = $notice && $child->noticeToday()?->isNew();
                            $ags = $ferientag ? collect() : $child->arbeitsgemeinschaften_aktuell();

                            $childData = array_merge($child->toArray(), [
                                'checked_in' => $child->checkedIn() ? 'true' : 'false',
                                'schickzeiten' => $child->getSchickzeitenForToday()?->toArray(),
                                'regular_schickzeiten' => $child->regularSchickzeiten?->toArray(),
                                'mandates' => $child->mandates?->toArray(),
                                'parents' => $parentContacts[$child->id] ?? [],
                            ]);
                            $noticeData = $notice ? [
                                'id' => $notice->id,
                                'notice' => $notice->notice,
                                'date' => $notice->date?->toDateString(),
                                'created_at' => $notice->created_at?->toIso8601String(),
                                'created_at_formatted' => $notice->created_at?->format('d.m.Y H:i'),
                                'author_name' => $notice->user?->name ?? 'Unbekannt',
                            ] : null;
                            $initials = mb_strtoupper(mb_substr($child->first_name, 0, 1).mb_substr($child->last_name, 0, 1));
                        @endphp
                        <article class="att-tile"
                                 data-child-id="{{ $child->id }}"
                                 data-status="{{ $status }}"
                                 data-sick="{{ $sick ? '1' : '0' }}"
                                 data-group="{{ $section['id'] }}"
                                 data-class="{{ $child->class_id }}"
                                 data-first-name="{{ $child->first_name }}"
                                 data-search="{{ mb_strtolower($child->first_name.' '.$child->last_name.' '.$child->last_name.' '.$child->first_name) }}"
                                 data-sort-name="{{ mb_strtolower($child->last_name.' '.$child->first_name) }}"
                                 data-pick-from="{{ $pickFrom }}"
                                 data-pick-deadline="{{ $pickDeadline }}"
                                 data-pick-label="{{ $pickLabel }}"
                                 data-gone-at="{{ $status === 'gone' ? $checkIn?->checked_out_at?->format('H:i') : '' }}">
                            <button type="button" class="att-tile-main child-item"
                                    data-child='@json($childData)'
                                    data-notices='@json($noticeData)'
                                    title="Details zu {{ $child->first_name }} öffnen">
                                <span class="ea-avatar att-avatar" aria-hidden="true">{{ $initials }}</span>
                                <span class="att-tile-text">
                                    <span class="att-name">{{ $child->first_name }} {{ $child->last_name }}</span>
                                    @if($child->class_id && $classNames->has($child->class_id))
                                        <span class="att-class">{{ $classNames[$child->class_id] }}</span>
                                    @endif
                                </span>
                                <span class="att-icons">
                                    @if($notice)
                                        <span class="att-icon att-icon-notice {{ $noticeNew ? 'is-new' : '' }}" title="Nachricht für heute"><i class="fas fa-envelope" aria-hidden="true"></i><span class="sr-only">Nachricht</span></span>
                                    @endif
                                    @if($sick)
                                        <span class="att-icon att-icon-sick" title="Krank gemeldet"><i class="fas fa-notes-medical" aria-hidden="true"></i><span class="sr-only">krank</span></span>
                                    @endif
                                    @if($ags->isNotEmpty())
                                        <span class="att-icon att-icon-ag" title="AG heute: {{ $ags->map(fn ($ag) => $ag->name.' '.$ag->start_time?->format('H:i').'–'.$ag->end_time?->format('H:i'))->implode(', ') }}"><i class="fas fa-users" aria-hidden="true"></i><span class="sr-only">AG heute</span></span>
                                    @endif
                                    @if($status === 'expected')
                                        <span class="att-icon att-icon-expected" title="Laut Abfrage erwartet, noch nicht da"><i class="fas fa-question" aria-hidden="true"></i><span class="sr-only">erwartet</span></span>
                                    @endif
                                </span>
                            </button>
                            <div class="att-tile-info">
                                <span class="att-pickup" @if($pickLabel === '') hidden @endif><i class="fas fa-clock" aria-hidden="true"></i> <span>{{ $pickLabel }}</span></span>
                                <span class="att-gone-at"><i class="fas fa-door-open" aria-hidden="true"></i> gegangen <span data-gone-time>{{ $status === 'gone' ? $checkIn?->checked_out_at?->format('H:i') : '' }}</span></span>
                                @if($ags->isNotEmpty())
                                    <span class="att-ag-text"><i class="fas fa-users" aria-hidden="true"></i> {{ $ags->first()->name }} {{ $ags->first()->start_time?->format('H:i') }}–{{ $ags->first()->end_time?->format('H:i') }}</span>
                                @endif
                            </div>
                            <button type="button" class="att-toggle" data-child-id="{{ $child->id }}">
                                <span class="att-toggle-in"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Anmelden</span>
                                <span class="att-toggle-out"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Abmelden</span>
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <div class="ea-card att-empty" id="att-empty" hidden>
        <div class="ea-empty">
            <div class="ea-empty-icon"><i class="fas fa-child" aria-hidden="true"></i></div>
            <p class="font-semibold ea-text mb-1">Keine Kinder für diese Auswahl</p>
            <p class="text-sm mb-0">Filter, Suche oder Anzeigemodus ändern.</p>
        </div>
    </div>

    {{-- Rückgängig-Hinweise --}}
    <div class="att-toasts" id="att-toasts" aria-live="polite"></div>
</div>
</div>

@push('js')
    <script>
        (function () {
            const board = document.getElementById('att-board');
            if (!board) return;

            const UNDO_MS = 5000;
            const NEXT_WINDOW_MIN = 60;
            const REFRESH_MS = 60000;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const urls = {in: board.dataset.urlAnmelden, out: board.dataset.urlAbmelden};
            const serverMode = board.dataset.mode; // present | expected | all

            // ── Einstellungen pro Gerät ───────────────────────────────
            const store = {
                get(key, fallback) {
                    try { return localStorage.getItem('att.' + key) ?? fallback; } catch (e) { return fallback; }
                },
                set(key, value) {
                    try { localStorage.setItem('att.' + key, value); } catch (e) { /* privat/gesperrt */ }
                },
            };
            const state = {
                group: store.get('group', ''),
                cls: store.get('class', ''),
                sort: store.get('sort', 'name'),
                density: store.get('density', 'compact'),
                search: '',
                onlyMissing: false,
            };

            // Ausstehende An-/Abmeldungen: childId → {action, status, timer, toast}
            const pending = new Map();

            // Serverzeit maßgeblich: Abweichung der Geräteuhr einmalig ausgleichen
            const browserMinutes = () => { const d = new Date(); return d.getHours() * 60 + d.getMinutes(); };
            let clockOffset = Number(board.dataset.serverMinutes) - browserMinutes();
            if (clockOffset > 720) clockOffset -= 1440;
            if (clockOffset < -720) clockOffset += 1440;
            if (Math.abs(clockOffset) <= 1) clockOffset = 0;
            const nowMinutes = () => (browserMinutes() + clockOffset + 1440) % 1440;
            const pad = n => String(n).padStart(2, '0');
            const formatMinutes = m => pad(Math.floor(m / 60)) + ':' + pad(m % 60);
            const tiles = () => Array.from(document.querySelectorAll('#groups-container .att-tile'));

            // Status einer Kachel, inklusive noch nicht gespeicherter Wechsel
            function statusOf(tile) {
                const p = pending.get(tile.dataset.childId);
                return p ? p.status : tile.dataset.status;
            }

            // ── Darstellung ───────────────────────────────────────────
            function applyPendingVisuals() {
                tiles().forEach(tile => {
                    const p = pending.get(tile.dataset.childId);
                    tile.classList.toggle('is-pending', !!p);
                    if (p) {
                        tile.dataset.status = p.status;
                        if (p.status === 'gone') {
                            const t = tile.querySelector('[data-gone-time]');
                            if (t && !t.textContent.trim()) t.textContent = formatMinutes(nowMinutes());
                        }
                    }
                });
            }

            function matchesMode(status, tile) {
                if (state.onlyMissing) return status === 'expected';
                const p = pending.get(tile.dataset.childId);
                if (p) return true; // während „Rückgängig“ sichtbar lassen
                if (serverMode === 'present') return status === 'present';
                if (serverMode === 'expected') return status === 'present' || status === 'expected';
                return true;
            }

            function inScope(tile) {
                if (state.group && tile.dataset.group !== state.group) return false;
                if (state.cls && tile.dataset.class !== state.cls) return false;
                return true;
            }

            function sortKey(tile) {
                // Ausstehende Kacheln bleiben an ihrem Platz, damit ein zweiter Tipp dasselbe Kind trifft
                const p = pending.get(tile.dataset.childId);
                const status = p ? p.previous : statusOf(tile);
                const rank = status === 'gone' ? 2 : 0; // Gegangene ans Ende
                if (state.sort === 'time') {
                    const t = tile.dataset.pickFrom || tile.dataset.pickDeadline;
                    return [rank, t === '' || t === undefined ? 9999 : Number(t), tile.dataset.sortName];
                }
                return [rank, 0, tile.dataset.sortName];
            }

            function compare(a, b) {
                const ka = sortKey(a), kb = sortKey(b);
                for (let i = 0; i < ka.length; i++) {
                    if (ka[i] < kb[i]) return -1;
                    if (ka[i] > kb[i]) return 1;
                }
                return 0;
            }

            function isOverdue(tile) {
                const deadline = tile.dataset.pickDeadline;
                return statusOf(tile) === 'present' && deadline !== '' && Number(deadline) < nowMinutes();
            }

            function render() {
                applyPendingVisuals();
                const search = state.search.trim().toLowerCase();
                const counts = {present: 0, expected: 0, gone: 0, sick: 0};
                let anyVisible = false;

                document.querySelectorAll('#groups-container .att-section').forEach(section => {
                    const grid = section.querySelector('.att-grid');
                    const sectionTiles = Array.from(grid.children);
                    sectionTiles.sort(compare).forEach(t => grid.appendChild(t));

                    let visible = 0, present = 0, total = 0;
                    sectionTiles.forEach(tile => {
                        const status = statusOf(tile);
                        const scoped = inScope(tile);
                        if (scoped) {
                            if (status in counts) counts[status]++;
                            if (status === 'present') present++;
                            total++;
                        }
                        const show = search
                            ? tile.dataset.search.includes(search) // Suche über alle Gruppen und Modi
                            : scoped && matchesMode(status, tile);
                        tile.hidden = !show;
                        tile.classList.toggle('is-overdue', isOverdue(tile));
                        if (show) visible++;
                    });

                    section.querySelector('[data-section-present]').textContent = present;
                    section.querySelector('[data-section-total]').textContent = total;
                    section.hidden = visible === 0;
                    if (visible) anyVisible = true;
                });

                Object.entries(counts).forEach(([key, value]) => {
                    const el = board.querySelector('[data-count="' + key + '"]');
                    if (el) el.textContent = value;
                });
                document.getElementById('att-empty').hidden = anyVisible;
                renderNext();
                syncControls();
            }

            function renderNext() {
                const list = document.getElementById('att-next-list');
                const now = nowMinutes();
                const items = tiles()
                    .filter(t => inScope(t) && statusOf(t) === 'present')
                    .map(t => {
                        const from = t.dataset.pickFrom === '' ? null : Number(t.dataset.pickFrom);
                        const deadline = t.dataset.pickDeadline === '' ? null : Number(t.dataset.pickDeadline);
                        const key = from ?? deadline;
                        return {tile: t, key, overdue: deadline !== null && deadline < now};
                    })
                    .filter(i => i.key !== null && (i.overdue || i.key <= now + NEXT_WINDOW_MIN))
                    .sort((a, b) => (b.overdue - a.overdue) || (a.key - b.key));

                list.replaceChildren(...items.map(i => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'att-next-item' + (i.overdue ? ' is-overdue' : '');
                    btn.dataset.target = i.tile.dataset.childId;
                    const name = document.createElement('span');
                    name.className = 'att-next-name';
                    name.textContent = i.tile.querySelector('.att-name').textContent;
                    const time = document.createElement('span');
                    time.className = 'att-next-time';
                    time.textContent = (i.overdue ? 'überfällig · ' : '') + i.tile.dataset.pickLabel;
                    btn.append(name, time);
                    return btn;
                }));
                document.getElementById('att-next').classList.toggle('is-empty', items.length === 0);
            }

            function syncControls() {
                board.classList.toggle('att-density-compact', state.density === 'compact');
                board.classList.toggle('att-density-large', state.density === 'large');
                board.querySelectorAll('[data-filter-group]').forEach(b => {
                    const active = b.dataset.filterGroup === state.group;
                    b.classList.toggle('ea-chip-active', active);
                    b.setAttribute('aria-pressed', active);
                });
                board.querySelectorAll('[data-filter-class]').forEach(b => {
                    const active = b.dataset.filterClass === state.cls;
                    b.classList.toggle('ea-chip-active', active);
                    b.setAttribute('aria-pressed', active);
                });
                board.querySelectorAll('[data-sort]').forEach(b => b.classList.toggle('is-active', b.dataset.sort === state.sort));
                const missing = document.getElementById('att-filter-missing');
                missing.classList.toggle('is-active', state.onlyMissing);
                missing.setAttribute('aria-pressed', state.onlyMissing);
                document.getElementById('att-search-clear').hidden = state.search === '';
            }

            // Ungültige gespeicherte Filter (z. B. Gruppe entfernt) zurücksetzen
            if (state.group && !board.querySelector('[data-filter-group="' + CSS.escape(state.group) + '"]')) state.group = '';
            if (state.cls && !board.querySelector('[data-filter-class="' + CSS.escape(state.cls) + '"]')) state.cls = '';

            // ── An-/Abmelden mit Rückgängig ───────────────────────────
            function send(childId, action, keepalive) {
                return fetch(urls[action].replace(':childId', childId), {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: !!keepalive,
                    headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                }).then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r; });
            }

            function showToast(childId, text) {
                const toast = document.createElement('div');
                toast.className = 'att-toast';
                toast.innerHTML = '<span class="att-toast-text"></span>'
                    + '<button type="button" class="att-toast-undo"><i class="fas fa-undo" aria-hidden="true"></i> Rückgängig</button>'
                    + '<span class="att-toast-bar" aria-hidden="true"></span>';
                toast.querySelector('.att-toast-text').textContent = text;
                toast.querySelector('.att-toast-bar').style.animationDuration = UNDO_MS + 'ms';
                toast.querySelector('.att-toast-undo').addEventListener('click', () => undo(childId));
                document.getElementById('att-toasts').appendChild(toast);
                return toast;
            }

            function commit(childId) {
                const p = pending.get(childId);
                if (!p || p.sending) return;
                p.sending = true;
                p.toast?.remove();
                send(childId, p.action)
                    .then(() => {
                        pending.delete(childId);
                        refreshAttendanceList();
                    })
                    .catch(() => {
                        pending.delete(childId);
                        const tile = document.querySelector('.att-tile[data-child-id="' + childId + '"]');
                        if (tile) tile.dataset.status = p.previous;
                        render();
                        alert('Die Anwesenheit konnte nicht gespeichert werden. Bitte erneut versuchen.');
                    });
            }

            function undo(childId) {
                const p = pending.get(childId);
                if (!p || p.sending) return;
                clearTimeout(p.timer);
                p.toast?.remove();
                pending.delete(childId);
                const tile = document.querySelector('.att-tile[data-child-id="' + childId + '"]');
                if (tile) tile.dataset.status = p.previous;
                render();
            }

            board.addEventListener('click', function (event) {
                const toggle = event.target.closest('.att-toggle');
                if (toggle) {
                    const tile = toggle.closest('.att-tile');
                    const childId = tile.dataset.childId;
                    if (pending.has(childId)) { undo(childId); return; } // zweiter Tipp = Rückgängig
                    const previous = tile.dataset.status;
                    const action = previous === 'present' ? 'out' : 'in';
                    const name = tile.dataset.firstName;
                    const p = {action, previous, status: action === 'out' ? 'gone' : 'present'};
                    p.toast = showToast(childId, name + (action === 'out' ? ' abgemeldet' : ' angemeldet'));
                    p.timer = setTimeout(() => commit(childId), UNDO_MS);
                    pending.set(childId, p);
                    render();
                    return;
                }

                const next = event.target.closest('.att-next-item');
                if (next) {
                    const tile = document.querySelector('.att-tile[data-child-id="' + next.dataset.target + '"]');
                    if (tile) {
                        tile.hidden = false;
                        tile.scrollIntoView({behavior: 'smooth', block: 'center'});
                        tile.classList.add('is-flash');
                        setTimeout(() => tile.classList.remove('is-flash'), 1600);
                    }
                    return;
                }

                const groupBtn = event.target.closest('[data-filter-group]');
                if (groupBtn) { state.group = groupBtn.dataset.filterGroup; store.set('group', state.group); render(); return; }

                const classBtn = event.target.closest('[data-filter-class]');
                if (classBtn) { state.cls = classBtn.dataset.filterClass; store.set('class', state.cls); render(); return; }

                const sortBtn = event.target.closest('[data-sort]');
                if (sortBtn) { state.sort = sortBtn.dataset.sort; store.set('sort', state.sort); render(); return; }

                if (event.target.closest('#att-filter-missing')) { state.onlyMissing = !state.onlyMissing; render(); return; }

                if (event.target.closest('#att-density')) {
                    state.density = state.density === 'compact' ? 'large' : 'compact';
                    store.set('density', state.density);
                    render();
                    return;
                }

                if (event.target.closest('#att-search-clear')) {
                    const input = document.getElementById('att-search');
                    input.value = '';
                    state.search = '';
                    render();
                    input.focus();
                }
            });

            document.getElementById('att-search').addEventListener('input', function () {
                state.search = this.value;
                render();
            });

            // Beim Verlassen/Neuladen ausstehende Wechsel noch speichern
            function flushPending() {
                pending.forEach((p, childId) => {
                    if (p.sending) return;
                    clearTimeout(p.timer);
                    p.sending = true;
                    send(childId, p.action, true).catch(() => {});
                });
            }
            window.addEventListener('pagehide', flushPending);

            // ── Uhr und Aktualisierung ────────────────────────────────
            function tick() {
                document.getElementById('att-clock').textContent = formatMinutes(nowMinutes());
            }
            setInterval(() => { tick(); render(); }, 30000);
            tick();

            setInterval(() => {
                if (document.hidden) return;
                if (document.getElementById('childModal')?.classList.contains('show')) return;
                refreshAttendanceList();
            }, REFRESH_MS);

            document.addEventListener('anwesenheit:list-updated', render);

            render();
        })();
    </script>
@endpush
