{{-- Dashboard-Widget: Rückmeldestatus (Lehrkraft-Perspektive) --}}
@if($authorFeedbackStats && $authorFeedbackStats->count() > 0)
<div class="col-12 mb-4">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-4 py-3 border-b border-purple-800">
            <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                <i class="fas fa-chart-pie"></i>
                Rückmeldestatus meiner Nachrichten
            </h5>
        </div>
        <div class="p-4">
            @foreach($authorFeedbackStats as $stat)
            <div class="mb-4 pb-3 {{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                <div class="d-flex justify-content-between text-sm mb-2">
                    <a href="{{ url('post/'.$stat['post_id']) }}" class="font-semibold text-gray-800 text-decoration-none hover:text-blue-600">
                        {{ \Illuminate\Support\Str::limit($stat['header'], 50) }}
                    </a>
                    <span class="text-gray-600 font-mono">{{ $stat['responded'] }}/{{ $stat['total'] }}</span>
                </div>

                {{-- Fortschrittsbalken --}}
                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                    @php
                        $percentage = $stat['total'] > 0 ? round($stat['responded'] / $stat['total'] * 100) : 0;
                    @endphp
                    <div class="h-3 rounded-full transition-all duration-500 {{ $percentage >= 80 ? 'bg-green-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-red-400') }}"
                         style="width: {{ $percentage }}%">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center text-xs">
                    <span class="text-gray-500">
                        {{ $percentage }}% beantwortet
                    </span>
                    @if($stat['overdue'] > 0)
                        <span class="text-red-600 font-semibold">
                            <i class="fas fa-exclamation-triangle"></i> {{ $stat['overdue'] }} überfällig
                        </span>
                    @else
                        @if($percentage >= 100)
                            <span class="text-green-600 font-semibold">
                                <i class="fas fa-check-circle"></i> Vollständig
                            </span>
                        @endif
                    @endif
                </div>

                @if($stat['deadline'])
                    <div class="text-xs text-gray-400 mt-1">
                        <i class="far fa-clock"></i> Frist: {{ $stat['deadline']->format('d.m.Y') }}
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

