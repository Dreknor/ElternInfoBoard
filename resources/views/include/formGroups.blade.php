{{--
    Empfängerauswahl: Gruppen (nach Bereich, als Chips mit Filter) und – mit
    ['withPersons' => true] und aktiver Einstellung general.allow_individual_recipients –
    einzelne Personen. Sendet gruppen[] (Gruppen-IDs) und personen[] (User-IDs).

    Vorbelegung Gruppen: old('gruppen') bzw. $post / $user / $liste / $selectedGroups.
    Vorbelegung Personen: old('personen') bzw. $liste → $termin → $post.
--}}
@php
    $withRecipients = ($withPersons ?? false) && \App\Services\IndividualRecipients::enabled();

    if (is_array(old('gruppen'))) {
        $preselectedGroups = collect(old('gruppen'))->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (string) $id);
    } else {
        $preselectedGroups = $gruppen->filter(fn ($gruppe) =>
            (isset($post) && $post->groups->contains($gruppe->id)) ||
            (isset($user) && $user->groups->contains($gruppe)) ||
            (isset($liste) && $liste->groups->contains($gruppe)) ||
            (isset($groups) && isset($selectedGroups) && $selectedGroups->contains($gruppe))
        )->map(fn ($gruppe) => (string) $gruppe->id);
    }

    $groupData = $gruppen->map(fn ($gruppe) => [
        'id' => (string) $gruppe->id,
        'name' => $gruppe->name,
        'bereich' => (string) $gruppe->bereich,
        'protected' => (bool) $gruppe->protected,
    ])->values();

    $bereiche = $gruppen->pluck('bereich')->filter()->unique()->sort()->values();
    $ohneBereich = $gruppen->filter(fn ($gruppe) => blank($gruppe->bereich));

    $personOptions = collect();
    $preselectedPersons = collect();
    if ($withRecipients) {
        $personOptions = \App\Services\IndividualRecipients::selectableUsers();

        if (is_array(old('personen'))) {
            $preselectedPersons = collect(old('personen'))->map(fn ($id) => (int) $id);
        } elseif (isset($liste) && $liste instanceof \App\Model\Liste && $liste->exists) {
            $preselectedPersons = $liste->recipientUsers->pluck('id');
        } elseif (isset($termin) && $termin->exists) {
            $preselectedPersons = $termin->recipientUsers->pluck('id');
        } elseif (isset($post) && $post->exists) {
            $preselectedPersons = $post->recipientUsers->pluck('id');
        }
    }
@endphp

<script>
    window.recipientForm ??= function (config) {
        return {
            groups: config.groups,
            selectedGroups: config.selectedGroups,
            persons: config.persons,
            selectedPersons: config.selectedPersons,
            groupFilter: '',
            personQuery: '',
            active: 0,

            // Gruppen
            visible(name) {
                const q = this.groupFilter.trim().toLowerCase();
                return q === '' || name.toLowerCase().includes(q);
            },
            idsOf(bereich) {
                return this.groups.filter(g => g.bereich === bereich).map(g => g.id);
            },
            bereichVisible(bereich) {
                return this.groups.some(g => g.bereich === bereich && this.visible(g.name));
            },
            bereichState(bereich) {
                const ids = this.idsOf(bereich);
                const count = ids.filter(id => this.selectedGroups.includes(id)).length;
                return count === 0 ? 'none' : (count === ids.length ? 'all' : 'some');
            },
            toggleBereich(bereich) {
                const ids = this.idsOf(bereich);
                this.selectedGroups = this.bereichState(bereich) === 'all'
                    ? this.selectedGroups.filter(id => !ids.includes(id))
                    : [...new Set([...this.selectedGroups, ...ids])];
            },
            selectAllUnprotected() {
                const ids = this.groups.filter(g => !g.protected).map(g => g.id);
                this.selectedGroups = [...new Set([...this.selectedGroups, ...ids])];
            },
            clearGroups() {
                this.selectedGroups = [];
            },

            // Personen
            get chosenPersons() {
                return this.selectedPersons
                    .map(id => this.persons.find(p => p.id === id))
                    .filter(Boolean);
            },
            get personMatches() {
                const q = this.personQuery.trim().toLowerCase();
                if (q.length < 2) return [];
                return this.persons
                    .filter(p => !this.selectedPersons.includes(p.id))
                    .filter(p => p.name.toLowerCase().includes(q) || (p.email || '').toLowerCase().includes(q))
                    .slice(0, 6);
            },
            addPerson(person) {
                if (!person || this.selectedPersons.includes(person.id)) return;
                this.selectedPersons.push(person.id);
                this.personQuery = '';
                this.active = 0;
                this.$refs.personSearch.focus();
            },
            removePerson(id) {
                this.selectedPersons = this.selectedPersons.filter(p => p !== id);
            },
            move(step) {
                const n = this.personMatches.length;
                if (n) this.active = (this.active + step + n) % n;
            },

            // Zusammenfassung
            get summary() {
                const parts = [];
                const g = this.selectedGroups.length, p = this.selectedPersons.length;
                if (g) parts.push(g + (g === 1 ? ' Gruppe' : ' Gruppen'));
                if (p) parts.push(p + (p === 1 ? ' Person' : ' Personen'));
                return parts.join(' · ');
            },
        };
    };
