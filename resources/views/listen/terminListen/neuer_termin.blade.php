{{-- Dialog: neue Termine anlegen (nur Verwaltung) --}}
@php $feld = 'w-full px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-emerald-400'; $feldStyle = 'border: 1px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);'; @endphp
<div x-show="neuerTermin" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="neuer-termin-titel">
    <div class="absolute inset-0 bg-black/50" @click="neuerTermin = false"></div>
    <form method="post" action="{{ url("listen/termine/$liste->id/store") }}"
          class="relative w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 space-y-4 max-h-[90vh] overflow-y-auto"
          style="background: var(--color-card-bg);" x-data="{ weekly: '0' }">
        @csrf
        <h2 id="neuer-termin-titel" class="text-lg font-bold mb-0" style="color: var(--color-text-primary);">
            <i class="fas fa-calendar-plus mr-1" aria-hidden="true"></i> Neue Termine anlegen
        </h2>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="termin" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">Datum <span class="text-red-500">*</span></label>
                <input type="date" name="termin" id="termin" required class="{{ $feld }}" style="{{ $feldStyle }}">
            </div>
            <div>
                <label for="zeit" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">Beginn <span class="text-red-500">*</span></label>
                <input type="time" name="zeit" id="zeit" required class="{{ $feld }}" style="{{ $feldStyle }}">
            </div>
            <div>
                <label for="duration" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">Dauer (Minuten)</label>
                <input type="number" name="duration" id="duration" min="1" value="{{ $liste->duration }}" class="{{ $feld }}" style="{{ $feldStyle }}">
            </div>
            <div>
                <label for="repeat" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">Anzahl</label>
                <input type="number" name="repeat" id="repeat" min="1" step="1" value="1" class="{{ $feld }}" style="{{ $feldStyle }}">
            </div>
        </div>
        <fieldset>
            <legend class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">Wiederholung</legend>
            <div class="flex flex-col gap-1 text-sm" style="color: var(--color-text-primary);">
                <label class="inline-flex items-center gap-2"><input type="radio" name="weekly" value="0" x-model="weekly"> direkt hintereinander (z. B. Sprechstunde)</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="weekly" value="1" x-model="weekly"> wöchentlich zur gleichen Zeit</label>
            </div>
        </fieldset>
        <div>
            <label for="comment" class="block text-sm font-semibold mb-1" style="color: var(--color-text-primary);">Anmerkung</label>
            <input type="text" name="comment" id="comment" placeholder="z. B. Turnhalle A" class="{{ $feld }}" style="{{ $feldStyle }}">
        </div>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" @click="neuerTermin = false"
                    class="px-4 py-2.5 rounded-lg font-medium" style="border: 1px solid var(--color-card-border); color: var(--color-text-primary);">
                Abbrechen
            </button>
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-semibold text-white bg-emerald-600 hover:bg-emerald-700">
                <i class="fas fa-check" aria-hidden="true"></i> Speichern
            </button>
        </div>
    </form>
</div>
