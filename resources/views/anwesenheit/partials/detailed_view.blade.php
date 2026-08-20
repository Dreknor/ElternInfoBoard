<style>
    /* Verhindert, dass list-items beim Mehrspalten-Layout zerrissen werden */
    .group-col ul.list-group li { break-inside: avoid; }
</style>
<div class="container-fluid">
    @php
        $groupsAndClassesIdentical = $groups->pluck('id')->sort()->values()->all() === $classes->pluck('id')->sort()->values()->all();
        $visibleGroups = $groups->sortBy('name')->filter(function($group) use ($careSettings, $children) {
            return !($careSettings->hide_groups_when_empty && $children->where('group_id', $group->id)->count() == 0);
        });
    @endphp

    {{-- Gruppen-Filter-Leiste --}}
    <div class="mb-2 d-flex flex-wrap align-items-center" style="gap: 0.4rem;">
        <small class="text-muted mr-1"><i class="fas fa-filter"></i> Gruppen:</small>
        @foreach($visibleGroups as $group)
            <button type="button"
                    class="btn btn-sm btn-primary group-toggle-btn"
                    data-group-id="{{ $group->id }}"
                    title="Gruppe ein-/ausblenden">
                {{ $group->name }}
            </button>
        @endforeach
        <button type="button" class="btn btn-sm btn-outline-secondary ml-1" id="show-all-groups" title="Alle Gruppen anzeigen">
            <i class="fas fa-eye"></i> Alle
        </button>
        <span class="ml-2 text-muted" style="border-left: 1px solid #ccc; padding-left: 0.6rem;">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="sort-schickzeit-btn" title="Nach Schickzeit sortieren">
                <i class="fas fa-sort-numeric-down"></i> Schickzeit
            </button>
        </span>
    </div>

    <div class="row" id="groups-container">
        @foreach($visibleGroups as $group)
            <div class="col-lg-3 col-md-6 mb-1 group-col" data-group-id="{{ $group->id }}">
                <div class="card">
                    <div class="card-header bg-primary text-white"
                         style="position: sticky; top: 0; z-index: 1; padding: 0.5rem; background-color: #007bff !important; color: #fff !important;">
                        <span class="badge badge-warning float-right">{{ $children->where('group_id', $group->id)->count() }}</span>

                        <h3 style="margin: 0;">{{ $group->name }}</h3>
                    </div>
                    <div class="card-body" style="padding: 0.5rem;">
                        @if($groupsAndClassesIdentical)
                            @php
                                $sortedChildren = $children->where('group_id', $group->id)?->sortBy('last_name');
                            @endphp
                            <ul class="list-group" style="margin: 0;">
                                @forelse($sortedChildren as $child)
                                    @php
                                        $childData = array_merge(
                                            $child->toArray(),
                                            [
                                                'checked_in' => $child->checkedIn() ? 'true' : 'false',
                                                'schickzeiten' => $child->getSchickzeitenForToday()?->toArray(),
                                                'regular_schickzeiten' => $child->regularSchickzeiten?->toArray(),
                                                'mandates' => $child->mandates?->toArray(),
                                               'parents' => $child->parents?->flatMap(function($u) use ($sorg2Users) {
                                                   $list = [['name' => $u->name, 'email' => $u->email]];
                                                   if ($u->sorg2 && isset($sorg2Users[$u->sorg2])) {
                                                       $partner = $sorg2Users[$u->sorg2];
                                                       $list[] = ['name' => $partner->name, 'email' => $partner->email];
                                                   }
                                                   return $list;
                                               })->unique('email')->values()->toArray(),
                                           ]
                                       );
                                   @endphp
                                   <li class="list-group-item custom-list-item d-flex align-items-center child-item {{ $loop->index % 2 == 0 ? 'list-item-odd' : '' }} @if($child->checkedIn()) detail-checkedIn @else detail-checkedOut @endif"
                                       data-child='@json($childData)'
                                       data-notices='@json($child->hasNotice())'
                                       style="padding: 0.5rem;">
                                       <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-2 d-flex justify-content-center align-items-center" style="gap: 4px; flex-wrap: wrap;">
                                                    @if($child->should_be_today() and !$child->checkedIn())
                                                        <div title="Anwesenheit noch nicht bestätigt"
                                                             style="width:26px;height:26px;border-radius:50%;background-color:#17a2b8;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                            <i class="fas fa-question" style="font-size:12px;"></i>
                                                        </div>
                                                    @endif

                                                    @if($child->hasNotice())
                                                        <div title="Nachricht vorhanden"
                                                             class="@if($child->noticeToday()->isNew()) blink @endif"
                                                             style="width:26px;height:26px;border-radius:50%;background-color:#fd7e14;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                            <i class="fas fa-envelope" style="font-size:11px;"></i>
                                                        </div>
                                                    @endif

                                                    @if($child->krankmeldungToday())
                                                        <div class="badge badge-danger" style="font-size:0.7rem;">
                                                            <i class="fas fa-ban"></i> Krank
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-auto d-flex justify-content-center align-items-center name">
                                                    {{ $child->last_name }}, {{ $child->first_name }}
                                                </div>
                                                <div class="col-auto d-flex justify-content-center align-items-center ">
                                                    @if($child->getSchickzeitenForToday()?->count() > 0 and $child->checkedIn())
                                                        @foreach($child->getSchickzeitenForToday()?->sortBy('type') as $schickzeit)
                                                            @php
                                                                $currentTime = now();
                                                                $backgroundClass = 'badge badge-';
                                                                $text_size = 'text-smaller';

                                                                if($schickzeit->type == 'ab' and (isset($schickzeit->time_ab) && $currentTime->isBefore($schickzeit?->time_ab))) {
                                                                    $backgroundClass .= 'success';
                                                                    $text_size = 'text-smaller';
                                                                } elseif($schickzeit->type == 'ab' and ($schickzeit->time_ab && $currentTime->isAfter($schickzeit->time_ab)) and ($schickzeit->time_spaet && $currentTime->isBefore($schickzeit->time_spaet))) {
                                                                    $backgroundClass .= 'warning';
                                                                    $text_size = 'text-great';
                                                                } elseif($schickzeit->type == 'ab' and $schickzeit->time_spaet and $currentTime->isAfter($schickzeit->time_spaet)) {
                                                                    $backgroundClass .= 'danger';
                                                                    $text_size = 'text-medium';
                                                                } elseif($schickzeit->type == 'genau' and $schickzeit->time and $currentTime->isBefore($schickzeit->time)) {
                                                                    $backgroundClass .= 'success';
                                                                    $text_size = 'text-smaller';
                                                                } elseif($schickzeit->type == 'genau' and $schickzeit->time and $currentTime->isAfter($schickzeit->time)) {
                                                                    $backgroundClass .= 'danger';
                                                                    $text_size = 'text-great';
                                                                } else {
                                                                    $backgroundClass .= 'primary';
                                                                    $text_size = 'text-medium';
                                                                }

                                                            @endphp
                                                            @if($schickzeit->type == 'ab')
                                                                @if($schickzeit->time_ab != '')
                                                                    <span class="{{ $backgroundClass }} text-smaller">
                                                                         ab {{ $schickzeit->time_ab?->format('H:i') }}
                                                                    </span>
                                                                @endif
                                                                @if($schickzeit->time_spaet)
                                                                    <span class="{{ $backgroundClass }} {{$text_size}}">
                                                                             {{ $schickzeit->time_spaet?->format('H:i') }} (spät.)
                                                                        </span>
                                                                @endif
                                                            @else
                                                                <span class="{{ $backgroundClass }} {{$text_size}}">
                                                                       <i class="fa-regular fa-clock mr-1"></i>  {{ $schickzeit->time?->format('H:i') }}
                                                                    </span>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                    @if(!($isFerientag ?? false) && $child->arbeitsgemeinschaften_today()->isNotEmpty())
                                                        @foreach($child->arbeitsgemeinschaften_today() as $ag)
                                                            <span class="badge badge-primary ml-1">
                                                                <i class="fas fa-users"></i> AG
                                                                {{ $ag->name }}, <br> {{ $ag->start_time->format('H:i') }} - {{ $ag->end_time->format('H:i') }}
                                                            </span>
                                                        @endforeach
                                                    @endif
                                                </div>

                                            </div>


                                        </div>

                                    </li>
                                @empty
                                    @if($careSettings->show_message_on_empty_group)
                                        <li class="list-group-item bg-gradient-directional-light-yellow" style="padding: 0.5rem;">
                                            Keine Kinder in dieser Klassenstufe
                                        </li>
                                    @endif
                                @endforelse
                            </ul>
                        @else
                            @foreach($classes as $class)
                                @if($careSettings->hide_groups_when_empty and $children->where('group_id', $group->id)->where('class_id', $class->id)->count() == 0)
                                    @continue
                                @endif
                                <h4 class="bg-gradient-directional-grey-blue text-white p-2" style="position: sticky; top: 60px; z-index: 1; margin: 0.5rem 0;">
                                    {{ $class->name }}  <span class="badge badge-primary float-right">{{ $children->where('group_id', $group->id)->where('class_id', $class->id)->count() }}</span>
                                </h4>
                                @php
                                    $sortedClassChildren = $children->where('group_id', $group->id)->where('class_id', $class->id)?->sortBy('last_name');
                                @endphp
                                <ul class="list-group" style="margin: 0;">
                                    @forelse($sortedClassChildren as $child)
                                        @php
                                            $childData = array_merge(
                                                $child->toArray(),
                                                [
                                                    'checked_in' => $child->checkedIn() ? 'true' : 'false',
                                                    'schickzeiten' => $child->getSchickzeitenForToday()?->toArray(),
                                                    'regular_schickzeiten' => $child->regularSchickzeiten?->toArray(),
                                                    'mandates' => $child->mandates?->toArray(),
                                                    'parents' => $child->parents?->flatMap(function($u) use ($sorg2Users) {
                                                        $list = [['name' => $u->name, 'email' => $u->email]];
                                                        if ($u->sorg2 && isset($sorg2Users[$u->sorg2])) {
                                                            $partner = $sorg2Users[$u->sorg2];
                                                            $list[] = ['name' => $partner->name, 'email' => $partner->email];
                                                        }
                                                        return $list;
                                                    })->unique('email')->values()->toArray(),
                                                ]
                                            );
                                        @endphp
                                        <li class="list-group-item custom-list-item d-flex align-items-center child-item {{ $loop->index % 2 == 0 ? 'list-item-odd' : '' }} @if($child->checkedIn()) detail-checkedIn @else detail-checkedOut @endif"
                                            data-child='@json($childData)'
                                            data-notices='@json($child->hasNotice())'
                                            style="padding: 0.5rem;">
                                            <div class="container-fluid">
                                                <div class="row">
                                                    <div class="col-2 d-flex justify-content-center align-items-center" style="gap: 4px; flex-wrap: wrap;">
                                                        @if($child->should_be_today() and !$child->checkedIn())
                                                            <div title="Anwesenheit noch nicht bestätigt"
                                                                 style="width:26px;height:26px;border-radius:50%;background-color:#17a2b8;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                                <i class="fas fa-question" style="font-size:12px;"></i>
                                                            </div>
                                                        @endif

                                                        @if($child->hasNotice())
                                                            <div title="Nachricht vorhanden"
                                                                 class="@if($child->noticeToday()->isNew()) blink @endif"
                                                                 style="width:26px;height:26px;border-radius:50%;background-color:#fd7e14;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                                <i class="fas fa-envelope" style="font-size:11px;"></i>
                                                            </div>
                                                        @endif

                                                        @if($child->krankmeldungToday())
                                                            <div class="badge badge-danger" style="font-size:0.7rem;">
                                                                <i class="fas fa-ban"></i> Krank
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="col-auto d-flex justify-content-center align-items-center name">
                                                        {{ $child->last_name }}, {{ $child->first_name }}
                                                    </div>
                                                    <div class="col-auto d-flex justify-content-center align-items-center ">
                                                        @if($child->getSchickzeitenForToday()?->count() > 0 and $child->checkedIn())
                                                            @foreach($child->getSchickzeitenForToday()?->sortBy('type') as $schickzeit)
                                                                @php
                                                                    $currentTime = now();
                                                                    $backgroundClass = 'badge badge-';
                                                                    $text_size = 'text-smaller';

                                                                    if($schickzeit->type == 'ab' and (isset($schickzeit->time_ab) && $currentTime->isBefore($schickzeit?->time_ab))) {
                                                                        $backgroundClass .= 'success';
                                                                        $text_size = 'text-smaller';
                                                                    } elseif($schickzeit->type == 'ab' and ($schickzeit->time_ab && $currentTime->isAfter($schickzeit->time_ab)) and ($schickzeit->time_spaet && $currentTime->isBefore($schickzeit->time_spaet))) {
                                                                        $backgroundClass .= 'warning';
                                                                        $text_size = 'text-great';
                                                                    } elseif($schickzeit->type == 'ab' and $schickzeit->time_spaet and $currentTime->isAfter($schickzeit->time_spaet)) {
                                                                        $backgroundClass .= 'danger';
                                                                        $text_size = 'text-medium';
                                                                    } elseif($schickzeit->type == 'genau' and $schickzeit->time and $currentTime->isBefore($schickzeit->time)) {
                                                                        $backgroundClass .= 'success';
                                                                        $text_size = 'text-smaller';
                                                                    } elseif($schickzeit->type == 'genau' and $schickzeit->time and $currentTime->isAfter($schickzeit->time)) {
                                                                        $backgroundClass .= 'danger';
                                                                        $text_size = 'text-great';
                                                                    } else {
                                                                        $backgroundClass .= 'primary';
                                                                        $text_size = 'text-medium';
                                                                    }

                                                                @endphp
                                                                @if($schickzeit->type == 'ab')
                                                                    @if($schickzeit->time_ab != '')
                                                                        <span class="{{ $backgroundClass }} text-smaller">
                                                                             ab {{ $schickzeit->time_ab?->format('H:i') }}
                                                                        </span>
                                                                    @endif
                                                                    @if($schickzeit->time_spaet)
                                                                        <span class="{{ $backgroundClass }} {{$text_size}}">
                                                                                 {{ $schickzeit->time_spaet?->format('H:i') }} (spät.)
                                                                            </span>
                                                                    @endif
                                                                @else
                                                                    <span class="{{ $backgroundClass }} {{$text_size}}">
                                                                           <i class="fa-regular fa-clock mr-1"></i>  {{ $schickzeit->time?->format('H:i') }}
                                                                        </span>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                        @if(!($isFerientag ?? false) && $child->arbeitsgemeinschaften_today()->isNotEmpty())
                                                            @foreach($child->arbeitsgemeinschaften_today() as $ag)
                                                                <span class="badge badge-primary ml-1">
                                                                    <i class="fas fa-users"></i> AG
                                                                    {{ $ag->name }}, <br> {{ $ag->start_time->format('H:i') }} - {{ $ag->end_time->format('H:i') }}
                                                                </span>
                                                            @endforeach
                                                        @endif
                                                    </div>

                                                </div>


                                            </div>

                                        </li>
                                    @empty
                                        @if($careSettings->show_message_on_empty_group)
                                            <li class="list-group-item bg-gradient-directional-light-yellow" style="padding: 0.5rem;">
                                                Keine Kinder in dieser Klassenstufe
                                            </li>
                                        @endif
                                    @endforelse
                                </ul>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
