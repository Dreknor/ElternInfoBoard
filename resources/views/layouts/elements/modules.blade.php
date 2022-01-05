@auth
    @foreach($modules as $module)

        @if(count($module->options['rights']) == 0 or auth()->user()->hasAnyPermission($module->options['rights']))

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


            @if(array_key_exists('nav-user', $module->options) and  is_array($module->options['nav-user']) )
                @push('nav-user')
                    <li>
                        <a class="dropdown-item" href="{{url($module->options['nav-user']['link'])}}">
                            {{$module->options['nav-user']['name']}}
                        </a>
                    </li>
                @endpush
            @endif
            @if(array_key_exists('nav', $module->options) and  is_array($module->options['nav']))

                @push('nav')
                    <li class="@if(request()->path() ==$module->options['nav']['link']) active @endif">
                        <a href="{{url($module->options['nav']['link'])}}">
                            <i class="{{$module->options['nav']['icon']}}"></i>
                            <p>{{$module->options['nav']['name'] }}</p>
                        </a>
                    </li>
                @endpush

                @if(isset($module->options['nav']['bottom-nav']) and $module->options['nav']['bottom-nav'] == "true")
                    @push('bottom-nav')
                        <div
                            class="mobile-bottom-nav_item @if(request()->path() == $module->options['nav']['link']) mobile-bottom-nav_item--active @endif">
                            <div class="mobile-bottom-nav_item-content">
                                <a href="{{url($module->options['nav']['link'])}}">
                                    <i class=" mobile-bottom-nav_item-icon {{$module->options['nav']['icon']}}"></i>
                                    <span class="mobile-bottom-nav_item-text">
                                        {{$module->options['nav']['name'] }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    @endpush
                @endif
            @endif

            @if(array_key_exists('adm-nav', $module->options) and  is_array($module->options['adm-nav']) and isset($module->options['adm-nav']['adm-rights']) and auth()->user()->hasAnyPermission($module->options['adm-nav']['adm-rights']))
                @push('adm-nav')
                    <li class="@if(request()->path() == $module->options['adm-nav']['link']) active @endif">
                        <a href="{{url($module->options['adm-nav']['link'])}}">
                            <i class="{{$module->options['adm-nav']['icon']}}"></i>
                            <p>{{$module->options['adm-nav']['name']}}</p>
                        </a>
                    </li>
                @endpush
            @endif
        @endif
    @endforeach
@endauth
@push('js')
@endpush
