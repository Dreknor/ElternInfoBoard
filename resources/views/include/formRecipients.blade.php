{{--
    Auswahl einzelner Personen (zusätzlich zu Gruppen), nur wenn
    general.allow_individual_recipients aktiv ist. Sendet personen[] mit.
    Vorbelegung: old('personen') → $liste → $termin → $post.
--}}
@php
    $recipientOptions = \App\Services\IndividualRecipients::selectableUsers();

    if (is_array(old('personen'))) {
        $recipientSelected = collect(old('personen'))->map(fn ($id) => (int) $id);
    } elseif (isset($liste) && $liste instanceof \App\Model\Liste && $liste->exists) {
        $recipientSelected = $liste->recipientUsers->pluck('id');
    } elseif (isset($termin) && $termin->exists) {
        $recipientSelected = $termin->recipientUsers->pluck('id');
    } elseif (isset($post) && $post->exists) {
        $recipientSelected = $post->recipientUsers->pluck('id');
    } else {
        $recipientSelected = collect();
    }
@endphp

<script>
    window.recipientPicker ??= function (options, selected) {
        return {
            options,
            selected,
            query: '',
            open: false,
            active: 0,
            get chosen() {
                return this.selected
                    .map(id => this.options.find(o => o.id === id))
                    .filter(Boolean);
            },
            get matches() {
                const q = this.query.trim().toLowerCase();
                if (q.length < 2) return [];
                return this.options
                    .filter(o => !this.selected.includes(o.id))
                    .filter(o => o.name.toLowerCase().includes(q) || (o.email || '').toLowerCase().includes(q))
                    .slice(0, 8);
            },
            add(option) {
                if (!option || this.selected.includes(option.id)) return;
                this.selected.push(option.id);
                this.query = '';
                this.active = 0;
                this.$refs.search.focus();
            },
            remove(id) {
                this.selected = this.selected.filter(s => s !== id);
            },
            move(step) {
                if (!this.matches.length) return;
                this.active = (this.active + step + this.matches.length) % this.matches.length;
            },
        };
    };
</script>

<div class="mt-4 pt-3 border-t" style="border-color: var(--color-card-border);"
     x-data="recipientPicker(@js($recipientOptions), @js($recipientSelected->values()))">
    <p class="label-control mb-1">
        <i class="fas fa-user-plus" style="color: var(--color-text-muted);"></i>
        Einzelne Personen <span class="font-normal text-xs" style="color: var(--color-text-muted);">(optional, zusätzlich zu den Gruppen)</span>
    </p>

    {{-- Ausgewählte Personen --}}
    <div class="flex flex-wrap gap-2 mb-2" x-show="chosen.length" x-cloak>
        <template x-for="person in chosen" :key="person.id">
            <span class="inline-flex items-center gap-1 pl-3 pr-1 py-1 rounded-full text-sm"
                  style="background: var(--color-body-bg); border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                <span x-text="person.name"></span>
                <input type="hidden" name="personen[]" :value="person.id">
                <button type="button" class="w-6 h-6 rounded-full hover:opacity-70"
                        :aria-label="person.name + ' entfernen'" @click="remove(person.id)">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
        </template>
    </div>

    {{-- Suche --}}
    <div class="relative" @click.outside="open = false">
        <input type="text" x-ref="search" x-model="query"
               class="form-control"
               placeholder="Name oder E-Mail eingeben …"
               autocomplete="off"
               @focus="open = true" @input="open = true; active = 0"
               @keydown.arrow-down.prevent="move(1)"
               @keydown.arrow-up.prevent="move(-1)"
               @keydown.enter.prevent="add(matches[active])"
               @keydown.escape="open = false">

        <ul x-show="open && matches.length" x-cloak
            class="absolute z-20 left-0 right-0 mt-1 rounded-lg shadow-lg overflow-hidden"
            style="background: var(--color-card-bg, #fff); border: 1px solid var(--color-card-border);">
            <template x-for="(option, index) in matches" :key="option.id">
                <li>
                    <button type="button" class="w-full text-left px-3 py-2 text-sm"
                            :style="index === active ? 'background: var(--color-body-bg);' : ''"
                            @mouseenter="active = index" @click="add(option)">
                        <span x-text="option.name" style="color: var(--color-text-primary);"></span>
                        <span x-text="option.email" class="block text-xs" style="color: var(--color-text-muted);"></span>
                    </button>
                </li>
            </template>
        </ul>
        <p x-show="open && query.trim().length >= 2 && !matches.length" x-cloak
           class="text-xs mt-1" style="color: var(--color-text-muted);">Keine passende Person gefunden.</p>
    </div>

    <p class="text-xs mt-1 mb-0" style="color: var(--color-text-muted);">
        Ausgewählte Personen sehen den Eintrag auch, wenn sie in keiner der gewählten Gruppen sind.
    </p>
</div>
