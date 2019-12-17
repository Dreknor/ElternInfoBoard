@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <h5>
                    {{$liste->listenname}}
                </h5>
                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <p class="info small">
                            {!! $liste->comment !!}

                        </p>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        @if(auth()->user()->id == $liste->besitzer or auth()->user()->can('edit terminliste'))
                            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#createEintragungModal">
                                <i class="fa fa-dd-user"></i>
                                    Termin anlegen
                            </button>
                        @endif
                    </div>
                </div>

            </div>
            <div class="card-body">
                @if($liste->eintragungen->count()> 0)
                    <ul class="list-group">
                        @foreach($liste->eintragungen->sortBy('termin') as $eintrag)
                            <div class="list-group-item">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 m-auto">
                                        {{	$eintrag->termin->format('d.m.Y')}},  {{	$eintrag->termin->formatLocalized('%A')}}
                                    </div>

                                    <div class="col-md-3 col-sm-6 m-auto">
                                        {{	$eintrag->termin->format('H:i')}}
                                    </div>

                                    <div class="col-md-3 col-sm-6 m-auto">
                                        @if(auth()->user()->id == $liste->besitzer or auth()->user()->can('edit terminliste'))
                                           <div class="row">
                                               <div class="col-md-6 col-sm-12 m-auto">
                                                   @if($eintrag->reserviert_fuer != null)
                                                       {{$eintrag->eingetragenePerson->name }}
                                                   @endif
                                               </div>
                                               <div class="col-md-6 col-sm-12">
                                                   <form method="post" action="{{url("eintragungen/".$eintrag->id)}}">
                                                       @csrf
                                                       @method('delete')
                                                       <button type="submit" class="btn btn-danger btn-round">
                                                           Termin löschen @if($eintrag->reserviert_fuer != null) und {{$eintrag->eingetragenePerson->name }} absagen @endif
                                                       </button>
                                                   </form>
                                               </div>
                                           </div>


                                        @else
                                            @if($eintrag->reserviert_fuer != null)
                                                @if($liste->visible_for_all)
                                                            {{$eintrag->eingetragenePerson->name }}
                                                @else
                                                    vergeben
                                                @endif
                                            @else
                                                <form method="post" action="{{url("eintragungen/".$eintrag->id)}}">
                                                    @csrf
                                                    @method('put')
                                                    <button type="submit" class="btn btn-primary btn-round">reservieren</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="col-md-3 col-sm-6 m-auto">
                                        {{$eintrag->comment}}
                                    </div>
                                </div>

                            </div>
                        @endforeach
                @else
                    <div class="alert alert-info">
                        <p>
                            Es wurden bisher keine Eintragungen angelegt.
                        </p>
                    </div>
                @endif
                </ul>

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
                   <form method="post" action="{{url("eintragungen/$liste->id/store")}}" class="form-horizontal" id="terminForm">
                       @csrf
                       <div class="form-row">
                           <label for="termin">
                               Terminstart:
                           </label>
                           <input type="datetime-local" name="termin" class="form-control" required>
                       </div>
                       <div class="form-row">
                           <label for="termin">
                               Dauer in Minuten (kann nicht geändert werden):
                           </label>
                           <input type="number" name="duration" class="form-control" readonly value="{{$liste->duration}}">
                       </div>
                       <div class="form-row">
                           <label for="comment">
                               Anmerkung:
                           </label>
                           <input type="text" name="comment" class="form-control">
                       </div>
                       <div class="form-row">
                           <label for="termin">
                               Anzahl aufeinderfolgender Termine
                           </label>
                           <input type="number" name="repeat" class="form-control" min="1" step="1" max="5" value="1">
                       </div>
                   </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" form="terminForm">Speichern</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>
@endsection