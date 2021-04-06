<div class="container-fluid">
    <div class="card blur">
        <div class="card-header border-bottom">
            <h5>
                Aktuelle Nachrichten
            </h5>
        </div>

        @if($nachrichten != null and count($nachrichten)>0)
            <div class="card-body">
                <button class="btn btn-primary hidden  d-md-none" type="button" data-toggle="collapse" data-target="#Themen" aria-expanded="false" aria-controls="collapseThemen">
                    Themen zeigen
                </button>
                <div class="row collapse d-md-block" id="Themen">
                    <div class="col">
                        @foreach($nachrichten AS $nachricht)
                            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                                <a href="#{{$nachricht->id}}" class="btn btn-sm {{$nachricht->type}} @if($nachricht->released == 1) btn-outline-primary @else btn-outline-warning @endif">

                                    <div class="
                                        @switch($nachricht->type)
                                    @case('pflicht')
                                        text-danger
                                        @break

                                    @case('wahl')
                                        text-warning
                                        @break
                                    @endswitch
                                        ">
                                        {{$nachricht->header}}
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-4">
                            <div class="btn btn-outline-primary btn-sm btn-block" type="button" id="infoButton">
                                <i class="fas fa-eye"></i> Infos ausblenden
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <div class="btn btn-outline-danger btn-sm btn-block" type="button" id="pflichtButton">
                                <i class="fas fa-eye"></i> Pflichtaufgaben ausblenden
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <div class="btn btn-outline-warning btn-sm btn-block" type="button" id="wahlButton">
                                <i class="fas fa-eye"></i> Wahlaufgaben ausblenden
                            </div>
                        </div>
                    </div>
                </div>

        @else

            <div class="card-body bg-info">
                <p>
                    Es sind keine Nachrichten vorhanden
                </p>
            </div>

        @endif
    </div>

    <div id="">

        @foreach($nachrichten AS $nachricht)
            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                    @include('nachrichten.nachricht')
                </div>
            @endif
        @endforeach
    </div>

    @if($nachrichten != null and count($nachrichten)>0)
        <div class="archiv">
            {{$nachrichten->links()}}
        </div>
    @endif
</div>
