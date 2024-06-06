<div class="row  p-2 align-items-center h-100"
     @if($loop->iteration%2) style="background-color: rgba(217,217,217,0.61);" @endif>
    <div class="col-sm-6 col-md-2 col-lg-2">
        @if($termin->start->day != $termin->ende->day)
            {{$termin->start->format('d.m. ')}} - {{$termin->ende->format('d.m.Y')}}
        @else
            {{$termin->start->format('d.m.Y')}}
        @endif
    </div>
    <div class="col-sm-6 col-md-2 col-lg-2">
        @if($termin->start->day == $termin->ende->day and !$termin->fullDay )
            {{$termin->start->format('H:i')}} -  {{$termin->ende->format('H:i')}} Uhr
        @endif
    </div>
    <div class="col-sm-12 col-md-8 col-lg-6 font-weight-bold">
        {{$termin->terminname}}
        <div class="d-inline">
            <div class="pull-right">
                <a href="{{$termin->link(auth()->user()->calendar_prefix)->ics()}}" class="card-link"
                   title="ICS-Download für Apple und Windows">
                    <img src="{{asset('img/ics-icon.png')}}" height="25px">
                </a>
                <a href="{{$termin->link(auth()->user()->calendar_prefix)->google()}}" class="card-link" target="_blank"
                   title="Goole-Kalender-Link">
                    <img src="{{asset('img/icon-google-cal.png')}}" height="25px">
                </a>
            </div>
        </div>
    </div>

@if(auth()->user()->can('edit termin'))
        <div class="col-auto">
            <a href="#"
               tabindex="0" role="button"
               data-toggle="popover" title="{{$termin->terminname}} @if($termin->public == 1) (öffentlich) @endif"
               data-content="Gruppen: @foreach($termin->groups as $group) {{$group->name}}@if(!$loop->last), @endif @endforeach"
               data-trigger="focus">
                <i class="fa fa-info-circle">
                </i>
            </a>
        </div>
        <div class="col-auto">
            <a href="{{url("termin/$termin->id/edit")}}" class="text-black-50">
                <i class="fa fa-edit"></i>
                <div class="d-none d-lg-inline ">bearbeiten</div>
            </a>
        </div>
    @endif
</div>
