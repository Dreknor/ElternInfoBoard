@for($x=Carbon\Carbon::today(); $x< $targetDate; $x->addDay())
    @if(!$x->isWeekend())
        <div class="card border border-dark">
            <div class="card-header" id="heading{{$x->format('Ymd')}}">
                <h6>
                    Vertretungen für
                    <div class="text-danger d-inline">{{$x->locale('de')->dayName}} </div>
                    , den {{$x->format('d.m.Y')}}
                </h6>
            </div>
            <div id="collapse{{$x->format('Ymd')}}" aria-labelledby="heading{{$x->format('Ymd')}}">
                <div class="card-body">
                    <table class="table table-bordered table-sm table-responsive-sm">
                        <thead class="thead-light">
                        <tr class="">
                            <th class="">Klasse</th>
                            <th class="">Stunde</th>
                            <th class="">Fächer</th>
                            <th class="">Lehrer</th>
                            <th class="">Kommentar</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($vertretungen->filter(function ($vertretung) use ($x) {
                                if (\Carbon\Carbon::make($vertretung->date)->eq($x)){
                                    return $vertretung;
                                }
                            }) as $vertretung)
                            <tr @if(($loop->iteration-1)%2 == 0) class="bg-secondary text-white" @endif>
                                <td class="">
                                    {{\Illuminate\Support\Str::after($vertretung->klasse, ' ')}}
                                </td>
                                <td>
                                    {{$vertretung->stunde}}
                                </td>
                                <td>
                                    {{\Illuminate\Support\Str::limit($vertretung->altFach, 5)}} @if($vertretung->neuFach)
                                        -> {{\Illuminate\Support\Str::limit($vertretung->neuFach,5)}}@endif
                                </td>
                                <td>
                                    @if(!is_null($vertretung->lehrer))
                                        {{\Illuminate\Support\Str::limit(\Illuminate\Support\Str::after($vertretung->lehrer, ' '),4,'...')}}
                                    @endif
                                </td>
                                <td>
                                    {{$vertretung->comment}}
                                </td>
                            </tr>

                        @endforeach
                        <tr class="">

                        </tr>
                        @foreach($mitteilungen->filter(function ($mitteilungen) use ($x) {
                                if ((\Carbon\Carbon::make($mitteilungen->date_start)->eq($x) and \Carbon\Carbon::make($mitteilungen->date_end) == null) or (\Carbon\Carbon::make($mitteilungen->date_start)->lessThanOrEqualTo($x) and \Carbon\Carbon::make($mitteilungen->date_end) != null and \Carbon\Carbon::make($mitteilungen->date_end)->greaterThanOrEqualTo($x))){
                                    return $mitteilungen;
                                }
                            }) as $dailyNews)
                            <tr>
                                <th colspan="6" class="border-outline-info">
                                    {{$dailyNews->news}}
                                </th>
                            </tr>
                        @endforeach
                        @if(!is_null($absences))
                            <tr>
                                <th colspan="6">
                                    @if($absences->count() > 1)
                                        Es fehlen:
                                    @else
                                        Es fehlt:
                                    @endif
                                    @foreach($absences->filter(function ($absence) use ($x) {
                                        if (Carbon\Carbon::make($absence->start)->lte($x) and Carbon\Carbon::make($absence->end)->gte($x)){
                                            return $absence;
                                        }
                                    }) as $absence)
                                        {{$absence->user}}@if(!$loop->last),@endif
                                    @endforeach
                                </th>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endfor
