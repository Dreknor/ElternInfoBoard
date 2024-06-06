@extends('layouts.app')
@section('title') - Listen @endsection

@section('content')
    <div class="container-fluid">
        <a class="btn btn-outline-info" href="{{url('listen')}}">zurück zur Übersicht</a>
        <div class="card">
            <div class="card-header border-bottom @if($liste->active == 0) bg-info @endif">
                <h5>
                    {{$liste->listenname}} @if($liste->active == 0) (inaktiv) @endif
                </h5>
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <p class="info small">
                            {!! $liste->comment !!}
                        </p>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        @if(auth()->user()->id == $liste->besitzer or auth()->user()->can('edit terminliste'))
                            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#createEintragungModal">
                                <i class="fa fa-dd-user"></i>
                                    Termin anlegen
                            </button>

                            <a href="{{url('listen/'.$liste->id.'/export')}}" class="btn btn-secondary pull-right" >
                                <i class="fa fas-export"></i>
                                Druckansicht
                            </a>

                            <button class="btn btn-primary pull-right" type="button" id="showAll">
                                <i class="fas fa-eye"></i> alle Termine
                            </button>
                        @endif
                    </div>
                </div>

            </div>
            <div class="card-body">
                @if($liste->termine->count()> 0)
                    <ul class="list-group">

                        @foreach($liste->termine->sortBy('termin') as $eintrag)
                            @include('listen.terminListen.termin')
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-info">
                        <p>
                            Es wurden bisher keine Eintragungen angelegt.
                        </p>
                    </div>
                @endif


            </div>
        </div>
    </div>



    <!-- Modal zum Anlegen der Eintragungen -->
    <div class="modal" tabindex="-1" role="dialog" id="createEintragungModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">neue Auswahlmöglichkeit erstellen</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="post" action="{{url("listen/termine/$liste->id/store")}}" class="form-horizontal"
                          id="terminForm">
                        @csrf
                        <div class="form-row">
                            <label for="termin">
                                Datum:
                            </label>
                            <input type="date" name="termin" class="form-control" required>
                        </div>
                        <div class="form-row">
                            <label>
                                Uhrzeit:
                           </label>
                           <input type="time" name="zeit" class="form-control" required>
                       </div>
                       <div class="form-row">
                           <label for="termin">
                               Dauer in Minuten:
                           </label>
                           <input type="number" name="duration" class="form-control" value="{{$liste->duration}}">
                       </div>
                       <div class="form-row">
                           <label for="comment">
                               Anmerkung:
                           </label>
                           <input type="text" name="comment" class="form-control">
                       </div>
                       <div class="form-row">
                           <label for="weekly">
                               Wöchentlich?
                           </label>
                           <select type="number" name="weekly" class="form-control">
                               <option value="0">nein</option>
                               <option value="1">ja</option>
                           </select>
                       </div>
                       <div class="form-row">
                           <label for="repeat">
                               Anzahl aufeinderfolgender Termine (bei wöchentlich Anzahl der Wochen)
                           </label>
                           <input type="number" name="repeat" class="form-control" min="1" step="1" value="1">
                       </div>
                   </form>
                </div>
                <div class="modal-footer">
                    <div class="btn btn-primary btn-block" id="submitBtn">Speichern</div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal zur Absage -->
    <div class="modal" tabindex="-1" role="dialog" id="deleteEintragungModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Termin absagen</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="absagenForm">
                        @csrf
                        @method('delete')
                        <label for="text">Nachricht</label>
                        <textarea class="form-control" id="text" name="text"></textarea>
                        <button type="submit" class="btn  btn-outline-danger btn-xs btn-round">
                            absagen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')

    <script>
        $('#submitBtn').on('click', function (event) {
            $("#terminForm").submit();
        })
    </script>

    <script>
        $('#showAll').on('click', function () {
            var btn = this;
            $(btn).addClass('d-none');
            $('.hide').removeClass('d-none');
        });
    </script>

    <script>
        $('.btnAbsage').on('click', function () {
            var btn = this;
            console.log(btn);
            var id = $(this).data('terminid');
            var url = "{{url("listen/termine/absagen/")}}/" + id;
            $('#absagenForm').attr('action', url);

        });
    </script>


@endpush
