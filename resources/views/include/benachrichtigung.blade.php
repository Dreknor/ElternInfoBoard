<div x-data="{ open: false, showRead: false }" class="relative">
    @if(count($notifications) == 0)
        <!-- No Notifications -->
        <button type="button"
                class="relative p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200"
                title="Keine neuen Benachrichtigungen vorhanden">
            <i class="fas fa-bell text-xl"></i>
        </button>
    @else
        <!-- Notifications Bell with Badge -->
        <button type="button"
                @click="open = !open"
                @click.away="open = false"
                class="relative p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200">
            <i class="far fa-bell text-xl"></i>
            @if($notifications->where('read', 0)->count() > 0)
                <span class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse">
                    {{$notifications->where('read', 0)->count()}}
                </span>
            @endif
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 mt-2 w-screen max-w-sm md:max-w-md bg-white rounded-lg shadow-2xl border border-gray-200 overflow-hidden z-50"
             style="display: none; max-height: calc(100vh - 80px);"
             @click.away="open = false">

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 flex items-center justify-between">
                <h6 class="text-white font-bold text-base mb-0">
                    <i class="fas fa-bell mr-2"></i>
                    Benachrichtigungen
                </h6>
                @if($notifications->where('read', 0)->count() > 0)
                    <a href="{{route('notification.readAll')}}"
                       class="text-xs text-white hover:text-blue-100 underline transition-colors">
                        Alle als gelesen markieren
                    </a>
                @endif
            </div>

            <!-- Filter Toggle -->
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer" @click.stop>
                    <input type="checkbox"
                           x-model="showRead"
                           class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 cursor-pointer">
                    <span class="text-sm text-gray-700 select-none">Gelesene anzeigen</span>
                </label>
                <span class="text-xs text-gray-500">{{$notifications->count()}} insgesamt</span>
            </div>

            <!-- Notifications List -->
            <div class="overflow-y-auto" style="max-height: 400px;">
                @if($notifications->count() > 0)
                    @foreach($notifications->sortBy('read') as $item)
                        <div x-show="!{{$item->read ? 'true' : 'false'}} || showRead"
                             id="notification-{{$item->id}}"
                             class="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150">
                            <a href="{{$item['url']}}"
                               onclick="readNotification({{$item->id}})"
                               class="block px-4 py-3 text-decoration-none @if(!$item->read) bg-blue-50 border-l-4 border-l-blue-500 @else opacity-60 @endif">
                                <div class="flex gap-3">
                                    @if($item['icon'])
                                        <div class="flex-shrink-0">
                                            <img src="{{$item['icon']}}"
                                                 alt="Icon"
                                                 class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
                                        </div>
                                    @else
                                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                            <i class="fas fa-bell text-white"></i>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 mb-1 line-clamp-1">
                                            {{$item['title']}}
                                        </p>
                                        <p class="text-xs text-gray-600 mb-0 line-clamp-2">
                                            {{$item['message']}}
                                        </p>
                                        @if($item->created_at)
                                            <p class="text-xs text-gray-400 mt-1 mb-0">
                                                <i class="far fa-clock mr-1"></i>
                                                {{$item->created_at->diffForHumans()}}
                                            </p>
                                        @endif
                                    </div>
                                    @if(!$item->read)
                                        <div class="flex-shrink-0">
                                            <span class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                                        </div>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                @else
                    <div class="px-4 py-8 text-center">
                        <i class="fas fa-bell-slash text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500 text-sm mb-0">Keine Benachrichtigungen vorhanden</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            @can('testing')
                <div class="bg-gray-50 border-t border-gray-200 px-4 py-2">
                    <a href="{{route('push.test')}}"
                       class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-vial mr-1"></i>
                        Push-Benachrichtigung testen
                    </a>
                </div>
            @endcan
        </div>
    @endif
</div>

@push('js')
    <script>
        function readNotification(id) {
            $.ajax({
                url: "{{route('notification.read')}}",
                type: "POST",
                data: {
                    id: id,
                    _token: "{{csrf_token()}}"
                },
                success: function (data) {
                    if (data.success) {
                        // Fade out the notification smoothly
                        $('#notification-' + id).fadeOut(300, function() {
                            $(this).remove();

                            // Update badge count if needed
                            var badge = $('.animate-pulse');
                            if (badge.length) {
                                var currentCount = parseInt(badge.text());
                                if (currentCount > 1) {
                                    badge.text(currentCount - 1);
                                } else {
                                    badge.closest('button').html('<i class="fas fa-bell text-xl"></i>');
                                }
                            }
                        });
                    }
                }
            });
        }
    </script>
@endpush
