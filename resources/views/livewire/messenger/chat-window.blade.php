<div class="bg-white rounded-lg shadow h-full overflow-hidden flex flex-col" wire:poll.5s="refresh">
    @if(!$conversation)
    <div class="flex-1 flex items-center justify-center text-gray-400">
        <div class="text-center p-6">
            <i class="fas fa-comments text-4xl mb-3 block"></i>
            <p class="text-sm">Wähle eine Konversation aus</p>
        </div>
    </div>
    @else
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex items-center gap-3">
        <div class="w-9 h-9 rounded-full flex items-center justify-center"
             style="background-color: var(--color-widget-primary-bg)">
            <i class="{{ $conversation->type === 'group' ? 'fas fa-users' : 'fas fa-user' }} text-sm"
               style="color: var(--color-primary)"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-800 truncate text-sm">{{ $conversation->displayNameFor(auth()->id()) }}</p>
            <p class="text-xs text-gray-500">{{ $conversation->users->count() }} Teilnehmer</p>
        </div>
    </div>

    <!-- Nachrichten -->
    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="lw-msg-container">
        @forelse($messages as $message)
        @php /** @var \App\Model\Message $message */ $isOwn = $message->sender_id === auth()->id(); @endphp
        <div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-xs">
                @unless($isOwn)
                <p class="text-xs text-gray-500 mb-0.5 ml-2">{{ $message->sender?->name }}</p>
                @endunless
                @if($message->replyTo)
                <div class="mb-1 px-2 py-1 rounded text-xs text-gray-500 border-l-4"
                     style="{{ $isOwn ? 'border-color: var(--color-primary); background-color: var(--color-widget-primary-bg)' : 'border-color: #d1d5db; background-color: #f9fafb' }}">
                    {{ Str::limit($message->replyTo->trashed() ? '[gelöscht]' : $message->replyTo->body, 60) }}
                </div>
                @endif
                <div class="px-3 py-2 rounded-2xl text-sm {{ $isOwn ? 'text-white rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-bl-sm' }}"
                     @if($isOwn) style="background-color: var(--color-primary)" @endif>
                    {{ $message->body }}
                </div>
                <div class="flex items-center gap-2 mt-0.5 {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                    <span class="text-[10px] text-gray-400">{{ $message->created_at->format('H:i') }}</span>
                    <button wire:click="setReplyTo({{ $message->id }})"
                            class="text-gray-300 transition-colors" title="Antworten"
                            onmouseover="this.style.color='var(--color-primary)'"
                            onmouseout="this.style.color='#d1d5db'">
                        <i class="fas fa-reply text-[10px]"></i>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="flex items-center justify-center h-full text-gray-400 text-sm">
            Noch keine Nachrichten
        </div>
        @endforelse
    </div>

    <!-- Antwort-Preview -->
    @if($replyMessage)
    <div class="border-t border-gray-100 px-4 py-2 bg-gray-50 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
            <div class="w-1 h-6 rounded-full flex-shrink-0" style="background-color: var(--color-primary)"></div>
            <p class="text-xs text-gray-600 truncate">
                <span class="font-medium" style="color: var(--color-primary)">{{ $replyMessage->sender?->name }}:</span>
                {{ Str::limit($replyMessage->body, 60) }}
            </p>
        </div>
        <button wire:click="cancelReply()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>
    @endif

    <!-- Eingabe -->
    <div class="border-t border-gray-200 p-3">
        <form wire:submit="sendMessage" class="flex items-end gap-2">
            <div class="flex-1">
                <textarea wire:model.live="newMessage"
                          rows="1"
                          placeholder="Nachricht eingeben..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg outline-none resize-none text-sm"
                          style="transition: border-color 0.2s"
                          onfocus="this.style.borderColor='var(--color-primary)'; this.style.boxShadow='0 0 0 2px var(--color-primary-light)'"
                          onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow=''"
                          oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"
                          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();$wire.sendMessage()}"></textarea>
                @error('newMessage')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-white transition-colors flex-shrink-0"
                    style="background-color: var(--color-primary)"
                    onmouseover="this.style.backgroundColor='var(--color-primary-dark)'"
                    onmouseout="this.style.backgroundColor='var(--color-primary)'">
                <i class="fas fa-paper-plane text-sm"></i>
            </button>
        </form>
    </div>
    @endif
</div>

@script
<script>
// Beim neuen Rendern zum Ende scrollen
document.addEventListener('livewire:updated', function() {
    const c = document.getElementById('lw-msg-container');
    if (c) c.scrollTop = c.scrollHeight;
});
</script>
@endscript
