<div class="list-group-item @if($eintrag->termin->lessThan(\Carbon\Carbon::now())) hide d-none @endif">
    <div class="row">
        <div class="col-sm-6 col-md-3 m-auto bg-light-gray">
            Von
        </div>
        <div class="col-sm-6 col-md-3 m-auto bg-light-gray ">
            bis
        </div>
        <div class="col-sm-6 col-md-3 m-auto  bg-light-gray">
            Kommentar
        </div>
        <div class="col-sm-6 col-md-3 m-auto bg-light-gray ">
            Reserviert für
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6 col-md-3 m-auto">
            {{	$eintrag->termin->dayName}}, <br>
            <b>{{	$eintrag->termin->format('d.m.Y')}}
        </div>
        <div class="col-sm-6 col-md-3 m-auto h-100">
            {{	$eintrag->termin->format('H:i')}} Uhr - </b>
            <b>@if($eintrag->ende->day != $eintrag->termin->day) {{$eintrag->ende->format('d.m.Y')}}@endif  {{$eintrag->ende->format('H:i')}} Uhr</b>
        </div>
        <div class="col-sm-6 col-md-3 m-auto">
            {{$eintrag->comment}}
        </div>
        <div class="col-sm-6 col-md-3 m-auto">
            <b>
                @if($eintrag->reserviert_fuer != null)
                    @if($eintrag->eingetragenePerson->id == auth()->id() or $eintrag->eingetragenePerson->sorg2 == auth()->id() or $liste->visible_for_all or auth()->user()->can('edit terminliste'))
                        {{$eintrag->eingetragenePerson->name }}
                    @else
                        reserviert
                    @endif
                @endif
            </b>

        </div>
    </div>
    <div class="row">
        @if(auth()->user()->id != $liste->besitzer and !auth()->user()->can('edit terminliste'))
            <div class="col-12 m-auto">
                @if($eintrag->reserviert_fuer == null)
                    <form method="post" action="{{url("listen/termine/".$eintrag->id)}}">
                        @csrf
                        @method('put')
                        <button type="submit" class="btn btn-primary btn-round">
                            reservieren
                        </button>
                    </form>
                @endif
            </div>
        @else
                @if($eintrag->reserviert_fuer != null)
                <div class="col-sm-6 col-md-6 m-auto">
                    <button
                        class="btn btn-outline-danger btn-xs btn-round btnAbsage"
                        data-toggle="modal" data-target="#deleteEintragungModal"
                        data-terminID="{{$eintrag->id}}">
                        {{$eintrag->eingetragenePerson->name }} absagen
                    </button>
                </div>
                @else
                    <div class="col-sm-6 col-md-3 m-auto">
                        <form method="post"
                              action="{{url("listen/termine/".$eintrag->id)}}">
                            @csrf
                            @method('delete')
                            <button type="submit"
                                    class="btn  btn-outline-warning btn-xs btn-round">
                                Termin löschen
                            </button>
                        </form>
                    </div>
                    <div class="col-sm-6 col-md-3 m-auto">
                        <form method="post"
                              action="{{url("listen/termine/".$eintrag->id)}}">
                            @csrf
                            @method('put')
                            <button type="submit"
                                    class="btn btn-primary btn-round">reservieren
                            </button>
                        </form>
                    </div>
            @endif

            <div class="col-sm-6 col-md-6 m-auto">
                <a href="{{url("listen/termine/".$eintrag->id."/copy")}}"
                   class="btn btn-outline-info btn-round">kopieren</a>
            </div>
        @endif
    </div>
</div>
