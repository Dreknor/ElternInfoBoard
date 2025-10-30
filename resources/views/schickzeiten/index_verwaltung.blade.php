@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">
                            Anwesenheitsabfragen
                        </h6>
                        <p>
                            Herunterladen des aktuellen Standes der Anwesenheitsabfragen für den angegebenen Zeitraum als Excel-Datei
                        </p>
                    </div>
                    <div class="card-body">
                        <form action="{{route('care.abfrage.anwesenheit.download')}}" method="post">
                            @csrf
                            <div class="form-group">
                                <label for="date_start">Datum von</label>
                                <input type="date" name="date_start" id="date_start" class="form-control" required>
                            </div>
                            <div class="form-group
                            ">
                                <label for="date_end">Datum bis</label>
                                <input type="date" name="date_end" id="date_end" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">download</button>

                        </form>
                    </div>
                    <div class="card-body border-top">
                        <div class="container-fluid">
                                    <h6 class="card-title">
                                        Abfragen Anwesenheit
                                    </h6>
                                    <p>
                                        Aktuelle Anwesenheitsabfragen werden hier angezeigt. Kommentar erscheinen bei der Elternansicht und können kurze Hinweise für den Tag sein.
                                    </p>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                        <tr>
                                            <th class="border-right">
                                                Datum
                                            </th>
                                            <th>
                                                Anzahl
                                            </th>
                                            <th>
                                                Kommentar
                                            </th>
                                            <th>
                                                Aktionen
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($abfragen as $date => $abfrage)

                                            <tr>
                                                <td class="border-right">
                                                    {{$date}}
                                                </td>
                                                <td>
                                                    {{$abfrage['count']}}
                                                </td>
                                                <td>
                                                    {{$abfrage['comment']}}
                                                </td>

                                                <td>
                                                    <div class="row">

                                                        @if(\Carbon\Carbon::parse($date)->isFuture())
                                                            <div class="col">
                                                                <button type="button" class="btn btn-link edit-comment-button"
                                                                        data-date="{{ $date }}"
                                                                        data-comment="{{$abfragen['comment'] ?? ''}}">
                                                                    <i class="fa fa-edit"></i> Kommentar bearbeiten
                                                                </button>
                                                            </div>
                                                        @endif

                                                        @if(\Carbon\Carbon::parse($date)->isFuture())
                                                            <div class="col pull-right">
                                                                <form action="{{route('care.abfrage.destroy', ['date' => $date])}}" method="post" class="delete-form">
                                                                    @csrf
                                                                    @method('delete')
                                                                    <button type="button" class="btn btn-link btn-danger text-danger delete-button">
                                                                        <i class="fa fa-trash"></i> Abfrage für alle löschen
                                                                    </button>
                                                                </form>
                                                            </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3">
                                                    keine zukünftigen Anwesenheitsabfragen vorhanden
                                                </td>
                                            </tr>

                                        @endforelse
                                        </tbody>
                                    </table>
                        </div>
                    </div>
                    <div class="card-body border-top">
                                    <h6>neue Anwesenheit abfragen</h6>
                                    <p>
                                        Anwesenheitsabfragen dienen dem Erfassen von Anwesenheiten zu einzelnen Tagen (bsp. Ferientage). Hier können neue Abfragen erstellt werden.
                                    </p>
                                    <form action="{{route('care.abfrage.store')}}" method="post">
                                        @csrf
                                        <div class="form-group">
                                            <label for="date_start" class=" text-danger">Datum von</label>
                                            <input type="date" name="date_start" id="date_start" class="form-control" min="{{now()->addDay()}}" required>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="date_end">Datum bis</label>
                                            <input type="date" name="date_end" id="date_end" class="form-control" min="{{now()->addDay()}}">
                                        </div>
                                        <div class="form-group mb-3
                    ">
                                            <label for="lock_at">Bis wann ist eine Anmeldung möglich</label>
                                            <input type="date" name="lock_at" id="lock_at" class="form-control" min="{{now()->addDay()}}">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Anwesenheit abfragen</button>
                                    </form>
                                </div>
                                <div class="card-footer border-top">
                                    <h6>Anwesenheit für Kind eintragen</h6>
                                    <p>
                                        Das Erfassen von geplanten Anwesenheiten einzelner Kinder ist hier möglich.
                                    </p>
                                    <form action="{{route('care.abfrage.anwesenheit.store')}}" method="post">
                                        @csrf
                                        <div class="form-group">
                                            <label for="date_start" class=" text-danger">Datum von</label>
                                            <input type="date" name="date_start" id="date_start" class="form-control" min="{{now()->addDay()}}" required>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="date_end">Datum bis</label>
                                            <input type="date" name="date_end" id="date_end" class="form-control" min="{{now()->addDay()}}">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="'child_id">Kind</label>
                                            <select name="child_id" id="child_id" class="form-control" required>
                                                <option value="">Bitte wählen</option>
                                                @foreach($children as $child)
                                                    <option value="{{$child->id}}">{{$child->last_name}}, {{$child->first_name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Abwesenheit eintragen</button>
                                    </form>
                                </div>
                            </div>
                        </div>
        @can('download schickzeiten')
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">
                            Schickzeiten
                        </h6>
                        <p>
                            Herunterladen der eingetragnen Schickzeiten als Excel-Datei
                        </p>
                    </div>
                    <div class="card-body">
                        <a href="{{url('schickzeiten/download')}}" class="btn btn-primary">
                            download
                        </a>
                    </div>
                    <div class="card-body border-top">
                        <h6>Dauer-Schickzeiten der einzelnen Kinder</h6>
                        <p>Hier können für jedes Kind die hinterlegten Dauerschickzeiten angezeigt werden</p>
                        <div class="container-fluid">
                            <div class="card">
                                <div class="card-body">
                                    <ul class="list-group">
                                        @foreach($children as $child)
                                            <li class="list-group-item"  data-toggle="collapse" href="#collapse{{$child->id}}_{{$child->users_id}}" role="button" >
                                                {{$child->last_name}}, {{$child->first_name}} <span class="badge badge-primary">{{$child->schickzeiten->count()}}</span>
                                            </li>
                                            <div class="collapse card mt-2" id="collapse{{$child->id}}_{{$child->users_id}}">
                                                <div class="card-body">
                                                    <table class="table table-striped">
                                                        <tr>
                                                            <th>

                                                            </th>
                                                            <th>
                                                                ab
                                                            </th>
                                                            <th>
                                                                genau
                                                            </th>
                                                            <th>
                                                                spätestens
                                                            </th>
                                                            <td></td>
                                                        </tr>
                                                        @for($x=1;$x<6;$x++)
                                                            <tr>
                                                                <th>
                                                                    {{$weekdays[$x]}}
                                                                </th>
                                                                <td>
                                                                    @if($child->schickzeiten->where('weekday', $x)->count() > 0) {{$child->schickzeiten->where('weekday', $x)->first()->time_ab?->format('H:i')}} @endif
                                                                </td>
                                                                <td>
                                                                    @if($child->schickzeiten->where('weekday', $x)->count() > 0) {{ $child->schickzeiten->where('weekday', $x)->first()?->time?->format('H:i') }} @endif
                                                                </td>
                                                                <td>
                                                                    @if($child->schickzeiten->where('weekday', $x)->count() > 0)  {{$child->schickzeiten->where('weekday', $x)->first()?->time_spaet?->format('H:i')}} @endif
                                                                </td>
                                                                <td>
                                                                    <div class="row">
                                                                        <div class="col">
                                                                            <a href="{{route('schickzeiten.edit',['child' => $child->id, 'day' => $x])}}" class="btn btn-link btn-primary text-primary">
                                                                                <i class="fa fa-edit"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="col">
                                                                            @if($child->schickzeiten->where('weekday', $x)->first())
                                                                                <form action="{{route('schickzeiten.destroy', ['schickzeit' => $child->schickzeiten->where('weekday', $x)->first()->id])}}" method="post">
                                                                                    @csrf
                                                                                    @method('delete')
                                                                                    <button type="submit" class="btn btn-link btn-danger text-danger"><i class="fa fa-trash"></i></button>
                                                                                </form>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endfor
                                                        <tfoot>
                                                        @foreach($child->schickzeiten->where('weekday', null) as $schickzeit)
                                                            <tr>
                                                                <th>
                                                                    {{$schickzeit->specific_date?->format('d.m.Y')}}
                                                                </th>
                                                                <td>
                                                                    @if($schickzeit->type == 'genau')
                                                                        {{$schickzeit->time?->format('H:i')}}
                                                                    @else
                                                                        ab {{$schickzeit->time_ab?->format('H:i')}} Uhr @if($schickzeit->time_spaet) bis  {{$schickzeit->time_spaet?->format('H:i')}} Uhr @endif
                                                                    @endif
                                                                </td>
                                                                <td colspan="2">

                                                                </td>
                                                                <td>
                                                                    <div class="row">
                                                                        <div class="col">

                                                                        </div>
                                                                        <div class="col">
                                                                            <form action="{{route('schickzeiten.destroy', ['schickzeit' => $schickzeit->id])}}" method="post">
                                                                                @csrf
                                                                                @method('delete')
                                                                                <button type="submit" class="btn btn-link btn-danger text-danger"><i class="fa fa-trash"></i></button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endcan
            </div>
        </div>




    <!-- Modal -->
    <div class="modal fade" id="commentModal" tabindex="-1" role="dialog" aria-labelledby="commentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commentModalLabel">Kommentar bearbeiten</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="commentForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="comment">Kommentar</label>
                            <textarea name="comment" id="comment" class="form-control" rows="3" required maxlength="256"></textarea>
                        </div>
                        <input type="hidden" name="date" id="commentDate">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                        <button type="button" id="deleteComment" class="btn btn-danger">Löschen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bestätigungsmodals für Schickzeiten --}}
    @include('components.schickzeiten-confirmation-modals')

