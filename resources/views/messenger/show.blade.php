@extends('layouts.app')

@section('title', '| ' . $display_name)

@section('content')
<div class="container-fluid px-4 py-6">

    {{-- Globale Flash-Meldungen (Melden, Löschen, Bearbeiten etc.) --}}
    @if(session('Meldung'))
    <div x-data="{ show: true }" x-show="show" x-cloak
         class="mb-4 p-3 rounded-lg text-sm flex items-center justify-between gap-3
             {{ session('type') === 'success' ? 'bg-green-50 border border-green-200 text-green-800' :
                (session('type') === 'warning' ? 'bg-amber-50 border border-amber-200 text-amber-800' :
                'bg-red-50 border border-red-200 text-red-800') }}">
        <span><i class="fas {{ session('type') === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' }} mr-1.5"></i>{{ session('Meldung') }}</span>
        <button @click="show = false" class="opacity-60 hover:opacity-100"><i class="fas fa-times"></i></button>
    </div>
    @endif

    <!-- Header -->
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('messenger.index') }}"
           class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center
                {{ $conversation->type === 'group' ? 'bg-blue-100' : 'bg-indigo-100' }}">
                <i class="{{ $conversation->type === 'group' ? 'fas fa-users text-blue-600' : 'fas fa-user text-indigo-600' }}"></i>
            </div>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-gray-800 truncate">{{ $display_name }}</h1>
                <p class="text-xs text-gray-500">
                    {{ $conversation->users->count() }} Teilnehmer
                </p>
            </div>
        </div>
        <!-- Stummschalten -->
        <form action="{{ route('messenger.mute', $conversation) }}" method="POST" class="ml-auto">
            @csrf
            @php
                $myPivot = $conversation->users->where('id', auth()->id())->first()?->pivot;
                $isMuted = $myPivot && $myPivot->muted_until && now()->lessThan($myPivot->muted_until);
            @endphp
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $isMuted ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    title="{{ $isMuted ? 'Stummschaltung aufheben' : 'Für 24h stummschalten' }}">
                <i class="{{ $isMuted ? 'fas fa-bell-slash' : 'fas fa-bell' }}"></i>
                {{ $isMuted ? 'Stummgeschaltet' : 'Stummschalten' }}
            </button>
        </form>
    </div>

    <!-- Nachrichtenverlauf -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col" style="height: calc(100vh - 280px); min-height: 400px;">
        <div id="messageContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
            {{-- Ältere Nachrichten laden --}}
            @if($messages->hasMorePages())
            <div class="text-center mb-4">
                <a href="?page={{ $messages->currentPage() + 1 }}"
                   class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                    <i class="fas fa-chevron-up mr-1"></i>Ältere Nachrichten laden
                </a>
            </div>
            @endif

            @forelse($messages->reverse() as $message)
            @php $isOwn = $message->sender_id === auth()->id(); @endphp
            <div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }} group" id="msg-{{ $message->id }}">
                <div class="max-w-xs lg:max-w-md xl:max-w-lg">
                    {{-- Absender (nur bei fremden Nachrichten) --}}
                    @unless($isOwn)
                    <p class="text-xs text-gray-500 mb-1 ml-2">{{ $message->sender?->name }}</p>
                    @endunless

                    {{-- Antwort-Vorschau --}}
                    @if($message->replyTo)
                    <div class="mb-1 px-3 py-1.5 rounded-lg border-l-4 {{ $isOwn ? 'border-blue-300 bg-blue-50' : 'border-gray-300 bg-gray-50' }} text-xs text-gray-600">
                        <span class="font-medium">{{ $message->replyTo->sender?->name }}:</span>
                        {{ $message->replyTo->trashed() ? '[gelöscht]' : Str::limit($message->replyTo->body, 80) }}
                    </div>
                    @endif

                    {{-- Nachrichtenblase --}}
                    <div class="px-4 py-2.5 rounded-2xl {{ $isOwn
                        ? 'bg-blue-600 text-white rounded-br-sm'
                        : 'bg-gray-100 text-gray-800 rounded-bl-sm' }}">
                        {{-- Anhang --}}
                        @if($message->type !== 'text')
                        @php $media = $message->getFirstMedia('message-attachments'); @endphp
                        @if($media)
                            @if($message->type === 'image')
                            <a href="{{ route('messenger.attachment', $message) }}" target="_blank" class="block mb-2">
                                <img src="{{ route('messenger.attachment', $message) }}"
                                     alt="{{ $media->file_name }}"
                                     loading="lazy"
                                     class="rounded-lg max-w-full cursor-zoom-in"
                                     style="max-height: 300px; object-fit: contain;"
                                     onerror="this.parentElement.innerHTML='<span class=\'text-xs opacity-75 flex items-center gap-1\'><i class=\'fas fa-image\'></i> {{ e($media->file_name) }}</span>'">
                            </a>
                            @else
                            <a href="{{ route('messenger.attachment', $message) }}"
                               download="{{ $media->file_name }}"
                               class="flex items-center gap-2 p-2 rounded-lg {{ $isOwn ? 'bg-blue-500 hover:bg-blue-400' : 'bg-gray-200 hover:bg-gray-300' }} mb-2 transition-colors">
                                @php
                                    $ext = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));
                                    $icon = match($ext) {
                                        'pdf'           => 'fa-file-pdf text-red-400',
                                        'doc', 'docx'   => 'fa-file-word text-blue-400',
                                        'xls', 'xlsx'   => 'fa-file-excel text-green-400',
                                        default         => 'fa-file text-gray-300',
                                    };
                                @endphp
                                <i class="fas {{ $icon }} text-lg flex-shrink-0"></i>
                                <div class="min-w-0">
                                    <span class="text-sm truncate block">{{ $media->file_name }}</span>
                                    <span class="text-xs opacity-75">{{ number_format($media->size / 1024, 1) }} KB</span>
                                </div>
                                <i class="fas fa-download ml-auto opacity-75 text-sm flex-shrink-0"></i>
                            </a>
                            @endif
                        @endif
                        @endif

                        <p class="text-sm leading-relaxed whitespace-pre-wrap break-words">{{ $message->body }}</p>
                    </div>

                    {{-- Zeitstempel & Aktionen --}}
                    <div class="flex items-center gap-2 mt-1 {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                        <span class="text-[10px] text-gray-400">
                            {{ $message->created_at->format('H:i') }}
                            @if($message->edited_at)
                                · <em>bearbeitet</em>
                            @endif
                        </span>
                        <div class="hidden group-hover:flex items-center gap-1">
                            {{-- Antworten --}}
                            <button onclick="setReplyTo({{ $message->id }}, '{{ addslashes($message->sender?->name) }}', '{{ addslashes(Str::limit($message->body, 60)) }}')"
                                    class="text-gray-400 hover:text-blue-600 transition-colors" title="Antworten">
                                <i class="fas fa-reply text-xs"></i>
                            </button>
                            @if($isOwn && $message->isEditableBy(auth()->user()))
                            <button onclick="openEditModal({{ $message->id }}, '{{ addslashes($message->body) }}')"
                                    class="text-gray-400 hover:text-green-600 transition-colors" title="Bearbeiten">
                                <i class="fas fa-pencil-alt text-xs"></i>
                            </button>
                            @endif
                            @if($message->isDeletableBy(auth()->user()))
                            <form action="{{ route('messenger.delete', $message) }}" method="POST"
                                  onsubmit="return confirm('Nachricht löschen?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Löschen">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                            @endif
                            @if(! $isOwn)
                            <button onclick="openReportModal({{ $message->id }})"
                                    class="text-gray-400 hover:text-orange-500 transition-colors" title="Melden">
                                <i class="fas fa-flag text-xs"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="flex items-center justify-center h-full text-gray-400">
                <div class="text-center">
                    <i class="fas fa-comments text-4xl mb-3 block"></i>
                    <p class="text-sm">Noch keine Nachrichten. Schreib die erste!</p>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Antwort-Preview --}}
        <div id="replyPreview" class="hidden border-t border-gray-100 px-4 py-2 bg-gray-50 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-1 h-8 bg-blue-500 rounded-full flex-shrink-0"></div>
                <div class="min-w-0">
                    <p id="replyName" class="text-xs font-semibold text-blue-600 truncate"></p>
                    <p id="replyBody" class="text-xs text-gray-500 truncate"></p>
                </div>
            </div>
            <button onclick="cancelReply()" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Eingabefeld --}}
        <div class="border-t border-gray-200 p-3">
            <form action="{{ route('messenger.send', $conversation) }}" method="POST"
                  enctype="multipart/form-data" id="messageForm">
                @csrf
                <input type="hidden" name="reply_to_id" id="replyToInput" value="">

                @if($errors->any())
                <p class="text-red-500 text-xs mb-2">{{ $errors->first() }}</p>
                @endif

                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <textarea name="body"
                                  id="messageInput"
                                  rows="1"
                                  placeholder="Nachricht eingeben..."
                                  maxlength="{{ app(\App\Settings\MessengerSetting::class)->max_message_length }}"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none text-sm"
                                  oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px'"></textarea>
                    </div>
                    @if(app(\App\Settings\MessengerSetting::class)->allow_file_uploads)
                    <label class="cursor-pointer inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors flex-shrink-0" title="Datei anhängen">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" name="attachment" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                               onchange="document.getElementById('fileLabel').textContent = this.files[0]?.name ?? ''">
                    </label>
                    @endif
                    <button type="submit"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors flex-shrink-0">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <p id="fileLabel" class="text-xs text-gray-500 mt-1 truncate"></p>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Nachricht bearbeiten --}}
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-800"><i class="fas fa-pencil-alt mr-2 text-green-600"></i>Nachricht bearbeiten</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="editForm" method="POST" class="p-4">
            @csrf @method('PUT')
            <textarea name="body" id="editBody" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none resize-none text-sm mb-3"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">Abbrechen</button>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors">Speichern</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Nachricht melden --}}