</script>

<div class="form-group mb-0"
     x-data="recipientForm({
        groups: @js($groupData),
        selectedGroups: @js($preselectedGroups->values()),
        persons: @js($personOptions),
        selectedPersons: @js($preselectedPersons->values()),
     })">

    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
        <p class="label-control mb-0">
            @if($withRecipients)
                Für wen? <span class="font-normal text-xs" style="color: var(--color-text-muted);">Gruppen und/oder einzelne Personen</span>
            @else
                Für welche Gruppen?
            @endif
        </p>
        <span class="text-xs font-semibold" x-cloak
              :style="summary ? 'color: var(--color-primary);' : 'color: var(--color-text-muted);'"
              x-text="summary ? summary + ' ausgewählt' : 'Noch nichts ausgewählt'"></span>
    </div>

    {{-- Gruppen: Filter und Schnellauswahl --}}
    <div class="flex flex-wrap items-center gap-2 mb-3">
        @if($gruppen->count() > 8)
            <div class="relative flex-1" style="min-width: 10rem;">
                <i class="fas fa-search absolute text-xs" style="left: .75rem; top: 50%; transform: translateY(-50%); color: var(--color-text-muted);"></i>
                <input type="search" x-model="groupFilter" class="form-control" style="padding-left: 2rem;"
                       placeholder="Gruppe suchen …" autocomplete="off" aria-label="Gruppen filtern">
            </div>
        @endif
        <button type="button" class="text-xs font-semibold px-3 py-2 rounded-lg border"
                style="border-color: var(--color-card-border); color: var(--color-text-primary); background: var(--color-body-bg);"
                @click="selectAllUnprotected()">
            <i class="fas fa-check-double"></i> Alle Gruppen
            <span class="font-normal" style="color: var(--color-text-muted);">(außer geschützte)</span>
        </button>
        <button type="button" class="text-xs px-3 py-2 rounded-lg" x-show="selectedGroups.length" x-cloak
                style="color: var(--color-text-muted);" @click="clearGroups()">
            <i class="fas fa-times"></i> Gruppen leeren
        </button>
    </div>

    {{-- Gruppen nach Bereich --}}
    @php
        $sections = $bereiche->map(fn ($bereich) => ['bereich' => $bereich, 'gruppen' => $gruppen->where('bereich', $bereich)]);
        if ($ohneBereich->isNotEmpty()) {
            $sections->push(['bereich' => '', 'gruppen' => $ohneBereich]);
        }
    @endphp

    <div class="space-y-3">
        @foreach($sections as $section)
            <div x-show="bereichVisible(@js((string) $section['bereich']))">
                @if($bereiche->isNotEmpty())
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wide" style="color: var(--color-text-muted);">
                            {{ $section['bereich'] !== '' ? $section['bereich'] : 'Weitere Gruppen' }}
                        </span>
                        @if($section['bereich'] !== '')
                            <button type="button" class="text-xs font-semibold"
                                    style="color: var(--color-primary);"
                                    @click="toggleBereich(@js($section['bereich']))"
                                    x-text="bereichState(@js($section['bereich'])) === 'all' ? 'keine im Bereich' : 'alle im Bereich'">
                                alle im Bereich
                            </button>
                        @endif
                    </div>
                @endif

                <div class="flex flex-wrap gap-2">
                    @foreach($section['gruppen'] as $gruppe)
                        <label class="cursor-pointer select-none" x-show="visible(@js($gruppe->name))"
                               title="{{ $gruppe->protected ? 'Geschützte Gruppe' : $gruppe->name }}">
                            <input type="checkbox" name="gruppen[]" value="{{ $gruppe->id }}"
                                   class="sr-only peer" x-model="selectedGroups"
                                   @checked($preselectedGroups->contains((string) $gruppe->id))>
                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm border transition-colors peer-focus-visible:ring-2"
                                  :style="selectedGroups.includes('{{ $gruppe->id }}')
                                      ? 'background: var(--color-primary); border-color: var(--color-primary); color: #fff;'
                                      : 'background: var(--color-body-bg); border-color: var(--color-card-border); color: var(--color-text-primary);'">
                                <i class="fas fa-check text-[10px]" x-show="selectedGroups.includes('{{ $gruppe->id }}')" x-cloak></i>
                                {{ $gruppe->name }}
                                @if($gruppe->protected)
                                    <i class="fas fa-lock text-[10px] opacity-70"></i>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
        <p class="text-xs mb-0" x-show="groupFilter && !groups.some(g => visible(g.name))" x-cloak
           style="color: var(--color-text-muted);">Keine passende Gruppe.</p>
    </div>

    @if($withRecipients)
        {{-- Einzelne Personen --}}
        <div class="mt-4 pt-3 border-t" style="border-color: var(--color-card-border);">
            <p class="text-xs font-bold uppercase tracking-wide mb-1" style="color: var(--color-text-muted);">
                <i class="fas fa-user-plus"></i> Einzelne Personen
                <span class="font-normal normal-case tracking-normal">(optional, auch ohne Gruppenzugehörigkeit)</span>
            </p>

            <div class="flex flex-wrap gap-2 mb-2" x-show="chosenPersons.length" x-cloak>
                <template x-for="person in chosenPersons" :key="person.id">
                    <span class="inline-flex items-center gap-1 pl-3 pr-1 py-1 rounded-full text-sm"
                          style="background: var(--color-primary); color: #fff;">
                        <i class="fas fa-user text-[10px]"></i>
                        <span x-text="person.name"></span>
                        <input type="hidden" name="personen[]" :value="person.id">
                        <button type="button" class="w-6 h-6 rounded-full hover:opacity-70"
                                :aria-label="person.name + ' entfernen'" @click="removePerson(person.id)">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                </template>
            </div>

            <div class="relative">
                <i class="fas fa-search absolute text-xs" style="left: .75rem; top: 1.1rem; transform: translateY(-50%); color: var(--color-text-muted);"></i>
                <input type="search" x-ref="personSearch" x-model="personQuery"
                       class="form-control" style="padding-left: 2rem;"
                       placeholder="Person suchen (Name oder E-Mail) …" autocomplete="off"
                       aria-label="Person suchen"
                       @input="active = 0"
                       @keydown.arrow-down.prevent="move(1)"
                       @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter.prevent="addPerson(personMatches[active])"
                       @keydown.escape="personQuery = ''">

                {{-- Treffer direkt unter dem Feld (kein schwebendes Dropdown, damit nichts abgeschnitten wird) --}}
                <ul class="mt-1 rounded-lg overflow-hidden border" x-show="personMatches.length" x-cloak
                    style="border-color: var(--color-card-border);">
                    <template x-for="(person, index) in personMatches" :key="person.id">
                        <li>
                            <button type="button" class="w-full text-left px-3 py-2 text-sm flex items-center justify-between gap-2"
                                    :style="index === active ? 'background: var(--color-body-bg);' : ''"
                                    @mouseenter="active = index" @click="addPerson(person)">
                                <span class="min-w-0">
                                    <span class="block truncate" x-text="person.name" style="color: var(--color-text-primary);"></span>
                                    <span class="block truncate text-xs" x-text="person.email" style="color: var(--color-text-muted);"></span>
                                </span>
                                <i class="fas fa-plus text-xs flex-shrink-0" style="color: var(--color-primary);"></i>
                            </button>
                        </li>
                    </template>
                </ul>
                <p class="text-xs mt-1 mb-0" x-show="personQuery.trim().length >= 2 && !personMatches.length" x-cloak
                   style="color: var(--color-text-muted);">Keine passende Person gefunden.</p>
            </div>
        </div>
    @endif
</div>
