<div class="px-4 py-2 termin-row @if($loop->iteration%2) termin-row-even @endif transition-colors duration-200">
    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
        <!-- Datum -->
        <div class="flex items-center space-x-2 lg:w-40">
            <div>
                <div class="text-sm font-semibold leading-tight" style="color: var(--color-text-primary)">
                    @if($termin->start->day != $termin->ende->day)
                        {{$termin->start->format('d.m.')}} - {{$termin->ende->format('d.m.Y')}}
                    @else
                        {{$termin->start->format('d.m.Y')}}
                    @endif
                </div>
                @if($termin->fullDay)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium"
                          style="background-color: var(--color-widget-success-bg); color: var(--color-text-success)">
                    Ganztägig
                </span>
                @endif
                @if($termin->start->day == $termin->ende->day and !$termin->fullDay)
                    <div class="text-sm" style="color: var(--color-text-secondary)">
                        {{$termin->start->format('H:i')}} - {{$termin->ende->format('H:i')}} Uhr
                    </div>
                @endif
            </div>
        </div>

        <!-- Terminname -->
        <div class="flex-1 min-w-0 flex items-center gap-2">
            <h6 class="text-sm font-bold" style="color: var(--color-text-primary)">
                {{$termin->terminname}}
            </h6>
            @if(auth()->user()->can('view all') && ($termin->public ?? false))
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                      style="background-color: var(--color-widget-success-bg); color: var(--color-text-success)"
                      title="Dieser Termin ist öffentlich">Öffentlich</span>
            @endif
        </div>

        <!-- Kalender-Links -->
        <div class="flex items-center space-x-1.5 lg:w-auto">
            <a href="{{$termin->link(auth()->user()?->calendar_prefix)->ics()}}"
               class="inline-flex items-center justify-center w-8 h-8 rounded transition-colors"
               style="background-color: var(--color-surface-subtle)"
               onmouseover="this.style.backgroundColor='var(--color-card-border)'"
               onmouseout="this.style.backgroundColor='var(--color-surface-subtle)'"
               title="ICS-Download für Apple und Windows">
                <img src="{{asset('img/ics-icon.png')}}" class="w-5 h-5" alt="ICS">
            </a>
            <a href="{{$termin->link(auth()->user()?->calendar_prefix)->google()}}"
               class="inline-flex items-center justify-center w-8 h-8 rounded transition-colors"
               style="background-color: var(--color-surface-subtle)"
               onmouseover="this.style.backgroundColor='var(--color-card-border)'"
               onmouseout="this.style.backgroundColor='var(--color-surface-subtle)'"
               target="_blank"
               title="Google-Kalender-Link">
                <img src="{{asset('img/icon-google-cal.png')}}" class="w-5 h-5" alt="Google Calendar">
            </a>

            @can('edit termin')
                <!-- Info-Button -->
                <button type="button"
                   class="inline-flex items-center justify-center w-8 h-8 rounded transition-colors"
                   style="background-color: var(--color-widget-primary-bg); color: var(--color-widget-primary-from)"
                   onmouseover="this.style.backgroundColor='var(--color-widget-primary-from)'; this.style.color='#ffffff'"
                   onmouseout="this.style.backgroundColor='var(--color-widget-primary-bg)'; this.style.color='var(--color-widget-primary-from)'"
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
                       class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded text-white text-xs font-medium transition-colors"
                       style="background-color: var(--color-widget-primary-from)"
                       onmouseover="this.style.backgroundColor='var(--color-widget-primary-border)'"
                       onmouseout="this.style.backgroundColor='var(--color-widget-primary-from)'">
                        <i class="fa fa-edit"></i>
                        <span class="hidden lg:inline">Bearbeiten</span>
                    </a>
                @endif
            @endcan
        </div>
    </div>
</div>
