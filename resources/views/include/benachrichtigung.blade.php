<div x-data="{ open: false, showRead: false }" class="relative">
    @if(count($notifications) == 0)
        <!-- No Notifications -->
        <button type="button"
                class="relative p-2 rounded-lg transition-all duration-200"
                style="color: var(--color-mobile-nav-text);"
                onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary');this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary-light')"
                onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-mobile-nav-text');this.style.backgroundColor=''"
                title="Keine neuen Benachrichtigungen vorhanden">
            <i class="fas fa-bell text-xl"></i>
        </button>
    @else
        <!-- Notifications Bell with Badge -->
        <button type="button"
                @click="open = !open"
                aria-expanded="false"
                x-bind:aria-expanded="open"
                class="relative p-2 rounded-lg transition-all duration-200"
                style="color: var(--color-mobile-nav-text);"
                onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary');this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary-light')"
                onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-mobile-nav-text');this.style.backgroundColor=''" >
            <i class="far fa-bell text-xl"></i>
            @if($notifications->where('read', 0)->count() > 0)
                <span data-notification-badge
                      class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white rounded-full animate-pulse"
                      style="background-color: var(--color-badge-bg);">
                    {{$notifications->where('read', 0)->count()}}
                </span>
            @endif
        </button>

        <!-- Dropdown Menu -->
        <div x-cloak x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="fixed left-2 right-2 top-14 max-h-[85vh] sm:bottom-auto sm:absolute sm:mt-2 sm:right-0 sm:left-auto sm:w-96 sm:max-w-[calc(100vw-2rem)] md:max-w-md rounded-lg shadow-2xl border overflow-hidden z-50 flex flex-col"
             style="background-color: var(--color-card-bg); border-color: var(--color-card-border);"
             @click.away="open = false">

            <!-- Header -->
            <div class="px-4 py-3 flex items-center justify-between flex-shrink-0"
                 style="background-color: var(--color-primary);">
                <h6 class="text-white font-bold text-base mb-0 flex items-center">
                    <i class="fas fa-bell mr-2"></i>
                    Benachrichtigungen
                </h6>
                <div class="flex items-center gap-2">
                    @if($notifications->where('read', 0)->count() > 0)
                        <a href="{{route('notification.readAll')}}"
                           class="text-xs text-white underline transition-colors hidden sm:inline"
                           style="opacity: 0.85;"
                           onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'">
                             Alle als gelesen markieren
                        </a>
                    @endif
                    <button @click="open = false"
                            class="text-white transition-colors p-1 rounded hover:bg-white/10"
                            title="Schließen">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Filter Toggle -->
            <div class="px-4 py-3 border-b flex items-center justify-between flex-shrink-0"
                 style="background-color: var(--color-body-bg); border-color: var(--color-card-border);">
                <label class="flex items-center gap-2 cursor-pointer" @click.stop>
                    <input type="checkbox"
                           x-model="showRead"
                           class="w-4 h-4 rounded cursor-pointer"
                           style="accent-color: var(--color-primary);">
                    <span class="text-sm select-none" style="color: var(--color-text-secondary);">Gelesene anzeigen</span>
                </label>
                <span class="text-xs" style="color: var(--color-text-secondary);">{{$notifications->count()}} insgesamt</span>
            </div>

            <!-- Notifications List -->
            <div class="overflow-y-auto flex-1 min-h-0">
                @if($notifications->count() > 0)
                    @foreach($notifications->sortBy('read') as $item)
                        <div x-show="!{{$item->read ? 'true' : 'false'}} || showRead"
                             id="notification-{{$item->id}}"
                             class="border-b transition-colors duration-150"
                             style="border-color: var(--color-card-border);"
                             onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-navbar-user-btn-bg')"
                             onmouseout="this.style.backgroundColor=''">
                            <a href="{{$item['url']}}"
                               onclick="readNotification({{$item->id}})"
                               class="block px-4 py-3 text-decoration-none @if(!$item->read) border-l-4 @else opacity-60 @endif"
                               style="@if(!$item->read) background-color: var(--color-primary-light); border-left-color: var(--color-primary); @endif">
                                <div class="flex gap-3">
                                    @if($item['icon'])
                                        <div class="flex-shrink-0">
                                            <img src="{{$item['icon']}}"
                                                 alt="Icon"
                                                 class="w-12 h-12 rounded-full object-cover border-2"
                                                 style="border-color: var(--color-card-border);">
                                        </div>
                                    @else
                                        <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
                                             style="background-color: var(--color-avatar-bg);">
                                            <i class="fas fa-bell text-white"></i>
                                        </div>
                                    @endif
                                        <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold mb-1 line-clamp-1" style="color: var(--color-text-primary);">
                                            {{$item['title']}}
                                        </p>
                                        <p class="text-xs line-clamp-2 mb-1" style="color: var(--color-text-secondary);">
                                            {{$item['message']}}
                                        </p>
                                        <span class="text-xs" style="color: var(--color-text-secondary); opacity: 0.7;">
                                            {{$item->created_at?->diffForHumans()}}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                @else
                    <div class="px-4 py-8 text-center">
                        <i class="fas fa-bell-slash text-3xl mb-2 block" style="color: var(--color-text-secondary); opacity: 0.5;"></i>
                        <p class="text-sm" style="color: var(--color-text-secondary);">Keine Benachrichtigungen</p>
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-4 py-3 border-t flex-shrink-0"
                 style="background-color: var(--color-body-bg); border-color: var(--color-card-border);">
                <a href="{{ route('notification.readAll') }}"
                   class="block text-center text-xs font-medium transition-colors"
                   style="color: var(--color-primary);"
                   onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    Alle als gelesen markieren
                </a>
            </div>
        </div>
    @endif
</div>

