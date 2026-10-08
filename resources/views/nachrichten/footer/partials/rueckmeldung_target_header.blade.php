{{-- Kopfzeile eines Antwortziels bei Rückmeldungen pro Kind (Konzept §6.5, E2/E7) --}}
@if($rueckmeldungTarget->isChild())
    <div class="flex flex-wrap items-center justify-between gap-2 mt-4 mb-2 px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg">
        <div class="flex items-center gap-2 text-sm font-semibold text-indigo-900">
            <i class="fas fa-child"></i>
            <span>Rückmeldung für {{ $rueckmeldungTarget->label() }}</span>
        </div>
        <div class="text-xs">
            @if($rueckmeldungTarget->isAnswered())
                <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 rounded">
                    <i class="fas fa-check mr-1"></i>
                    beantwortet von {{ $rueckmeldungTarget->answeredBy() }}
                    am {{ $rueckmeldungTarget->answers->sortBy('created_at')->first()->created_at->format('d.m.Y') }}
                </span>
            @else
                <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 rounded">
                    <i class="fas fa-hourglass-half mr-1"></i> offen
                </span>
            @endif
        </div>
        @unless($rueckmeldungTarget->canAnswer)
            <div class="w-full text-xs text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                Für dieses Kind können nur Sorgeberechtigte antworten.
            </div>
        @endunless
    </div>
@endif
