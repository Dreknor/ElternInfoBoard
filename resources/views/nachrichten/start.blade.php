    <div class="card blur">
        <div class="card-header border-bottom">
            <h5>
                Aktuelle Nachrichten
            </h5>
        </div>

        @if($nachrichten != null and count($nachrichten)>0)
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12 col-md-9 col-lg-10">
                        <button class="btn btn-primary btn-block hidden  d-md-none" type="button" data-toggle="collapse"
                                data-target="#Themen" aria-expanded="false" aria-controls="collapseThemen">
                            Themen zeigen
                        </button>
                        <div class="row collapse d-md-block" id="Themen">
                            <div class="col">
                                @foreach($nachrichten AS $nachricht)
                                    @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                                        <a href="#{{$nachricht->id}}"
                                           class="anker_link btn btn-sm wrap
                                   @if($nachricht->released == 1) btn-primary @else btn-outline-warning @endif
                                   @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach
                                   ">
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
                                                @if(! is_null($nachricht->rueckmeldung))
                                                    <div
                                                        class="d-inline @if($nachricht->rueckmeldung->pflicht == 1) text-danger @endif">
                                                        @switch($nachricht->rueckmeldung->type)
                                                            @case('email')
                                                                <i class="fas fa-comment-dots"></i>
                                                                @break

                                                            @case('abfrage')
                                                                <i class="fa fa-poll-h"></i>
                                                                @break
                                                            @default

                                                                @break
                                                        @endswitch
                                                    </div>

                                                @endif
                                                @if($nachricht->read_receipt ==1 )
                                                    <i class="fas fa-book-open"></i>
                                                @endif
                                                {{$nachricht->header}}
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-3 col-lg-2 border-left">
                        <h6>Filter</h6>
                        <div class="row mt-1">
                            @foreach(auth()->user()->groups as $group)
                                <div class="col-auto">
                                    <label class="switch switch-sm ">
                                        <input type="checkbox" class="filter_switch"
                                               id="{{\Illuminate\Support\Str::camel($group->name)}}">
                                        <span class="slider slider-sm round"></span>
                                    </label>
                                    {{$group->name}}
                                </div>
                            @endforeach
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
        @foreach($nachrichten AS $nachricht)
            @if($nachricht->released == 1 or auth()->user()->can('edit posts') or $nachricht->author == auth()->id())
                <div
                    class="nachricht @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach">
                    @include('nachrichten.nachricht')
                </div>
            @endif
        @endforeach
