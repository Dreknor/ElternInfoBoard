<table>
    <thead>
    <tr>
        <th>
            Name
        </th>
        <th>
            Zeitpunkt
        </th>
        @foreach($rueckmeldung->options as $option)
            <th>
                {{$option->option}}
            </th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($rueckmeldung->userRueckmeldungen as $userRueckmeldung)
        <tr>
            <td>
                {{$userRueckmeldung->user->name}}
            </td>
            <td>
                {{$userRueckmeldung->created_at->format('Y-m-d H:i')}}
            </td>
            @foreach($rueckmeldung->options as $option)
                <th>
                    @if($userRueckmeldung->answers->where('option_id', $option->id)->first() != null)
                        @switch($option->type)
                            @case('text')
                                {{$userRueckmeldung->answers->where('option_id', $option->id)->first()->answer}}
                                @break
                            @case('textbox')
                                {{$userRueckmeldung->answers->where('option_id', $option->id)->first()->answer}}
                                @break
                            @case('check')
                                1
                                @break
                        @endswitch
                    @endif
                </th>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
