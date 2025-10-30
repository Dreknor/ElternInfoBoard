@if(!$nachricht->is_archived and $nachricht->rueckmeldung->pflicht == 1 and ($nachricht->users->unique('email')->count() - $nachricht->users()->doesnthave('sorgeberechtigter2')->count())> 1)
    @php
        $totalUsers = $nachricht->users->unique('email')->count() - $nachricht->users()->doesnthave('sorgeberechtigter2')->count();
        $responses = $nachricht->userRueckmeldung->groupBy('users_id')->count();
        $percentage = round(($responses / $totalUsers) * 100, 2);
        $remaining = $totalUsers - $responses;
    @endphp

    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-sm"></i>
                </div>
                <h6 class="text-sm font-semibold text-gray-900 mb-0">Rückmeldungs-Status</h6>
            </div>
            <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full">
                {{$responses}} / {{$totalUsers}}
            </span>
        </div>

        <!-- Progress Bar -->
        <div class="relative">
            <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden shadow-inner">
                <div class="h-full rounded-full transition-all duration-500 ease-out flex items-center justify-center text-xs font-bold text-white
                    @if($percentage < 30) bg-gradient-to-r from-red-500 to-red-600
                    @elseif($percentage < 70) bg-gradient-to-r from-yellow-500 to-orange-500
                    @else bg-gradient-to-r from-green-500 to-green-600
                    @endif"
                    style="width: {{$percentage}}%"
                    id="progress_{{$nachricht->id}}">
                    @if($percentage >= 15)
                        <span class="drop-shadow-sm">{{$percentage}}%</span>
                    @endif
                </div>
            </div>
            @if($percentage < 15 && $percentage > 0)
                <span class="absolute left-2 top-0.5 text-xs font-bold text-gray-600">{{$percentage}}%</span>
            @endif
        </div>

        <!-- Status Text -->
        <div class="mt-3 flex items-center justify-between">
            <p class="text-sm text-gray-600 mb-0">
                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                <span class="font-medium">{{$percentage}}%</span> der erforderlichen Rückmeldungen sind eingegangen
            </p>
            @if($remaining > 0)
                <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded">
                    Noch {{$remaining}} ausstehend
                </span>
            @endif
        </div>

        <!-- Deadline Warning -->
        @if($nachricht->rueckmeldung->ende->diffInDays(\Carbon\Carbon::now()) <= 3 && $nachricht->rueckmeldung->ende->isFuture())
            <div class="mt-3 flex items-center gap-2 p-2 bg-red-50 border border-red-200 rounded-lg">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
                <p class="text-xs text-red-700 font-medium mb-0">
                    Frist endet in {{$nachricht->rueckmeldung->ende->diffInDays(\Carbon\Carbon::now())}} Tag(en)!
                </p>
            </div>
        @endif
    </div>
@endif

