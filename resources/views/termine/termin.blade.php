<div class="px-4 py-2 hover:bg-gray-50 transition-all duration-200 @if($loop->iteration%2) bg-gray-50/50 @endif">
    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
        <!-- Datum -->
        <div class="flex items-center space-x-2 lg:w-40">
            <div>
                <div class="text-sm font-semibold text-gray-900 leading-tight">
                    @if($termin->start->day != $termin->ende->day)
                        {{$termin->start->format('d.m.')}} - {{$termin->ende->format('d.m.Y')}}
                    @else
                        {{$termin->start->format('d.m.Y')}}
                    @endif
                </div>
                @if($termin->fullDay)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    Ganztägig
                </span>
                @endif
                @if($termin->start->day == $termin->ende->day and !$termin->fullDay)
                    <div class="text-sm text-gray-600">
                        {{$termin->start->format('H:i')}} - {{$termin->ende->format('H:i')}} Uhr
                    </div>
                @endif
            </div>
        </div>

        <!-- Terminname -->
        <div class="flex-1 min-w-0 flex items-center gap-2">
            <h6 class="text-sm font-bold text-gray-900">
                {{$termin->terminname}}
            </h6>
        </div>

        <!-- Kalender-Links -->
        <div class="flex items-center space-x-1.5 lg:w-auto">
            <a href="{{$termin->link(auth()->user()->calendar_prefix)->ics()}}"
               class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-100 hover:bg-gray-200 transition-colors"
               title="ICS-Download für Apple und Windows">
                <img src="{{asset('img/ics-icon.png')}}" class="w-5 h-5" alt="ICS">
            </a>
            <a href="{{$termin->link(auth()->user()->calendar_prefix)->google()}}"
               class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-100 hover:bg-gray-200 transition-colors"
               target="_blank"
               title="Google-Kalender-Link">
                <img src="{{asset('img/icon-google-cal.png')}}" class="w-5 h-5" alt="Google Calendar">
            </a>

            @can('edit termin')
                <!-- Info-Button -->
                <button type="button"
                   class="inline-flex items-center justify-center w-8 h-8 rounded bg-blue-100 hover:bg-blue-200 text-blue-600 transition-colors"
                   tabindex="0"
                   data-toggle="popover"
                   title="{{$termin->terminname}} @if($termin->public == 1) (öffentlich) @endif"
                   data-content="Gruppen: @foreach($termin->groups as $group) {{$group->name}}@if(!$loop->last), @endif @endforeach"
                   data-trigger="focus">
                    <i class="fa fa-info-circle text-sm"></i>
                </button>

                <!-- Bearbeiten-Button -->
                @if($termin->id != null)
                    <a href="{{url("termin/$termin->id/edit")}}"
                       class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors">
                        <i class="fa fa-edit"></i>
                        <span class="hidden lg:inline">Bearbeiten</span>
                    </a>
                @endif
            @endcan
        </div>
    </div>
</div>

