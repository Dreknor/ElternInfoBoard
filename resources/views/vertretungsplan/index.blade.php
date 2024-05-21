@extends('layouts.app')
@section('title') - Vertretungsplan @endsection

@section('content')
    <div class="content d-none d-lg-block">
        @for($x=Carbon\Carbon::today(); $x< $targetDate; $x->addDay())
            @if(!$x->isWeekend())
                <div class="card border border-dark">
                    <div class="card-header" id="heading{{$x->format('Ymd')}}">
                        <h6>
                            Vertretungen für
                            <div class="text-danger d-inline">{{$x->locale('de')->dayName}} </div>
                            ,
                            den {{$x->format('d.m.Y')}} @if(count($weeks->where('week', $x->copy()->startOfWeek())) > 0 )
                                )
                                ({{$weeks->where('week', $x->copy()->startOfWeek())->first()?->type}} - Woche)
                            @endif
                        </h6>
                    </div>
                    <div id="collapse{{$x->format('Ymd')}}" aria-labelledby="heading{{$x->format('Ymd')}}">
                        <div class="card-body">
                            <div class="">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                    <tr class="">
                                        <th class="d-lg-table-cell">Klasse</th>
                                        <th class="d-lg-table-cell">Stunde</th>
                                        <th class="d-lg-table-cell">Fächer</th>
                                        <th class="d-none d-lg-table-cell">Lehrer</th>
                                        <th class="d-none d-lg-table-cell">Kommentar</th>
                                    </tr>
                                    <tr class="d-lg-none">
                                        <th class="d-lg-table-cell">Lehrer</th>
                                        <th class="d-lg-table-cell" colspan="2">Kommentar</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($vertretungen->filter(function ($vertretung) use ($x) {
                                        if (\Carbon\Carbon::make($vertretung->date)->eq($x)){
                                            return $vertretung;
                                        }
                                    }) as $vertretung)
                                        <tr @if(($loop->iteration-1)%2 == 0) class="bg-secondary text-white" @endif>
                                            <td class="d-lg-table-cell">{{$vertretung->group->name}}</td>
                                            <td class="d-lg-table-cell">{{$vertretung->stunde}}</td>
                                            <td class="d-lg-table-cell">{{$vertretung->altFach}} @if($vertretung->neuFach)
                                                    -> {{$vertretung->neuFach}}@endif</td>
                                            <td class="d-none d-lg-table-cell">{{$vertretung->lehrer}}</td>
                                            <td class="d-none d-lg-table-cell">{{$vertretung->comment}}</td>
                                        </tr>
                                        <tr class="d-lg-none @if(($loop->iteration-1)%2 == 0) bg-secondary text-white @endif">
                                            <td class="d-lg-table-cell">{{$vertretung->lehrer}}</td>
                                            <td class="d-lg-table-cell" colspan="2">{{$vertretung->comment}}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="">

                                    </tr>
                                    @foreach($mitteilungen->filter(function ($mitteilungen) use ($x) {
                                        if ((\Carbon\Carbon::make($mitteilungen->start)->eq($x) and \Carbon\Carbon::make($mitteilungen->end) == null)
                                        or (\Carbon\Carbon::make($mitteilungen->start)->lessThanOrEqualTo($x)
                                        and \Carbon\Carbon::make($mitteilungen->end) != null and \Carbon\Carbon::make($mitteilungen->end)->greaterThanOrEqualTo($x))){
                                            return $mitteilungen;
                                        }
                                    }) as $dailyNews)
                                        <tr>
                                            <th colspan="6" class="border-outline-info">
                                                {{$dailyNews->news}}
                                            </th>
                                        </tr>
                                    @endforeach
                                    @if($absences and $absences->count() > 0)
                                        <tr>
                                            <th colspan="6">
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
                                                    {{$absence->name}} @if($absence->reason != "") ({{$absence->reason}}) @endif @if(!$loop->last),@endif
                                                @endforeach
                                            </th>
                                        </tr>
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endfor
    </div>

    <div class="content d-block d-lg-none">
        @include('vertretungsplan.vertretungMobil')
    </div>
@endsection
