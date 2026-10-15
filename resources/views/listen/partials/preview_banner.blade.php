{{-- Hinweis in der Vorschau: Liste wird so angezeigt, wie Eltern sie nach der Veröffentlichung sehen --}}
<div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-amber-900">
    <div class="flex items-start gap-3">
        <i class="fas fa-eye mt-1 text-amber-500"></i>
        <div>
            <p class="font-semibold mb-0">Vorschau</p>
            <p class="text-sm text-amber-800 mb-0">
                So sehen Eltern diese Liste@if($liste->active == 0), sobald sie veröffentlicht ist@endif. Schaltflächen sind in der Vorschau deaktiviert.
            </p>
        </div>
    </div>
    <a href="{{ url('listen/' . $liste->id) }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-amber-100 text-amber-900 font-medium rounded-lg border border-amber-300 transition-colors duration-200">
        <i class="fas fa-times"></i>
        Vorschau beenden
    </a>
</div>
