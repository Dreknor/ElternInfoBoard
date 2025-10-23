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
                @forelse($child->notice()->future()->get() as $notice)
                    <div class="card">
                        <div class="card-header">
                            <b>{{$notice->date->format('d.m.Y')}}:</b>
                            <div class="pull-right">
                                <form action="{{route('child.notice.destroy', ['childNotice' => $notice->id])}}" method="post">
                                    @csrf
                                    @method('delete')
                                    <button  class="btn btn-link btn-danger delete-notice-btn">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    {{$notice->notice}}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p>Keine Notizen hinterlegt</p>
                @endforelse
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
