@extends('layouts.app')

@section('content')
    <div class="w-full max-w-7xl mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800 @if($liste->active == 0) from-cyan-500 to-cyan-600 border-cyan-700 @endif">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-list-check"></i>
                        {{ $liste->listenname }}
                        @if($liste->active == 0)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-sm font-semibold">
                                <i class="fas fa-exclamation-circle"></i>
                                inaktiv
                            </span>
                        @endif
                    </h2>
                </div>
                @if($liste->comment)
                    <p class="text-blue-100">{{ $liste->comment }}</p>
                @endif
            </div>

            <!-- Body -->
            <div class="px-6 py-6">
                <!-- Content will be populated by specific list type views -->
            </div>
        </div>
    </div>
@endsection
