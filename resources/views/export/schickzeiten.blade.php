<table>
    <thead>
    <tr>
        <th></th>
        <th>Montag ({{\Carbon\Carbon::today()->startOfWeek()->modify('monday')->format('d.m.Y')}})</th>
        <th>Dienstag ({{\Carbon\Carbon::today()->startOfWeek()->modify('tuesday')->format('d.m.Y')}})</th>
        <th>Mittwoch ({{\Carbon\Carbon::today()->startOfWeek()->modify('wednesday')->format('d.m.Y')}})</th>
        <th>Donnerstag ({{\Carbon\Carbon::today()->startOfWeek()->modify('thursday')->format('d.m.Y')}})</th>
        <th>Freitag ({{\Carbon\Carbon::today()->startOfWeek()->modify('friday')->format('d.m.Y')}})</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <th>
                {{$stunde}} Uhr
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 1 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('monday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (ab)
                            @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (spät.)
                            @else
                            (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 2 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('tuesday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 3 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('wednesday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 4 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('thursday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 5 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('friday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
        </tr>
        <tr>
            <th>
                {{$stunde}}.30 Uhr
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 1 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('monday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 2 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('tuesday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 3 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('wednesday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 4 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('thursday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->filter(function ($item) use ($stunde ){
                        //Prüfe Wochentag oder Datum
                        if ($item->weekday == 5 or $item->specific_date == \Carbon\Carbon::today()->startOfWeek()->modify('friday')->format('Y-m-d')){
                            if (!is_null($item->time) and $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_ab) and $item->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            } elseif (!is_null($item->time_spaet) and $item->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59'))){
                                return $item;
                            }
                        }
                        //return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    @if(!is_null($schickzeit->child)) {{$schickzeit->child->first_name}}, {{$schickzeit->child->last_name}} @else {{$schickzeit->child_name}} @endif
                    @if (!is_null($schickzeit->time) and $schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab')
                        @if(!is_null($schickzeit->time_ab) and  $schickzeit->time_ab->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (ab)
                        @elseif(!is_null($schickzeit->time_spaet) and  $schickzeit->time_spaet->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde.':59')))
                            (spät.)
                        @else
                        (ab)
                        @endif
                    @elseif ($schickzeit->type == 'spät.')
                        (spät.)
                    @endif
                    <br>
                @endforeach
            </th>
        </tr>
    </tbody>
</table>
