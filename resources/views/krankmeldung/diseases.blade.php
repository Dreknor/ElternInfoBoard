@canany(['see diseases', 'manage diseases'])
    @if(count($diseases) > 0)
        <div class="card border border-danger">
            <div class="card-header bg-danger">
                <h6 class="card-title">aushangpflichtige Krankheiten</h6>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($diseases as $disease)
                        <li class="list-group-item @if(!$disease->active) bg-info @endif">
                            <div class="row">
                                <div class="col-3">
                                    seit: {{$disease->start->format('d.m.Y')}}
                                </div>
                                <div class="col-7">
                                    {{$disease->disease->name}}
                                </div>
                                @can('manage diseases')
                                    @if(!$disease->active)
                                        <div class="col-2">
                                            <a href="{{url('krankmeldung/disaese/activate/'.$disease->id)}}"
                                               class="btn btn-success btn-sm">aktivieren</a>
                                        </div>
                                    @endif
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>

            </div>
        </div>
    @endif
@endcanany