<div id="reportModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-800"><i class="fas fa-flag mr-2 text-orange-500"></i>Nachricht melden</h3>
            <button onclick="document.getElementById('reportModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="reportForm" method="POST" class="p-4">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-2">Grund der Meldung:</label>
            <textarea name="reason" rows="3" required maxlength="500"
                      placeholder="Beschreibe kurz, warum du diese Nachricht meldest..."
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none resize-none text-sm mb-3"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('reportModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">Abbrechen</button>
                <button type="submit"
                        class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm transition-colors">Melden</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
let replyToId = null;

function setReplyTo(id, name, body) {
    replyToId = id;
    document.getElementById('replyToInput').value = id;
    document.getElementById('replyName').textContent = name;
    document.getElementById('replyBody').textContent = body;
    document.getElementById('replyPreview').classList.remove('hidden');
    document.getElementById('messageInput').focus();
}

function cancelReply() {
    replyToId = null;
    document.getElementById('replyToInput').value = '';
    document.getElementById('replyPreview').classList.add('hidden');
}

function openEditModal(id, body) {
    document.getElementById('editForm').action = '/messenger/message/' + id;
    document.getElementById('editBody').value = body;
    document.getElementById('editModal').classList.remove('hidden');
}

function openReportModal(id) {
    document.getElementById('reportForm').action = '/messenger/message/' + id + '/report';
    document.getElementById('reportModal').classList.remove('hidden');
}

// Zum Ende scrollen
window.addEventListener('load', function() {
    const container = document.getElementById('messageContainer');
    if (container) container.scrollTop = container.scrollHeight;
});
</script>
@endpush
@endsection

