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
                @foreach($schickzeiten->where('weekday', '=', 1)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 2)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 3)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 4)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 5)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':00'), \Carbon\Carbon::parse($stunde.':29'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '00') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
        </tr>
        <tr>
            <th>
                {{$stunde}}.30 Uhr
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 1)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde+1 .':00'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}}) @endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 2)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde+1 .':00'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 3)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde+1 .':00'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 4)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde+1 .':00'));
                        }) as $schickzeit)
                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
            <th>
                @foreach($schickzeiten->where('weekday', '=', 5)->filter(function ($item) use ($stunde){
                        return $item->time->between(\Carbon\Carbon::parse($stunde.':30'), \Carbon\Carbon::parse($stunde+1 .':00'));
                        }) as $schickzeit)

                    {{$schickzeit->child_name}}
                    @if($schickzeit->time->format('i') != '30') ({{$schickzeit->time->format('i')}})@endif
                    @if($schickzeit->type == 'ab') (ab) @elseif ($schickzeit->type == 'spät.') (spät.) @endif <br>
                @endforeach
            </th>
        </tr>
    </tbody>
</table>
