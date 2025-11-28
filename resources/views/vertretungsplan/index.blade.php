@extends('layouts.app')
@section('title') - Vertretungsplan @endsection

@section('content')
    <div class="container-fluid px-4 py-3 hidden lg:block space-y-4">
        @for($x=Carbon\Carbon::today(); $x< $targetDate; $x->addDay())
            @if(!$x->isWeekend())
                <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800" id="heading{{$x->format('Ymd')}}">
                        <h6 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                            <i class="fas fa-calendar-day"></i>
                            <span>
                                Vertretungen für
                                <span class="text-red-200">{{$x->locale('de')->dayName}}</span>,
                                den {{$x->format('d.m.Y')}}
                                @if(count($weeks->where('week', $x->copy()->startOfWeek())) > 0 )
                                    ({{$weeks->where('week', $x->copy()->startOfWeek())->first()?->type}} - Woche)
                                @endif
                            </span>
                        </h6>
                    </div>
                    <div id="collapse{{$x->format('Ymd')}}" aria-labelledby="heading{{$x->format('Ymd')}}">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-100 border-b-2 border-gray-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Klasse</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Stunde</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fächer</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Lehrer</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Kommentar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @php
                                        $tagesVertretungen = $vertretungen->filter(function ($vertretung) use ($x) {
                                            if (\Carbon\Carbon::make($vertretung->date)->eq($x)){
                                                return $vertretung;
                                            }
                                        });
                                    @endphp

                                    @forelse($tagesVertretungen as $vertretung)
                                        <tr class="@if(($loop->iteration-1)%2 == 0) bg-blue-50 @else bg-white @endif hover:bg-blue-100 transition-colors">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{$vertretung->group->name}}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{$vertretung->stunde}}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{$vertretung->altFach}}
                                                @if($vertretung->neuFach)
                                                    <i class="fas fa-arrow-right text-blue-600 mx-1"></i> {{$vertretung->neuFach}}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{$vertretung->lehrer}}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">{{$vertretung->comment}}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center">
                                                <div class="flex flex-col items-center gap-2 text-green-700">
                                                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                                                    <span class="text-base font-medium">Keine Vertretungen für diesen Tag</span>
                                                    <span class="text-sm text-gray-500">Der Unterricht findet planmäßig statt.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse

                                    @foreach($mitteilungen->filter(function ($mitteilungen) use ($x) {
                                        if ((\Carbon\Carbon::make($mitteilungen->start)->eq($x) and \Carbon\Carbon::make($mitteilungen->end) == null)
                                        or (\Carbon\Carbon::make($mitteilungen->start)->lessThanOrEqualTo($x)
                                        and \Carbon\Carbon::make($mitteilungen->end) != null and \Carbon\Carbon::make($mitteilungen->end)->greaterThanOrEqualTo($x))){
                                            return $mitteilungen;
                                        }
                                    }) as $dailyNews)
                                        <tr>
                                            <td colspan="5" class="px-4 py-3 bg-cyan-50 border-l-4 border-cyan-500">
                                                <div class="flex items-start gap-2">
                                                    <i class="fas fa-info-circle text-cyan-600 mt-1"></i>
                                                    <span class="text-sm font-medium text-cyan-800">{{$dailyNews->news}}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if($absences and $absences->count() > 0)
                                        <tr>
                                            <td colspan="5" class="px-4 py-3 bg-amber-50 border-l-4 border-amber-500">
                                                <div class="flex items-start gap-2">
                                                    <i class="fas fa-user-times text-amber-600 mt-1"></i>
                                                    <span class="text-sm font-medium text-amber-800">
                                                        @if($absences->count() > 1)
                                                            Es fehlen:
                                                        @else
                                                            Es fehlt:
                                                        @endif
                                                        @foreach($absences->filter(function ($absence) use ($x) {
                                                            if (Carbon\Carbon::make($absence->start_date)->lte($x)
                                                            and Carbon\Carbon::make($absence->end_date)->gte($x)){
                                                                return $absence;
                                                            }
                                                        }) as $absence)
                                                            {{$absence->name}}
                                                            @if($absence->reason != "")
                                                                ({{$absence->reason}})
                                                            @endif
                                                            @if(!$loop->last)
                                                                ,
                                                            @endif
                                                        @endforeach
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endfor
    </div>

    <div class="block lg:hidden">
        @include('vertretungsplan.vertretungMobil')
    </div>
@endsection
