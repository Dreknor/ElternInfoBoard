{{-- Bearbeiten / Löschen eines Elternrat-Termins (nur wenn ElternratEvent::canBeManagedBy) --}}
<div class="flex items-center gap-2 flex-shrink-0">
    <a href="{{ route('elternrat.events.edit', $event) }}"
       class="px-3 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors shadow-md"
       title="Termin bearbeiten" aria-label="Termin bearbeiten">
        <i class="fas fa-edit"></i>
    </a>
    <form action="{{ route('elternrat.events.destroy', $event) }}" method="POST" class="inline-block"
          onsubmit="return confirm('Termin wirklich löschen?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors shadow-md"
                title="Termin löschen" aria-label="Termin löschen">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</div>
