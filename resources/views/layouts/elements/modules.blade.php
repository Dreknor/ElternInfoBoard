@auth
    @if(isset($modules) && is_iterable($modules))
        @foreach($modules as $module)
            @php
                $moduleRights = $module->options['rights'] ?? [];
                if (is_string($moduleRights)) { $moduleRights = json_decode($moduleRights, true) ?? []; }
                if (!is_array($moduleRights)) { $moduleRights = array_values((array) $moduleRights); }
            @endphp
            @if(count($moduleRights) == 0 or auth()->user()->hasAnyPermission($moduleRights))

            @if(array_key_exists('home-view',$module->options) and $module->options['home-view']!="" and (request()->segment(1) ==""  or request()->segment(1) =="home"))
                @push('home-view')
                    {{$input = \Illuminate\Support\Facades\View::make($module->options['home-view'])}}
                @endpush
            @endif

            @if(array_key_exists('home-view-top',$module->options) and $module->options['home-view-top']!="" and (request()->segment(1) ==""  or request()->segment(1) =="home"))
                @push('home-view-top')
                    {{\Illuminate\Support\Facades\View::make($module->options['home-view-top'])}}
                @endpush
            @endif


            @if(array_key_exists('nav-user', $module->options) and  is_array($module->options['nav-user']) and !empty($module->options['nav-user']) and isset($module->options['nav-user']['name'], $module->options['nav-user']['link']) )
                @push('nav-user')
                    <a href="{{url($module->options['nav-user']['link'])}}"
                       class="flex items-center gap-3 px-4 py-2 text-sm transition-colors"
                       style="color: var(--color-text-secondary);"
                       onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-navbar-user-btn-bg');this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-text-primary')"
                       onmouseout="this.style.backgroundColor='';this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-text-secondary')">
                        @if(array_key_exists('icon', $module->options['nav-user']))
                            <i class="{{$module->options['nav-user']['icon']}}" style="color: var(--color-primary);"></i>
                        @endif
                        <span>{{$module->options['nav-user']['name']}}</span>
                    </a>
                @endpush
            @endif
            @if(array_key_exists('nav', $module->options) and  is_array($module->options['nav']) and !empty($module->options['nav']) and isset($module->options['nav']['name'], $module->options['nav']['link'], $module->options['nav']['icon']))

                @push('nav')
                    <li class="nav-item"
                        @if(isset($notifications) and $notifications->where('type', $module->options['nav']['name'])->where('read',0)->count() > 0)
                            onclick="markNotificationAsRead('{{url('markNotificationAsRead')}}','{{$module->options['nav']['name']}}')"
                        @endif
                    >
                        <a href="{{url($module->options['nav']['link'])}}"
                           class="nav-link flex items-center justify-between gap-2 px-3 py-2 rounded-lg sidebar-nav-link transition-all duration-200 @if(request()->path() == $module->options['nav']['link']) sidebar-active @endif group">
                            <div class="flex items-center gap-2">
                                <i class="{{$module->options['nav']['icon']}} text-base group-hover:scale-110 transition-transform"></i>
                                <span class="font-medium">{{$module->options['nav']['name']}}</span>
                            </div>
                            @if(isset($notifications) and $notifications->where('type', $module->options['nav']['name'])->where('read',0)->count() > 0)
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white rounded-full animate-pulse"
                                  style="background-color: var(--color-badge-bg);">
                                    {{$notifications->where('type', $module->options['nav']['name'])->where('read',0)->count()}}
                                </span>
                            @endif
                        </a>
                    </li>
                @endpush

                @if(isset($module->options['nav']['bottom-nav']) and $module->options['nav']['bottom-nav'] == "true")
                    @push('bottom-nav')
                        <div class="mobile-bottom-nav_item flex-1 @if(request()->path() == $module->options['nav']['link']) mobile-bottom-nav_item--active @endif">
                            <div class="mobile-bottom-nav_item-content relative">
                                <a href="{{url($module->options['nav']['link'])}}"
                                   class="flex flex-col items-center justify-center gap-0.5 py-2 transition-all duration-200 group @if(request()->path() == $module->options['nav']['link']) mobile-bottom-nav_item--active @endif"
                                   style="color: @if(request()->path() == $module->options['nav']['link']) var(--color-primary) @else var(--color-mobile-nav-text) @endif;">
                                    <div class="relative">
                                        <i class="mobile-bottom-nav_item-icon {{$module->options['nav']['icon']}} text-2xl group-hover:scale-110 transition-transform duration-200"></i>
                                        @if(isset($notifications) and $notifications->where('type', $module->options['nav']['name'])->where('read',0)->count() > 0)
                                        <span class="absolute -top-1 -right-2 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white rounded-full animate-pulse border-2"
                                              style="background-color: var(--color-badge-bg); border-color: var(--color-mobile-nav-bg, #ffffff);">
                                                {{$notifications->where('type', $module->options['nav']['name'])->where('read',0)->count()}}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="mobile-bottom-nav_item-text text-[10px] font-semibold mt-0.5 truncate max-w-[60px]">
                                        {{$module->options['nav']['name']}}
                                    </span>
                                </a>
                            </div>
                        </div>
                    @endpush
                @endif
            @endif
            @if(array_key_exists('adm-nav', $module->options) and is_array($module->options['adm-nav']) and isset($module->options['adm-nav']['adm-rights']))
                @php
                    $hasAdmPermission = false;
                    foreach ($module->options['adm-nav']['adm-rights'] as $permission) {
                        if (auth()->user()->can($permission)) {
                            $hasAdmPermission = true;
                            break;
                        }
                    }
                @endphp

                @if($hasAdmPermission)
                    @push('adm-nav')
                        @if(!isset($__admNavHeaderPushed))
                            @php $__admNavHeaderPushed = true; @endphp
                            <li class="px-3 pt-3 pb-2">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 border-t" style="border-color: var(--color-sidebar-admin-border, rgba(255,255,255,0.15))"></div>
                                    <span class="text-[9px] font-bold uppercase tracking-widest flex items-center gap-1.5 px-1"
                                          style="color: var(--color-sidebar-admin-label, rgba(255,255,255,0.4))">
                                        <i class="fas fa-shield-alt text-[9px]"></i>
                                        Verwaltung
                                    </span>
                                    <div class="flex-1 border-t" style="border-color: var(--color-sidebar-admin-border, rgba(255,255,255,0.15))"></div>
                                </div>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{url($module->options['adm-nav']['link'])}}"
                               class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg sidebar-nav-link sidebar-admin-link transition-all duration-200 @if(request()->path() == $module->options['adm-nav']['link']) sidebar-active @endif group">
                                <i class="{{$module->options['adm-nav']['icon']}} text-base group-hover:scale-110 transition-transform"
                                   style="color: var(--color-sidebar-admin-icon, inherit)"></i>
                                <span class="font-medium">{{$module->options['adm-nav']['name']}}</span>
                            </a>
                        </li>
                    @endpush
                @endif
            @endif
        @endif

    @endforeach
    @endif
@endauth
@push('js')
    <script>
        function markNotificationAsRead(url, type) {
            $.ajax({
                type: "POST",
                url: url,
                data: {
                    "_token": "{{ csrf_token() }}",
                    "type": type
                },
            });
        }
    </script>
@endpush

