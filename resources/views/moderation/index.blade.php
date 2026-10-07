@extends('layouts.app')

@section('title', '| Moderation')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-shield-alt text-red-600"></i>
                Moderation
            </h1>
            <p class="text-sm text-gray-600 mt-1">Gemeldete Beiträge und Nachrichten prüfen und bearbeiten</p>
        </div>
        <div class="text-sm text-gray-500">
            <span class="font-semibold text-gray-700">{{ $resolvedCount }}</span> bereits gelöst
        </div>
    </div>

    @if(session('Meldung'))
        <div class="mb-4 p-4 @if(session('type') == 'success') bg-green-50 border-l-4 border-green-500 text-green-800 @elseif(session('type') == 'danger') bg-red-50 border-l-4 border-red-500 text-red-800 @else bg-blue-50 border-l-4 border-blue-500 text-blue-800 @endif rounded-lg text-sm">
            {{ session('Meldung') }}
        </div>
    @endif

    @if(count($tabs) > 1)
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex gap-2 -mb-px">
                @foreach($tabs as $key => $tab)
                    <a href="{{ route('moderation.index', ['tab' => $key]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 border-b-2 text-sm font-medium transition-colors
                              @if($key === $active) border-red-600 text-red-700 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif">
                        <i class="{{ $tab['icon'] }}"></i>
                        {{ $tab['label'] }}
                        @if($tab['open'] > 0)
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-600 rounded-full">
                                {{ $tab['open'] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
    @else
        <h2 class="mb-4 text-lg font-semibold text-gray-700 flex items-center gap-2">
            <i class="{{ $tabs[$active]['icon'] }}"></i> Gemeldete {{ $tabs[$active]['label'] }}
        </h2>
    @endif

    @if($active === \App\Http\Controllers\ModerationController::TAB_POSTS)
        @include('moderation._posts')
    @else
        @include('moderation._messages')
    @endif
</div>
@endsection
