@extends('layouts.app')

@section('title', '| Nachrichten')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-comments" style="color: var(--color-primary)"></i>
                Eltern-Nachrichten
            </h1>
            <p class="text-sm text-gray-600 mt-1">Austausch mit anderen Eltern in deinen Gruppen</p>
        </div>
        @if($settings->allow_direct_messages ?? true)
        <button type="button"
                onclick="document.getElementById('newDirectModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 text-white rounded-lg transition-colors text-sm font-medium"
                style="background-color: var(--color-primary)"
                onmouseover="this.style.backgroundColor='var(--color-primary-dark)'"
                onmouseout="this.style.backgroundColor='var(--color-primary)'">
            <i class="fas fa-plus"></i>
            Neue Direktnachricht
        </button>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg text-green-800 text-sm">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg text-red-800 text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}
        </div>
    @endif

    @if($conversations->isEmpty())
        <!-- Leer-Zustand -->
        <div class="bg-white rounded-lg shadow-lg p-12 text-center">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
                 style="background-color: var(--color-widget-primary-bg)">
                <i class="fas fa-comments text-3xl" style="color: var(--color-primary)"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Noch keine Konversationen</h3>
            <p class="text-gray-500 text-sm max-w-sm mx-auto">
                Du bist noch keiner Gruppenkonversation beigetreten.
                Gruppenkonversationen werden automatisch erstellt, sobald ein Administrator das Feature für deine Gruppe aktiviert.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($conversations as $conv)
            <a href="{{ route('messenger.show', $conv) }}"
               class="bg-white rounded-lg shadow hover:shadow-md transition-all border border-gray-100 p-4 flex items-center gap-4 group"
               onmouseover="this.style.borderColor='var(--color-primary)'"
               onmouseout="this.style.borderColor='#f3f4f6'">
                <!-- Icon -->
                <div class="w-12 h-12 rounded-full flex-shrink-0 flex items-center justify-center"
                     style="background-color: var(--color-widget-primary-bg)">
                    <i class="{{ $conv->type === 'group' ? 'fas fa-users' : 'fas fa-user' }} text-xl"
                       style="color: var(--color-primary)"></i>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-semibold text-gray-800 truncate transition-colors"
                            style="color: inherit"
                            onmouseover="this.style.color='var(--color-primary)'"
                            onmouseout="this.style.color=''">
                            {{ $conv->display_name }}
                        </h3>
                        @if($conv->latestMessage)
                        <span class="text-xs text-gray-400 flex-shrink-0">
                            {{ $conv->latestMessage->created_at->diffForHumans() }}
                        </span>
                        @endif
                    </div>
                    @if($conv->latestMessage)
                    <p class="text-sm text-gray-500 truncate mt-0.5">
                        <span class="font-medium">{{ $conv->latestMessage->sender?->name }}:</span>
                        {{ Str::limit($conv->latestMessage->body, 60) }}
                    </p>
                    @else
                    <p class="text-sm text-gray-400 mt-0.5 italic">Noch keine Nachrichten</p>
                    @endif
                </div>
                <!-- Badge -->
                @if($conv->unread_count > 0)
                <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse flex-shrink-0">
                    {{ $conv->unread_count }}
                </span>
                @endif
            </a>
            @endforeach
        </div>
    @endif
</div>

<!-- Modal: Neue Direktnachricht -->
<div id="newDirectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-user-plus" style="color: var(--color-primary)"></i>
                Neue Direktnachricht
            </h3>
            <button onclick="document.getElementById('newDirectModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-5">
            @include('messenger.components.user-picker')
        </div>
    </div>
</div>
@endsection

