@extends('layouts.app')
@section('title') - Changelog @endsection

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 border-b border-purple-800">
                <h5 class="text-xl font-bold text-white mb-0 flex items-center gap-2">
                    <i class="fas fa-history"></i>
                    Letzte Änderungen am {{config('app.name')}}
                </h5>
            </div>

            @can('add changelog')
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <a href="{{url('changelog/create')}}"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-plus"></i>
                        <span>Neues Changelog erstellen</span>
                    </a>
                </div>
            @endcan

            <div class="divide-y divide-gray-200">
                @forelse($changelogs as $changelog)
                    <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 border-2 border-amber-300 rounded-xl shadow-md overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg">
                                        <i class="fas fa-bullhorn text-lg text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h6 class="text-lg font-bold text-white mb-0">
                                            {{$changelog->header}}
                                        </h6>
                                        <p class="text-xs text-amber-100 mb-0 mt-0.5">
                                            <i class="fas fa-clock mr-1"></i>
                                            {{$changelog->updated_at->locale('de')->isoFormat('DD. MMMM YYYY, HH:mm')}} Uhr
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="px-5 py-4">
                                <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed changelog-content">
                                    {!! $changelog->text !!}
                                </div>
                            </div>
                            <div class="px-5 py-2.5 bg-gradient-to-r from-amber-100 to-orange-100 border-t border-amber-200">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-amber-800 mb-0 flex items-center gap-2">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Changelog-Eintrag vom {{$changelog->created_at->locale('de')->isoFormat('DD.MM.YYYY')}}</span>
                                    </p>
                                    @can('edit changelog')
                                        <div class="flex items-center gap-2">
                                            <a href="{{url('changelog/'.$changelog->id.'/edit')}}"
                                               class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                                                <i class="fas fa-edit"></i>
                                                <span>Bearbeiten</span>
                                            </a>
                                            <form action="{{url('changelog/'.$changelog->id)}}" method="POST" class="inline" onsubmit="return confirm('Möchten Sie diesen Changelog-Eintrag wirklich löschen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="fas fa-trash"></i>
                                                    <span>Löschen</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8">
                        <div class="flex flex-col items-center justify-center gap-3 text-gray-500">
                            <i class="fas fa-inbox text-5xl text-gray-300"></i>
                            <p class="text-lg font-medium text-gray-600 mb-0">Keine Changelog-Einträge vorhanden</p>
                            <p class="text-sm text-gray-500 mb-0">Es wurden noch keine Änderungen dokumentiert.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if($changelogs->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    {{$changelogs->links()}}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('css')
    <style>
        .changelog-content {
            line-height: 1.75;
        }
        .changelog-content p {
            margin-bottom: 1rem;
        }
        .changelog-content p:last-child {
            margin-bottom: 0;
        }
        .changelog-content ul,
        .changelog-content ol {
            margin: 0.75rem 0;
            padding-left: 1.5rem;
        }
        .changelog-content li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        .changelog-content li::marker {
            color: #f59e0b;
        }
        .changelog-content strong {
            color: #92400e;
            font-weight: 600;
        }
        .changelog-content a {
            color: #2563eb;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .changelog-content a:hover {
            color: #1d4ed8;
        }
        .changelog-content h1,
        .changelog-content h2,
        .changelog-content h3,
        .changelog-content h4 {
            color: #92400e;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .changelog-content h1:first-child,
        .changelog-content h2:first-child,
        .changelog-content h3:first-child,
        .changelog-content h4:first-child {
            margin-top: 0;
        }
        .changelog-content code {
            background: #fef3c7;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
            color: #92400e;
        }
        .changelog-content blockquote {
            border-left: 4px solid #fbbf24;
            padding-left: 1rem;
            margin: 1rem 0;
            color: #78350f;
            font-style: italic;
        }
    </style>
@endpush

