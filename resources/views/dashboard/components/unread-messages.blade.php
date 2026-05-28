{{-- Dashboard-Widget: Ungelesene Chat-Nachrichten --}}
@can('use messenger')
    @if(isset($unreadConversations) && $unreadConversations->count() > 0)
    <div class="col-12 mb-4">
        <div class="bg-indigo-50 border-l-4 border-indigo-500 rounded-lg shadow p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="font-bold text-indigo-800 mb-0">
                    <i class="fas fa-comments"></i>
                    Ungelesene Nachrichten
                    <span class="inline-flex items-center justify-center bg-indigo-600 text-white text-xs font-bold rounded-full px-2 py-0.5 ml-1">
                        {{ $unreadConversations->sum('unread') }}
                    </span>
                </h6>
                <a href="{{ route('messenger.index') }}"
                   class="text-sm text-indigo-600 font-semibold hover:text-indigo-800 text-decoration-none">
                    Alle Chats <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="space-y-2">
                @foreach($unreadConversations->take(5) as $conv)
                <a href="{{ route('messenger.show', $conv['id']) }}"
                   class="block p-3 bg-white rounded border border-indigo-200 hover:border-indigo-500 hover:shadow-md transition-all duration-200 text-decoration-none">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-1 min-w-0 mr-3">
                            <span class="font-semibold text-gray-800 block truncate">
                                <i class="fas fa-comment-alt text-indigo-500 mr-1"></i>
                                {{ $conv['name'] }}
                            </span>
                            @if($conv['last_message'])
                                <p class="text-xs text-gray-500 mt-1 mb-0 truncate">
                                    {{ Str::limit(strip_tags($conv['last_message']), 60) }}
                                </p>
                            @endif
                            @if($conv['last_at'])
                                <p class="text-xs text-gray-400 mt-0.5 mb-0">
                                    <i class="far fa-clock"></i> {{ $conv['last_at']->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                        <span class="inline-flex items-center justify-center bg-indigo-600 text-white text-xs font-bold rounded-full px-2 py-0.5 flex-shrink-0">
                            {{ $conv['unread'] }}
                        </span>
                    </div>
                </a>
                @endforeach
            </div>
            @if($unreadConversations->count() > 5)
                <div class="text-center mt-3">
                    <a href="{{ route('messenger.index') }}"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-comments"></i>
                        Alle {{ $unreadConversations->count() }} Konversationen anzeigen
                    </a>
                </div>
            @endif
        </div>
    </div>
    @endif
@endcan

