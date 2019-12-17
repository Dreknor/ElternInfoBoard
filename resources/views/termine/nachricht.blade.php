@if(isset($termine) and !is_null($termine) and !isset($archiv))
    <div class="card border">
        <div class="card-header border-bottom" >
            <div class="row">
                <div class="col-md-10">
                    <h6 class="card-title">
                        aktuelle Termine
                    </h6>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <table class="table table-striped">
                        <tbody>
                        @foreach($termine as $termin)
                            <tr class="w-100">
                                <td>
                                    @if($termin->start->day != $termin->ende->day)
                                        {{$termin->start->format('d.m. ')}} - {{$termin->ende->format('d.m.Y')}}
                                    @else
                                        {{$termin->start->format('d.m.Y')}}
                                    @endif
                                </td>
                                <td>
                                    @if($termin->start->day == $termin->ende->day and !$termin->fullDay )
                                        {{$termin->start->format('H:i')}} -  {{$termin->ende->format('H:i')}} Uhr
                                    @endif
                                </td>
                                <td>
                                    {{$termin->terminname}}
                                </td>
                                <td class="pull-right">
                                    @if(auth()->user()->can('edit termin'))
                                        <form action="{{url("termin/$termin->id")}}" method="post">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="far fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                        <a href="{{$termin->link()->ics()}}" class="btn btn-primary btn-sm" title="ICS-Download für Apple und Windows">
                                            <img  src="{{asset('img/ics-icon.png')}}" height="25px">
                                        </a>
                                        <a href="{{$termin->link()->google()}}" class="btn btn-primary btn-sm" target="_blank" title="Goole-Kalender-Link">
                                            <img src="{{asset('img/icon-google-cal.png')}}" height="25px">
                                        </a>

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

    </div>

@endif