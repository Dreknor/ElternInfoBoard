    <div class="card blur">
        <div class="card-header border-bottom">
            <h5>
                Aktuelle Nachrichten
            </h5>
        </div>

        @if($nachrichten != null and count($nachrichten)>0)
            <div class="card-body">
                <button class="btn btn-primary hidden  d-md-none" type="button" data-toggle="collapse"
                        data-target="#Themen" aria-expanded="false" aria-controls="collapseThemen">
                    Themen zeigen
                </button>
                <div class="row collapse d-md-block" id="Themen">
                    <div class="col">
                        @foreach($nachrichten AS $nachricht)
                            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                                <a href="#{{$nachricht->id}}"
                                   class="btn btn-sm wrap  @if($nachricht->released == 1) btn-outline-primary @else btn-outline-warning @endif  @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach">

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

                <div class="card-footer border-top">
                    <div class="row">

                    @if(count($nachrichten->filter(function ($item, $key){ if ($item->type == "info") { return $item;}})) > 0)
                            <div class="col">
                                <div class="btn btn-outline-primary btn-sm btn-block" type="button" id="infoButton">
                                    <i class="fas fa-eye"></i> Infos ausblenden
                                </div>
                            </div>
                        @endif
                        @if(count($nachrichten->filter(function ($item, $key){ if ($item->type == "pflicht") { return $item;}})) > 0)
                            <div class="col">
                                <div class="btn btn-outline-danger btn-sm btn-block" type="button" id="pflichtButton">
                                    <i class="fas fa-eye"></i> Pflichtaufgaben ausblenden
                                </div>
                            </div>
                        @endif
                        @if(count($nachrichten->filter(function ($item, $key){ if ($item->type == "wahl") { return $item;}})) > 0)
                            <div class="col">
                                <div class="btn btn-outline-warning btn-sm btn-block" type="button" id="wahlButton">
                                    <i class="fas fa-eye"></i> Wahlaufgaben ausblenden
                                </div>
                            </div>
                        @endif

                    </div>
                    <div class="row mt-1">

                        @foreach(auth()->user()->groups as $group)
                            <div class="col">
                                    <div class="btn btn-outline-primary btn-sm btn-block" type="button" id="{{\Illuminate\Support\Str::camel($group->name)}}" data-show="true">
                                        {{$group->name}}
                                    </div>
                            </div>
                        @endforeach
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
        @foreach($nachrichten AS $nachricht)
            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                    @include('nachrichten.nachricht')
                </div>
            @endif
        @endforeach
