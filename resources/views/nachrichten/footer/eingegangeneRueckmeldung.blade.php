@can('view rueckmeldungen')
    <div class="container-fluid">
        <table class="table table-hover table-striped table-bordered">
            <thead>
                <tr>
                    <th>
                        Benutzer
                    </th>
                    <th>
                        Datum
                    </th>
                    <th>
                        Rückmeldung
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($nachricht->userRueckmeldung as $rueckmeldung)
                    <tr>
                        <td>
                            {{$rueckmeldung->user->name}}
                        </td>
                        <td>
                            {{$rueckmeldung->updated_at->format('d.m.Y H:i')}}
                        </td>
                        <td class="w-75">
                            <button class="btn btn-outline-info btn-block btnShow" data-toggle="collapse" data-target="#{{$rueckmeldung->id}}_rueckmeldungen_text">
                                <i class="fa fa-eye"></i>
                                Text anzeigen
                            </button>
                            <div id="{{$rueckmeldung->id."_rueckmeldungen_text"}}" class="collapse">
                                {!! $rueckmeldung->text !!}
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endcan
