@if($children->count() > 0)
    <div class="container-fluid">
        <div class="row">
            @foreach($children as $child)
                <div class="col-md-4">
                    <div class="card border">
                        <div class="@if($child->checkedIn()) bg-gradient-directional-teal @else bg-info @endif card-header border-bottom text-white pt-1">
                            <h6 class="card-title">
                                @if($child->krankmeldungToday())
                                    <div class="badge badge-danger mr-2">
                                        <i class="fas fa-ban"></i> Krank
                                    </div>
                                @endif
                                {{ $child->first_name }}
                                    <a href="{{ url('schickzeiten') }}" class="text-white float-right p-1 border mb-1">
                                        <i class="fas fa-eye"></i>
                                    </a>
                            </h6>

                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    @if(!$child->checkedIn() and ($child->checkIns()->where('date', today())->first()?->checked_out))
                                        <p>
                                            Abgemeldet um
                                            {{$child->checkIns()->where('date', today())->first()?->updated_at?->format('H:i')}}
                                            Uhr
                                        </p>
                                    @elseif($child->checkedIn())
                                        <p>
                                            <i class="fas fa-user-check text-success"></i> derzeit angemeldet
                                            @if($child->getSchickzeitenForToday()->count() > 0)
                                                @foreach($child->getSchickzeitenForToday() as $schickzeit)
                                                    <br><b>Schickzeit:</b>
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
                                            @else
                                                <br>keine Schickzeiten
                                            @endif
                                            @if($child->hasNotice())
                                                <br>
                                                <b>Nachricht:</b> {{$child->hasNotice()->notice}}
                                            @endif
                                        </p>
                                    @else
                                        heute nicht angemeldet
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <b>Anwesenheit:</b>
                                    <p>
                                        @forelse($child->checkIns->sortBy('date') as $checkIn)
                                                {{ $checkIn->date->format('d.m.Y') }} @if($checkIn->should_be) <i class="text-success">angemeldet </i> @else <i class="text-danger">nicht angemeldet </i>  @endif <br>
                                        @empty
                                            <tr>
                                                <td colspan="5">Keine Anwesenheitsabfragen vorhanden</td>
                                            </tr>
                                        @endforelse
                                    </p>

                                </div>
                            </div>

                    </div>
                </div>
            @endforeach
        </div>
    </div>

@endif