@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteButtons = document.querySelectorAll('.delete-button');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const form = this.closest('form');
                    Swal.fire({
                        title: 'Sind Sie sicher?',
                        text: 'Diese Aktion kann nicht rückgängig gemacht werden!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ja, löschen!',
                        cancelButtonText: 'Abbrechen'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            const commentModal = document.getElementById('commentModal');
            const commentForm = document.getElementById('commentForm');
            const deleteButton = document.getElementById('deleteComment');

            // Öffnen des Modals mit den entsprechenden Daten
            document.querySelectorAll('.edit-comment-button').forEach(button => {
                button.addEventListener('click', function () {
                    const date = this.dataset.date;
                    const comment = this.dataset.comment || '';

                    document.getElementById('commentDate').value = date;
                    document.getElementById('comment').value = comment;

                    commentForm.action = `{{route('anwesenheit.comment.update')}}`;
                    deleteButton.dataset.date = date;

                    $(commentModal).modal('show');
                });
            });

            // Löschen des Kommentars
            deleteButton.addEventListener('click', function () {
                const date = this.dataset.date;

                Swal.fire({
                    title: 'Sind Sie sicher?',
                    text: 'Der Kommentar wird gelöscht!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ja, löschen!',
                    cancelButtonText: 'Abbrechen'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`{{route('anwesenheit.comment.remove')}}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ date })
                        }).then(response => {
                            if (response.ok) {
                                Swal.fire('Gelöscht!', 'Der Kommentar wurde gelöscht.', 'success');
                                $(commentModal).modal('hide');
                                location.reload();
                            }
                        });
                    }
                });
            });
        });
    </script>


@endpush
