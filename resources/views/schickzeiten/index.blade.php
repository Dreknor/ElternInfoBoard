@extends('layouts.app')
@section('title') - Schickzeiten @endsection

@section('content')
        <div class="container-fluid">
            <p class="alert alert-info">
                <b>Im Test:</b> Das System zur Anmeldung und Abmeldung von Kindern im Hort, die Erfassung der tagesaktuellen Schickzeiten und Nachrichten befinden sich derzeit in der Testphase. <br>
                 Ob diese Funktionen dauerhaft eingeführt werden, steht noch nicht fest.<br><br>
                Bitte beachten Sie, dass es zu Fehlern kommen kann. Sollten Sie Fehler entdecken, so melden Sie uns dies bitte.
            </p>
            <div class="row">
                @foreach($children as $child)
                    <div class="col-auto">
                        <div class="card ">
                            <div class="card-header @if($child->checkedIn()) bg-gradient-directional-teal text-white @else bg-gradient-directional-warning @endif">
                                <h6 class="card-title">
                                    {{$child->first_name}} {{$child->last_name}}
                                </h6>
                            </div>
                            <div class="card-body">
                                @if(!$child->checkedIn() and $child->checkIns()->where('date', today())->first())
                                    <p>
                                        {{$child->checkIns()->where('date', today())->first()?->updated_at?->format('H:i')}}
                                        Uhr abgemeldet
                                    </p>
                                @elseif($child->checkedIn())
                                    <p>
                                        <i class="fas fa-user-check text-success"></i> derzeit angemeldet
                                        @if($child->getSchickzeitenForToday()->count() > 0 and $child->checkedIn())
                                            @foreach($child->getSchickzeitenForToday() as $schickzeit)
                                                <br>Schickzeit:
                                                @if($schickzeit->type == 'genau')
                                                    {{$schickzeit->time?->format('H:i')}} Uhr
                                                @else
                                                    @if(!is_null($schickzeit->time_ab))
                                                        ab
                                                    @endif
                                                    {{$schickzeit->time_ab?->format('H:i')}}
                                                    @if(!is_null($schickzeit->time_ab) && !is_null($schickzeit->time_spaet))
                                                        -
                                                    @endif
                                                    @if(!is_null($schickzeit->time_spaet))
                                                        spät.
                                                    @endif
                                                    {{$schickzeit->time_spaet?->format('H:i')}} Uhr
                                                @endif
                                            @endforeach
                                        @elseif($child->getSchickzeitenForToday()->count() == 0 and $child->checkedIn())
                                            <br>keine Schickzeiten
                                        @else

                                        @endif
                                    </p>
                                @else
                                    heute nicht angemeldet
                                @endif
                            </div>
                            <div class="card-footer border-top">
                                <ul class="list-group">
                                    @forelse($child->schickzeiten->where('specific_date', '!=', NULL) as $schickzeit)
                                        <li class="list-group-item">
                                            <div class="row">
                                                <div class="col-10">
                                                    <b>
                                                        {{$schickzeit->specific_date->format('d.m.Y')}}:
                                                    </b>
                                                    @if($schickzeit->type =="genau")
                                                        genau {{$schickzeit->time?->format('H:i')}} Uhr
                                                    @else
                                                        ab {{$schickzeit->time_ab?->format('H:i')}}
                                                        Uhr @if(!is_null($schickzeit->time_ab) && $schickzeit->time_spaet)
                                                            -
                                                        @endif {{$schickzeit->time_spaet?->format('H:i')}}
                                                        Uhr
                                                    @endif
                                                </div>
                                                <div class="col-1 pull-right">
                                                        <form
                                                            action="{{route('schickzeiten.destroy', ['schickzeit' => $schickzeit->id])}}"
                                                            method="post"
                                                        class="form-inline">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="submit"
                                                                    class="btn btn-link btn-danger">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>

                                                </div>
                                            </div>
                                        </li>
                                    @empty
                                        <b>Schickzeit</b>
                                        Keine Zeit hinterlegt
                                    @endforelse
                                </ul>
                            </div>
                            <div class="card-footer border-top">
                                <b>Nachrichten</b>
                                @if($child->notice()->Future()->count() > 0)
                                    <ul class="list-group">
                                        @foreach($child->notice()->future()->get() as $notice)
                                            <li class="list-group-item text-black-50">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <b>{{$notice->date->format('d.m.Y')}}:</b> {{$notice->notice}}
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p>Keine Notizen hinterlegt</p>
                                @endif
                            </div>

                            <div class="card-footer border-top">
                                <div class="pull-right">
                                    <button class="round-button" data-toggle="collapse"
                                            href="#noticeCollapse_{{$child->id}}" role="button"
                                            aria-expanded="false" aria-controls="noticeCollapse_{{$child->id}}">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>

                                <div class="collapse" id="noticeCollapse_{{$child->id}}">
                                    <div class="card ">
                                        <div class="card-header bg-gradient-x2-info">
                                            <h6>
                                                Neue tagesaktuelle Schickzeit anlegen
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="container-fluid">
                                                <form
                                                    action="{{route('schickzeiten.store', ['child' => $child->id])}}"
                                                    method="post">
                                                    @csrf
                                                    <div class="form-group">
                                                        <label for="specific_date">Datum</label>
                                                        <input type="date" name="specific_date"
                                                               id="specific_date"
                                                               value="{{old('specific_date', \Carbon\Carbon::now()->format('Y-m-d'))}}"
                                                               class="form-control">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="type">Typ</label>
                                                        <select name="type" class="custom-select"
                                                                id="type">
                                                            <option value="genau">genau</option>
                                                            <option value="ab">ab ... bis ... Uhr
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group" id="genauZeit">
                                                        <label for="time">Zeit</label>
                                                        <input name="time" id="time" type="time"
                                                               class="form-control"
                                                               min="{{$vorgaben->schicken_ab}}"
                                                               max="{{$vorgaben->schicken_bis}}"
                                                               value="{{old('time')}}">
                                                    </div>
                                                    <div class="form-group collapse" id="spaet_row">
                                                        <div class="container-fluid">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label for="ab">ab ...
                                                                        Uhr</label>
                                                                    <input name="time_ab"
                                                                           type="time"
                                                                           class="form-control"
                                                                           min="{{$vorgaben->schicken_ab}}"
                                                                           max="{{$vorgaben->schicken_bis}}"
                                                                           id="spät."
                                                                           value="{{old('time_ab')}}">
                                                                </div>
                                                                <div class="col-md-6 ">
                                                                    <label for="spät.">spätestens
                                                                        (optional)</label>
                                                                    <input name="time_spaet"
                                                                           type="time"
                                                                           class="form-control"
                                                                           min="{{$vorgaben->schicken_ab}}"
                                                                           max="{{$vorgaben->schicken_bis}}"
                                                                           id="spät."
                                                                           value="{{old('time_spaet')}}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <button type="submit"
                                                            class="btn btn-primary btn-block">Neue
                                                        individuelle Schickzeit
                                                        anlegen
                                                    </button>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header bg-gradient-x2-info">
                                            <h6>
                                                Nachricht hinterlegen
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <form class="form-horizontal noticeForm" id="noticeForm_{{$child->id}}">
                                                @csrf
                                                <input type="hidden" name="child_id" value="{{$child->id}}">
                                                <input type="date" name="date"
                                                       value="{{\Carbon\Carbon::now()->format('Y-m-d')}}"
                                                       min="{{\Carbon\Carbon::now()->format('Y-m-d')}}" class="form-control">
                                                <div class="form-group">
                                            <textarea name="notice" id="notice" class="form-control"
                                                      placeholder="Notiz hinzufügen">{{$child->notice->first()?->notice}}</textarea>
                                                </div>
                                                <div class="btn btn-primary btn-block form_submit">Notiz speichern</div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    <div class="container-fluid">
        <div class="row">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">
                                Schickzeiten
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <div class="pull-right">
                                <a href="{{url(('einstellungen'))}}" class="btn btn-primary">Neues Kind anlegen</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body border-top">
                    @include('schickzeiten.infos')
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($children as $child)
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="card">
                                    <div class="card-header bg-gradient-x2-info">
                                        <h5 class="card-title">
                                            {{$child->first_name}} {{$child->last_name}}
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title">regelmäßige Schickzeiten</h6>
                                        <div class="container-fluid">
                                            <ul class="list-group">
                                                @for($x=1;$x<6;$x++)
                                                    <li class="list-group-item">
                                                        <div class="row">
                                                            <div class="col-10">
                                                                <b>
                                                                    {{$weekdays[$x]}}
                                                                </b>
                                                            </div>
                                                            <div class="col-1 ml-auto">
                                                                <div class="btn-group">
                                                                    <a href="#" class="card-link "
                                                                       data-toggle="dropdown"
                                                                       aria-haspopup="true" aria-expanded="false">
                                                                        <i class="fa fa-ellipsis-v"
                                                                           aria-hidden="true"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu">
                                                                        <a href="{{url("schickzeiten/edit/$x/".$child->id)}}"
                                                                           class="dropdown-item">
                                                                            <i class="fa fa-edit"></i> bearbeiten
                                                                        </a>
                                                                        @if($child->schickzeiten->where('weekday', $x)->first())
                                                                            <form
                                                                                action="{{route('schickzeiten.destroy', ['schickzeit' => $child->schickzeiten->where('weekday', $x)->first()->id])}}"
                                                                                method="post" class="form-inline">
                                                                                @csrf
                                                                                @method('delete')
                                                                                <button type="submit"
                                                                                        class="dropdown-item btn-danger">
                                                                                    <i class="fa fa-trash"></i> löschen
                                                                                </button>
                                                                            </form>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                @if($child->schickzeiten->where('weekday', $x)->first())
                                                                    @if($child->schickzeiten->where('weekday', $x)->first()->type == 'genau')
                                                                        {{$child->schickzeiten->where('weekday', $x)->first()->time?->format('H:i')}}
                                                                        Uhr
                                                                    @else
                                                                        {{$child->schickzeiten->where('weekday', $x)->first()->time_ab?->format('H:i')}} @if(!is_null($child->schickzeiten->where('weekday', $x)->first()->time_ab) && $child->schickzeiten->where('weekday', $x)->first()->time_spaet)
                                                                            -
                                                                        @endif {{$child->schickzeiten->where('weekday', $x)->first()->time_spaet?->format('H:i')}}
                                                                        Uhr
                                                                    @endif

                                                                @endif
                                                            </div>
                                                        </div>

                                                    </li>
                                                @endfor
                                            </ul>
                                        </div>
                                    </div>
                                        <div class="card-footer">
                                            <form action="{{url("schickzeiten/$child->id")}}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-danger btn-block">Alle Schickzeiten
                                                    löschen
                                                </button>
                                            </form>
                                        </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">
                        Anwesenheitsabfragen
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($children as $child)
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <div class="card">
                                    <div class="card-header bg-gradient-x2-info">
                                        <h5 class="card-title">
                                            {{$child->first_name}} {{$child->last_name}}
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive-md">
                                            <table class="table table-striped">
                                                <thead>
                                                <tr>
                                                    <th>Datum</th>
                                                    <th>angemeldet?</th>
                                                    <th></th>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @forelse($child->checkIns->sortBy('date') as $checkIn)
                                                    <tr>
                                                        <td>{{$checkIn->date->dayName}}, {{$checkIn->date->format('d.m.Y')}}</td>
                                                        <td>{{$checkIn->should_be ? 'Ja' : 'Nein'}}</td>
                                                        <td>
                                                            @if(!$checkIn->should_be)
                                                                @if(($checkIn->lock_at && $checkIn->lock_at?->gte(now()) or (!$checkIn->lock_at && $checkIn->date->gt(now()))))
                                                                    <form
                                                                        action="{{route('checkIn.anmelden', ['childCheckIn' => $checkIn->id])}}"
                                                                        method="post">
                                                                        @csrf
                                                                        @method('put')
                                                                        <button type="submit" class="btn btn-success btn-sm">
                                                                            <i class="fa fa-check"></i> anmelden
                                                                        </button>
                                                                    </form>
                                                                @else
                                                                    <span class="text-danger">
                                                                    Zeitraum abgelaufen
                                                                </span>
                                                                @endif
                                                            @else
                                                                <form
                                                                    action="{{route('checkIn.abmelden', ['childCheckIn' => $checkIn->id])}}"
                                                                    method="post">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                                        <i class="fa fa-times"></i> abmelden
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($checkIn->lock_at != null)
                                                                Frist: {{$checkIn->lock_at?->format('d.m.Y')}}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3">Keine Anwesenheitsabfragen vorhanden</td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
    </div>


@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script>
        $(document).ready(function () {
            $("#type").change(function () {
                $('#spaet_row').toggle();
                $('#genauZeit').toggle();
            });

            $(".form_submit").click(function () {
                console.log('click');
                var form = $(this).closest('form');
                let notice = form.find('textarea[name="notice"]').val();
                let child_id = form.find('input[name="child_id"]').val();

                var url = "{{route('child.notice.store',['child' => 'child_id'])}}".replace('child_id', child_id);
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "date": form.find('input[name="date"]').val(),
                        "notice": notice
                    },
                    success: function (data) {
                        Swal.fire({
                            title: 'Notiz gespeichert',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (data) {
                        Swal.fire({
                            title: 'Fehler',
                            text: 'Es ist ein Fehler aufgetreten.' + data.responseJSON.message,
                        icon: 'error',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                });
            });

        });


    </script>
    <script>
        document.querySelectorAll('.round-button').forEach(button => {
            button.addEventListener('click', function () {
                this.style.display = 'none';
            });
        });
    </script>
@endpush
