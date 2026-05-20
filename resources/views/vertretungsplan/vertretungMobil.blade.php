<div class="px-3 py-4 space-y-4">
    @for($x=Carbon\Carbon::today(); $x< $targetDate; $x->addDay())
        @if(!$x->isWeekend())
            <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                {{-- Header --}}
                <div class="px-4 py-3"
                     style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to))">
                    <div class="flex items-center gap-2" style="color: var(--color-widget-header-text)">
                        <i class="fas fa-calendar-day text-lg"></i>
                        <div class="flex-1">
                            <div class="font-bold text-base">
                                {{$x->locale('de')->dayName}}
                            </div>
                            <div class="text-sm" style="color: rgba(255,255,255,0.75)">
                                {{$x->format('d.m.Y')}}
                                @if(count($weeks->where('week', $x->copy()->startOfWeek())) > 0 )
                                    <span style="color: rgba(255,255,255,0.6)">
                                        ({{$weeks->where('week', $x->copy()->startOfWeek())->first()?->type}}-Woche)
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="divide-y divide-gray-200">
                    @php
                        $tagesVertretungen = $vertretungen->filter(function ($vertretung) use ($x) {
                            if (\Carbon\Carbon::make($vertretung->date)->eq($x)){
                                return $vertretung;
                            }
                        });
                    @endphp

                    @forelse($tagesVertretungen as $vertretung)
                        <div class="p-4" style="{{ ($loop->iteration-1)%2 == 0 ? 'background-color: var(--color-primary-light)' : 'background-color: #ffffff' }}">
                            {{-- Klasse und Stunde --}}
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-700">
                                        Klasse {{$vertretung->group->name}}
                                    </span>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                      style="background-color: var(--color-primary-light); color: var(--color-primary)">
                                    {{$vertretung->stunde}}. Std.
                                </span>
                            </div>

                            {{-- Fächer --}}
                            <div class="mb-2">
                                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Fach</div>
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="font-medium text-gray-800">{{$vertretung->altFach}}</span>
                                    @if($vertretung->neuFach)
                                        <i class="fas fa-arrow-right" style="color: var(--color-primary)"></i>
                                        <span class="font-medium" style="color: var(--color-text-success, #15803d)">{{$vertretung->neuFach}}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Lehrer --}}
                            @if($vertretung->lehrer)
                                <div class="mb-2">
                                    <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Lehrer</div>
                                    <div class="flex items-center gap-2 text-sm text-gray-700">
                                        <i class="fas fa-user text-gray-400"></i>
                                        <span>{{$vertretung->lehrer}}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Kommentar --}}
                            @if($vertretung->comment)
                                <div>
                                    <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Hinweis</div>
                                    <div class="flex items-start gap-2 text-sm text-gray-600 bg-yellow-50 border-l-4 border-yellow-400 p-2 rounded">
                                        <i class="fas fa-comment-dots text-yellow-600 mt-0.5"></i>
                                        <span>{{$vertretung->comment}}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-6">
                            <div class="flex flex-col items-center gap-3 text-center">
                                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                                </div>
                                <div>
                                    <div class="text-base font-semibold text-green-700 mb-1">
                                        Keine Vertretungen
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Der Unterricht findet planmäßig statt.
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforelse

                    {{-- Mitteilungen --}}
                    @foreach($mitteilungen->filter(function ($mitteilungen) use ($x) {
                        if ((\Carbon\Carbon::make($mitteilungen->start)->eq($x) and \Carbon\Carbon::make($mitteilungen->end) == null)
                        or (\Carbon\Carbon::make($mitteilungen->start)->lessThanOrEqualTo($x)
                        and \Carbon\Carbon::make($mitteilungen->end) != null and \Carbon\Carbon::make($mitteilungen->end)->greaterThanOrEqualTo($x))){
                            return $mitteilungen;
                        }
                    }) as $dailyNews)
                        <div class="p-4 bg-cyan-50 border-l-4 border-cyan-500">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-cyan-100 flex items-center justify-center">
                                    <i class="fas fa-info-circle text-cyan-600"></i>
                                </div>
                                <div class="flex-1 pt-1">
                                    <div class="text-xs text-cyan-700 uppercase tracking-wide font-semibold mb-1">
                                        Mitteilung
                                    </div>
                                    <div class="text-sm text-cyan-900">
                                        {{$dailyNews->news}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Abwesenheiten --}}
                    @if($absences and $absences->count() > 0)
                        @php
                            $dayAbsences = $absences->filter(function ($absence) use ($x) {
                                if (Carbon\Carbon::make($absence->start_date)->lte($x)
                                and Carbon\Carbon::make($absence->end_date)->gte($x)){
                                    return $absence;
                                }
                            });
                        @endphp

                        @if($dayAbsences->count() > 0)
                            <div class="p-4 bg-amber-50 border-l-4 border-amber-500">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                                        <i class="fas fa-user-times text-amber-600"></i>
                                    </div>
                                    <div class="flex-1 pt-1">
                                        <div class="text-xs text-amber-700 uppercase tracking-wide font-semibold mb-1">
                                            @if($dayAbsences->count() > 1)
                                                Abwesende Lehrkräfte
                                            @else
                                                Abwesende Lehrkraft
                                            @endif
                                        </div>
                                        <div class="text-sm text-amber-900">
                                            @foreach($dayAbsences as $absence)
                                                <div class="mb-1">
                                                    <span class="font-medium">{{$absence->name}}</span>
                                                    @if($absence->reason != "")
                                                        <span class="text-amber-700">({{$absence->reason}})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    @endfor
</div>

