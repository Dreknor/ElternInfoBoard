{{-- Dashboard-Widget: Rückmeldestatus (Lehrkraft-Perspektive) --}}
@if($authorFeedbackStats && $authorFeedbackStats->count() > 0)
<div class="col-12 mb-4">
    <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
        <div class="px-4 py-3 border-b"
             style="background: linear-gradient(to right, var(--color-widget-accent-from), var(--color-widget-accent-to)); border-color: var(--color-widget-accent-border);">
            <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
                <i class="fas fa-chart-pie"></i>
                Rückmeldestatus meiner Nachrichten
            </h5>
        </div>
        <div class="p-4">
            @foreach($authorFeedbackStats as $stat)
            <div class="mb-4 pb-3 {{ !$loop->last ? 'border-b' : '' }}" style="{{ !$loop->last ? 'border-color: var(--color-card-border);' : '' }}">
                <div class="d-flex justify-content-between text-sm mb-2">
                    <a href="{{ url('post/'.$stat['post_id']) }}"
                       class="font-semibold text-decoration-none"
                       style="color: var(--color-text-primary);"
                       onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-from')"
                       onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-text-primary')">
                        {{ \Illuminate\Support\Str::limit($stat['header'], 50) }}
                    </a>
                    <span class="font-mono" style="color: var(--color-text-secondary);">{{ $stat['responded'] }}/{{ $stat['total'] }}</span>
                </div>

                {{-- Fortschrittsbalken --}}
                <div class="w-full rounded-full h-3 mb-2" style="background: var(--color-card-border);">
                    @php
                        $percentage = $stat['total'] > 0 ? round($stat['responded'] / $stat['total'] * 100) : 0;
                    @endphp
                    <div class="h-3 rounded-full transition-all duration-500 {{ $percentage >= 80 ? 'bg-green-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-red-400') }}"
                         style="width: {{ $percentage }}%">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center text-xs">
                    <span style="color: var(--color-text-secondary);">{{ $percentage }}% beantwortet</span>
                    @if($stat['overdue'] > 0)
                        <span class="text-red-600 font-semibold">
                            <i class="fas fa-exclamation-triangle"></i> {{ $stat['overdue'] }} überfällig
                        </span>
                    @elseif($percentage >= 100)
                        <span class="text-green-600 font-semibold">
                            <i class="fas fa-check-circle"></i> Vollständig
                        </span>
                    @endif
                </div>

                @if($stat['deadline'])
                    <div class="text-xs mt-1" style="color: var(--color-text-secondary);">
                        <i class="far fa-clock"></i> Frist: {{ $stat['deadline']->format('d.m.Y') }}
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

