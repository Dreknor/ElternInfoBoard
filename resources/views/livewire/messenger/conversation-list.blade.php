<div class="bg-white rounded-lg shadow h-full overflow-hidden flex flex-col">
    <div class="p-4 border-b border-gray-200">
        <h2 class="font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-comments text-blue-600"></i>
            Konversationen
        </h2>
    </div>
    <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
        @forelse($conversations as $conv)
        <button wire:click="selectConversation({{ $conv->id }})"
                class="w-full text-left p-4 hover:bg-blue-50 transition-colors
                    {{ $conversationId === $conv->id ? 'bg-blue-50 border-l-4 border-blue-600' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center
                    {{ $conv->type === 'group' ? 'bg-blue-100' : 'bg-indigo-100' }}">
                    <i class="{{ $conv->type === 'group' ? 'fas fa-users text-blue-600' : 'fas fa-user text-indigo-600' }} text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-sm font-semibold text-gray-800 truncate">{{ $conv->display_name }}</span>
                        @if($conv->unread_count > 0)
                        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-red-500 rounded-full flex-shrink-0">
                            {{ $conv->unread_count }}
                        </span>
                        @endif
                    </div>
                    @if($conv->latestMessage)
                    <p class="text-xs text-gray-500 truncate mt-0.5">
                        {{ Str::limit($conv->latestMessage->body, 40) }}
                    </p>
                    @endif
                </div>
            </div>
        </button>
        @empty
        <div class="p-6 text-center text-gray-400 text-sm">
            Noch keine Konversationen
        </div>
        @endforelse
    </div>
</div>

