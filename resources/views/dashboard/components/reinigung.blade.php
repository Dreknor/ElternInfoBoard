{{-- Dashboard-Widget: Eigene Reinigungsdienste der Familie (nächste 4 Wochen) --}}
<div class="col-12 mb-4">
    <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
        <div class="px-4 py-3 border-b d-flex justify-content-between align-items-center"
             style="background: linear-gradient(to right, var(--color-widget-warning-from), var(--color-widget-warning-to)); border-color: var(--color-widget-warning-border);">
            <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                <i class="fas fa-broom"></i>
                Ihr Reinigungsdienst
            </h5>
            <a href="{{ url('reinigung') }}" class="text-sm font-semibold text-decoration-none" style="color: var(--color-widget-header-text);">
                Zum Plan <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="p-4 space-y-2">
            @foreach($reinigungen as $reinigung)
                @php
                    $isCurrentWeek = $reinigung->weekStart()->isSameDay(now()->startOfWeek());
                @endphp
                <div class="p-3 rounded-lg"
                     style="background: {{ $isCurrentWeek ? 'var(--color-widget-warning-bg)' : 'var(--color-widget-body-bg)' }};
                            border: 1px solid {{ $isCurrentWeek ? 'var(--color-widget-warning-from)' : 'var(--color-card-border)' }};">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="font-semibold" style="color: var(--color-text-primary);">
                            <i class="far fa-calendar-alt mr-1" style="color: var(--color-widget-warning-from);"></i>
                            {{ $reinigung->weekStart()->format('d.m.') }} - {{ $reinigung->weekEnd()->format('d.m.Y') }}
                            @if($isCurrentWeek)
                                <span class="badge badge-warning badge-pill ml-1">diese Woche</span>
                            @endif
                        </span>
                        <span class="text-sm" style="color: var(--color-text-secondary);">
                            <i class="fas fa-clipboard-list mr-1"></i>{{ $reinigung->aufgabe ?: 'Reinigungsdienst' }}
                        </span>
                    </div>
                    @if($reinigung->bemerkung)
                        <ul class="text-sm mt-2 mb-0 pl-0" style="color: var(--color-text-secondary); list-style: none;">
                            @foreach($reinigung->bemerkungPunkte() as $punkt)
                                <li><i class="far fa-square mr-1"></i>{{ $punkt }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
