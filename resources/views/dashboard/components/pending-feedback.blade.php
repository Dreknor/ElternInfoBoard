{{-- Dashboard-Widget: Offene Rückmeldungen (Eltern-Perspektive) --}}
@if($pendingFeedback->count() > 0)
<div class="col-12 mb-4">
    <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg shadow p-4">
        <h6 class="font-bold text-amber-800 mb-3">
            <i class="fas fa-exclamation-circle"></i>
            Offene Rückmeldungen ({{ $pendingFeedback->count() }})
        </h6>
        <div class="space-y-2">
            @foreach($pendingFeedback as $item)
            <a href="{{ url('post/'.$item['post_id']) }}"
               class="block p-3 bg-white rounded border border-amber-200 hover:border-amber-500 transition-all duration-200 text-decoration-none">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="font-semibold text-gray-800">{{ $item['header'] }}</span>
                        <div class="text-xs text-gray-500 mt-1">
                            @if($item['type'] === 'lesebestaetigung')
                                <i class="fas fa-eye"></i> Lesebestätigung
                            @else
                                <i class="fas fa-reply"></i> Rückmeldung
                            @endif
                        </div>
                    </div>
                    @if($item['is_overdue'])
                        <span class="badge bg-danger text-white px-2 py-1 rounded text-xs font-semibold">
                            <i class="fas fa-exclamation-triangle"></i> Überfällig
                        </span>
                    @else
                        <span class="badge bg-warning text-dark px-2 py-1 rounded text-xs font-semibold">
                            <i class="far fa-clock"></i> Frist: {{ $item['deadline']->format('d.m.Y') }}
                        </span>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