(function () {
    const STORAGE_KEY = 'detailedView_hiddenGroups';

    function getHidden() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch (e) { return []; }
    }
    function setHidden(arr) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(arr));
    }

    function applyState() {
        const hidden = getHidden();
        const allCols = document.querySelectorAll('.group-col');

        allCols.forEach(function (col) {
            const id = col.dataset.groupId;
            const isHidden = hidden.includes(id);
            col.style.display = isHidden ? 'none' : '';
        });

        document.querySelectorAll('.group-toggle-btn').forEach(function (btn) {
            const id = btn.dataset.groupId;
            const isHidden = hidden.includes(id);
            btn.classList.toggle('btn-primary', !isHidden);
            btn.classList.toggle('btn-outline-primary', isHidden);
        });

        // Einzelgruppen-Modus: volle Breite + Mehrspalten-Layout
        const visibleCols = Array.from(allCols).filter(function (col) {
            return col.style.display !== 'none';
        });
        const singleGroup = visibleCols.length === 1;

        allCols.forEach(function (col) {
            // Bootstrap-Breitenklassen zurücksetzen
            col.classList.toggle('col-12', singleGroup);
            col.classList.toggle('col-lg-3', !singleGroup);
            col.classList.toggle('col-md-6', !singleGroup);

            // Mehrspalten-Layout für Kinderlisten
            col.querySelectorAll('ul.list-group').forEach(function (ul) {
                if (singleGroup) {
                    ul.style.display = 'block';
                    ul.style.columnCount = '3';
                    ul.style.columnGap = '0.5rem';
                } else {
                    ul.style.display = '';
                    ul.style.columnCount = '';
                    ul.style.columnGap = '';
                }
            });
        });
    }

    document.querySelectorAll('.group-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.dataset.groupId;
            let hidden = getHidden();
            if (hidden.includes(id)) {
                hidden = hidden.filter(function (x) { return x !== id; });
            } else {
                hidden.push(id);
            }
            setHidden(hidden);
            applyState();
        });
    });

    document.getElementById('show-all-groups').addEventListener('click', function () {
        setHidden([]);
        applyState();
    });

    // --- Schickzeit-Sortierung ---
    const SORT_KEY = 'detailedView_sortBySchickzeit';

    // Originalreihenfolge einmalig beim Laden speichern
    document.querySelectorAll('.group-col ul.list-group').forEach(function (ul) {
        Array.from(ul.querySelectorAll('li.child-item')).forEach(function (li, idx) {
            li.dataset.originalIndex = idx;
        });
    });

    function getSchickzeitSort() {
        return localStorage.getItem(SORT_KEY) === 'true';
    }

    function getEarliestMinutes(childData) {
        const sz = childData.schickzeiten || [];
        if (!sz.length) return Infinity;
        let earliest = Infinity;
        sz.forEach(function (s) {
            let t = null;
            if (s.type === 'genau' && s.time) {
                const d = new Date(s.time);
                if (!isNaN(d)) t = d.getHours() * 60 + d.getMinutes();
            } else if (s.time_ab) {
                const p = s.time_ab.split(':');
                if (p.length >= 2) t = parseInt(p[0]) * 60 + parseInt(p[1]);
            }
            if (t !== null && t < earliest) earliest = t;
        });
        return earliest;
    }

    function applySortState() {
        const active = getSchickzeitSort();
        const btn = document.getElementById('sort-schickzeit-btn');
        btn.classList.toggle('btn-secondary', active);
        btn.classList.toggle('btn-outline-secondary', !active);

        document.querySelectorAll('.group-col ul.list-group').forEach(function (ul) {
            const items = Array.from(ul.querySelectorAll('li.child-item'));
            if (!items.length) return;
            if (active) {
                items.sort(function (a, b) {
                    const da = JSON.parse(a.dataset.child);
                    const db = JSON.parse(b.dataset.child);
                    return getEarliestMinutes(da) - getEarliestMinutes(db);
                });
            } else {
                items.sort(function (a, b) {
                    return parseInt(a.dataset.originalIndex) - parseInt(b.dataset.originalIndex);
                });
            }
            items.forEach(function (li) { ul.appendChild(li); });
        });
    }

    document.getElementById('sort-schickzeit-btn').addEventListener('click', function () {
        localStorage.setItem(SORT_KEY, String(!getSchickzeitSort()));
        applySortState();
    });

    applyState();
    applySortState();
})();
</script>
