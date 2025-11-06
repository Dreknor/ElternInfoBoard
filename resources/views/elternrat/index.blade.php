@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-users text-indigo-600"></i>
                Elternrat
            </h1>
            <p class="text-sm text-gray-600 mt-1">Diskussionen, Dateien und Mitglieder des Elternrats</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content - Discussions -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Search & Filter Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="p-4">
                        <form action="{{url('elternrat')}}" method="GET" class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <div class="relative">
                                    <input type="text"
                                           name="search"
                                           value="{{request('search')}}"
                                           placeholder="Beiträge durchsuchen..."
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <select name="filter"
                                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <option value="all" {{request('filter') == 'all' ? 'selected' : ''}}>Alle</option>
                                    <option value="sticky" {{request('filter') == 'sticky' ? 'selected' : ''}}>Angeheftet</option>
                                    <option value="my" {{request('filter') == 'my' ? 'selected' : ''}}>Meine Beiträge</option>
                                </select>
                                <button type="submit"
                                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
                                    <i class="fas fa-filter"></i>
                                    <span class="hidden sm:inline">Filtern</span>
                                </button>
                                @if(request('search') || request('filter') != 'all')
                                    <a href="{{url('elternrat')}}"
                                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-2">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Active Filters Info -->
                @if(request('search') || (request('filter') && request('filter') != 'all'))
                    <div class="bg-blue-50 border-l-4 border-blue-600 rounded-lg p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-semibold text-blue-900 mb-1">Aktive Filter:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @if(request('search'))
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                                <i class="fas fa-search text-xs"></i>
                                                "{{request('search')}}"
                                            </span>
                                        @endif
                                        @if(request('filter') == 'sticky')
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">
                                                <i class="fas fa-thumbtack text-xs"></i>
                                                Nur angeheftete
                                            </span>
                                        @elseif(request('filter') == 'my')
                                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                                                <i class="fas fa-user text-xs"></i>
                                                Meine Beiträge
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-blue-700 mt-2">
                                        {{$themen->total()}} {{ $themen->total() == 1 ? 'Ergebnis' : 'Ergebnisse' }} gefunden
                                    </p>
                                </div>
                            </div>
                            <a href="{{url('elternrat')}}"
                               class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center gap-1 flex-shrink-0">
                                <i class="fas fa-times text-xs"></i>
                                <span>Zurücksetzen</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- New Post Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-comments"></i>
                            Beiträge
                        </h5>
                    </div>
                    <div class="p-6">
                        <a href="{{url('elternrat/discussion/create')}}"
                           class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-plus-circle"></i>
                            <span>Neuen Beitrag erstellen</span>
                        </a>
                    </div>
                </div>

                <!-- Discussion Posts -->
                @if($themen->count() > 0)
                    @foreach($themen as $beitrag)
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden @if($beitrag->sticky) ring-2 ring-indigo-500 @endif" x-data="{ commentsOpen: false }">
                            <!-- Post Header -->
                            <div class="@if($beitrag->sticky) bg-gradient-to-r from-indigo-500 to-purple-500 @else bg-gray-50 @endif px-6 py-4 border-b @if($beitrag->sticky) border-indigo-700 @else border-gray-200 @endif">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold @if($beitrag->sticky) text-white @else text-gray-800 @endif flex items-center gap-2">
                                            @if($beitrag->sticky)
                                                <i class="fas fa-thumbtack text-yellow-300"></i>
                                            @endif
                                            {{$beitrag->header}}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-4 mt-2 text-xs @if($beitrag->sticky) text-indigo-100 @else text-gray-600 @endif">
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-user"></i>
                                                {{$beitrag->author?->name}}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <i class="fas fa-clock"></i>
                                                {{$beitrag->updated_at?->format('d.m.Y H:i')}}
                                            </span>
                                        </div>
                                    </div>
                                    @if($beitrag->owner == auth()->user()->id)
                                        <a href="{{url('/elternrat/discussion/edit/'.$beitrag->id)}}"
                                           class="px-3 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors shadow-md hover:shadow-lg flex items-center gap-2"
                                           data-toggle="tooltip"
                                           data-placement="top"
                                           title="Nachricht bearbeiten">
                                            <i class="far fa-edit"></i>
                                            <span class="hidden sm:inline">Bearbeiten</span>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Post Content -->
                            <div class="p-6 prose max-w-none">
                                {!! $beitrag->text !!}
                            </div>

                            <!-- Comments Section (Collapsible) -->
                            <div class="border-t border-gray-200 overflow-hidden transition-all duration-300"
                                 x-show="commentsOpen"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 max-h-0"
                                 x-transition:enter-end="opacity-100 max-h-screen"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 max-h-screen"
                                 x-transition:leave-end="opacity-0 max-h-0"
                                 style="display: none;">
                                <div class="p-6 bg-gray-50">
                                    <ul class="space-y-4">
                                        @foreach($beitrag->comments?->sortByDesc('created_at') as $comment)
                                            <li class="bg-white rounded-lg p-4 shadow-sm @if ($loop->index % 2 == 0) border-l-4 border-indigo-500 @else border-l-4 border-purple-500 @endif">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="font-semibold text-gray-800">{{$comment->creator->name}}</span>
                                                    <span class="text-xs text-gray-500">{{$comment->created_at->diffForHumans()}}</span>
                                                </div>
                                                <p class="text-gray-700 text-sm">{{$comment->body}}</p>
                                            </li>
                                        @endforeach

                                        <!-- New Comment Form -->
                                        <li class="bg-white rounded-lg p-4 shadow-md">
                                            <form action="{{url("beitrag/$beitrag->id/comment/create")}}" method="post" class="space-y-3">
                                                @csrf
                                                <textarea
                                                    placeholder="Kommentar hier schreiben..."
                                                    name="body"
                                                    rows="3"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"></textarea>
                                                <button type="submit"
                                                        class="w-full sm:w-auto px-6 py-2 bg-gradient-to-r from-teal-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-teal-600 hover:to-teal-700 transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                                    <i class="fas fa-paper-plane"></i>
                                                    <span>Kommentieren</span>
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Post Footer -->
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all flex items-center gap-2 shadow-sm"
                                            type="button"
                                            @click="commentsOpen = !commentsOpen">
                                        <i class="fas fa-comment"></i>
                                        <span>{{$beitrag->commentCount()}} Kommentare</span>
                                        <i class="fas fa-chevron-down ml-1 transition-transform duration-200 transform"
                                           :class="commentsOpen ? 'rotate-180' : ''"></i>
                                    </button>

                                    @can('delete elternrat file')
                                        <form action="{{url('elternrat/discussion/'.$beitrag->id.'/delete')}}"
                                              method="post"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                                                <i class="fas fa-trash"></i>
                                                <span class="hidden sm:inline">Beitrag löschen</span>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- No Results Message -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="p-12 text-center">
                            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-bold text-gray-700 mb-2">Keine Beiträge gefunden</h3>
                            <p class="text-gray-500 mb-6">
                                @if(request('search'))
                                    Ihre Suche nach "<strong>{{request('search')}}</strong>" ergab keine Treffer.
                                @elseif(request('filter') == 'sticky')
                                    Es gibt derzeit keine angehefteten Beiträge.
                                @elseif(request('filter') == 'my')
                                    Sie haben noch keine Beiträge erstellt.
                                @else
                                    Es wurden noch keine Beiträge erstellt.
                                @endif
                            </p>

                        </div>
                    </div>
                @endif

                <!-- Pagination -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="p-4">
                        {{$themen->links()}}
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Stats Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 border-b border-green-800">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-chart-line"></i>
                            Aktionen
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{url('elternrat/add/file')}}"
                                  class="px-4 py-3 bg-gradient-to-r from-blue-500 to-cyan-500 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-blue-600 hover:to-cyan-600 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-file-upload"></i>
                                <span>Datei hochladen</span>
                            </a>

                            <a href="{{route('elternrat.tasks.index')}}"
                               class="px-4 py-3 bg-gradient-to-r from-rose-500 to-pink-500 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-rose-600 hover:to-pink-600 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-tasks"></i>
                                <span>Neue Aufgabe</span>
                            </a>
                            <a href="{{url('elternrat/discussion/create')}}"
                               class="px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-indigo-700 hover:to-purple-700 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-plus-circle"></i>
                                <span>Neuer Beitrag</span>
                            </a>
                            <a href="{{route('elternrat.events.create')}}"
                               class="px-4 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-orange-600 hover:to-red-600 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Neuer Termin</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events Widget -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4 border-b border-orange-800 flex items-center justify-between">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-calendar-alt"></i>
                            Termine
                        </h5>
                        <a href="{{route('elternrat.events.index')}}" class="text-white hover:text-orange-100 text-sm">
                            Alle →
                        </a>
                    </div>
                    <div class="divide-y divide-gray-200 max-h-64 overflow-y-auto">
                        @php
                            $upcomingEvents = \App\Model\ElternratEvent::where('start_time', '>=', now())
                                ->orderBy('start_time', 'asc')
                                ->limit(3)
                                ->get();
                        @endphp
                        @if($upcomingEvents->count() > 0)
                            @foreach($upcomingEvents as $event)
                                <div class="p-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start gap-2">
                                        <div class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-lg flex flex-col items-center justify-center">
                                            <span class="text-xs text-orange-800 font-bold">{{$event->start_time->format('d')}}</span>
                                            <span class="text-[10px] text-orange-600">{{$event->start_time->format('M')}}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">{{$event->title}}</p>
                                            <p class="text-xs text-gray-600 flex items-center gap-1 mt-1">
                                                <i class="fas fa-clock"></i>
                                                {{$event->start_time->format('H:i')}} Uhr
                                            </p>
                                            @if($event->location)
                                                <p class="text-xs text-gray-500 flex items-center gap-1">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    {{Str::limit($event->location, 20)}}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="p-4 text-center text-gray-500 text-sm">
                                <i class="fas fa-calendar-times text-2xl mb-2 text-gray-300"></i>
                                <p>Keine bevorstehenden Termine</p>
                            </div>
                        @endif
                    </div>
                    <div class="p-3 bg-gray-50 border-t border-gray-200">
                        <a href="{{route('elternrat.events.create')}}"
                           class="w-full px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 text-white font-semibold rounded-lg hover:from-orange-600 hover:to-red-600 transition-all flex items-center justify-center gap-2 text-sm">
                            <i class="fas fa-plus"></i>
                            <span>Termin erstellen</span>
                        </a>
                    </div>
                </div>

                <!-- Tasks Widget -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-4 border-b border-rose-800 flex items-center justify-between">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-tasks"></i>
                            Aufgaben
                        </h5>
                        <a href="{{route('elternrat.tasks.index')}}" class="text-white hover:text-rose-100 text-sm">
                            Alle →
                        </a>
                    </div>
                    <div class="divide-y divide-gray-200 max-h-64 overflow-y-auto">
                        @php
                            $openTasks = \App\Model\ElternratTask::where('status', '!=', 'completed')
                                ->orderBy('due_date', 'asc')
                                ->limit(3)
                                ->get();
                        @endphp
                        @if($openTasks->count() > 0)
                            @foreach($openTasks as $task)
                                <div class="p-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start gap-2">
                                        <div class="flex-shrink-0 mt-1">
                                            @if($task->priority === 'high')
                                                <i class="fas fa-exclamation-circle text-red-500"></i>
                                            @elseif($task->priority === 'medium')
                                                <i class="fas fa-circle text-amber-500"></i>
                                            @else
                                                <i class="fas fa-circle text-gray-400"></i>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">{{$task->title}}</p>
                                            @if($task->due_date)
                                                <p class="text-xs text-gray-600 flex items-center gap-1 mt-1">
                                                    <i class="fas fa-calendar"></i>
                                                    {{$task->due_date->format('d.m.Y')}}
                                                    @if($task->isOverdue())
                                                        <span class="text-red-600 font-semibold">(Überfällig)</span>
                                                    @elseif($task->isDueSoon())
                                                        <span class="text-amber-600">(Bald fällig)</span>
                                                    @endif
                                                </p>
                                            @endif
                                            @if($task->assignedUser)
                                                <p class="text-xs text-gray-500">→ {{$task->assignedUser->name}}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="p-4 text-center text-gray-500 text-sm">
                                <i class="fas fa-check-circle text-2xl mb-2 text-gray-300"></i>
                                <p>Keine offenen Aufgaben</p>
                            </div>
                        @endif
                    </div>
                    <div class="p-3 bg-gray-50 border-t border-gray-200">
                        <a href="{{route('elternrat.tasks.index')}}"
                           class="w-full px-4 py-2 bg-gradient-to-r from-rose-500 to-pink-500 text-white font-semibold rounded-lg hover:from-rose-600 hover:to-pink-600 transition-all flex items-center justify-center gap-2 text-sm">
                            <i class="fas fa-list-check"></i>
                            <span>Aufgaben verwalten</span>
                        </a>
                    </div>
                </div>

                <!-- Files Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4 border-b border-blue-800">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-folder-open"></i>
                            Dateien
                        </h5>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @foreach($directories as $index => $directory)
                            @php
                                $cleanId = 'dir_' . $index;
                            @endphp
                            <div class="p-4" x-data="{ open: false }">
                                <button class="w-full flex items-center justify-between text-left group hover:text-blue-600 transition-colors"
                                        type="button"
                                        @click="open = !open">
                                    <span class="font-semibold text-gray-800 group-hover:text-blue-600 flex items-center gap-2">
                                        <i class="fas fa-folder text-blue-600"></i>
                                        {{$directory}}
                                    </span>
                                    <i class="fas fa-chevron-down text-gray-400 group-hover:text-blue-600 transition-all duration-200 transform"
                                       :class="open ? 'rotate-180' : ''"></i>
                                </button>

                                <div class="mt-3 overflow-hidden transition-all duration-300"
                                     x-show="open"
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 transform translate-y-0"
                                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                                     style="display: none;">
                                    <ul class="space-y-2">
                                        @if($group->getMedia($directory)->count() > 0)
                                            @foreach($group->getMedia($directory) as $medium)
                                                <li class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors" id="file_{{$medium->id}}">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="flex-1 min-w-0">
                                                            <a href="{{url('/image/'.$medium->id)}}"
                                                               target="_blank"
                                                               class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                                                @php
                                                                    $extension = strtolower(pathinfo($medium->file_name, PATHINFO_EXTENSION));
                                                                    $iconClass = match($extension) {
                                                                        'pdf' => 'fa-file-pdf text-red-600',
                                                                        'doc', 'docx' => 'fa-file-word text-blue-700',
                                                                        'xls', 'xlsx' => 'fa-file-excel text-green-600',
                                                                        'ppt', 'pptx' => 'fa-file-powerpoint text-orange-600',
                                                                        'jpg', 'jpeg', 'png', 'gif', 'webp' => 'fa-file-image text-purple-600',
                                                                        'zip', 'rar', '7z' => 'fa-file-zipper text-yellow-600',
                                                                        default => 'fa-file text-gray-600'
                                                                    };
                                                                @endphp
                                                                <i class="fas {{$iconClass}}"></i>
                                                                <span class="truncate">{{$medium->name}}</span>
                                                                @if($medium->size)
                                                                    <span class="text-xs text-gray-400">({{number_format($medium->size / 1024, 0)}} KB)</span>
                                                                @endif
                                                            </a>
                                                            <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                                                <i class="fas fa-calendar-alt"></i>
                                                                {{$medium->updated_at->format('d.m.Y H:i')}}
                                                            </div>
                                                        </div>
                                                        @can('delete elternrat file')
                                                            <button class="fileDelete px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition-colors shadow-sm hover:shadow-md flex-shrink-0"
                                                                    data-id="{{$medium->id}}"
                                                                    title="Datei löschen">
                                                                <i class="fas fa-trash text-xs"></i>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </li>
                                            @endforeach
                                        @else
                                            <li class="text-sm text-gray-500 italic p-3 bg-gray-50 rounded-lg">
                                                Keine Dateien gefunden
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <a href="{{url('elternrat/add/file')}}"
                           class="w-full px-4 py-3 bg-gradient-to-r from-teal-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-teal-600 hover:to-teal-700 transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fa fa-plus-circle"></i>
                            <span>Datei hinzufügen</span>
                        </a>
                    </div>
                </div>

                <!-- Members Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4 border-b border-purple-800">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-user-friends"></i>
                            Zugriff auf Bereich
                        </h5>
                    </div>
                    <div class="p-4">
                        <ul class="space-y-2">
                            @foreach($users as $user)
                                <li class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                        {{substr($user->name, 0, 1)}}
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">{{$user->name}}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{asset('js/plugins/sweetalert2.all.min.js')}}"></script>

    <script>
        $(document).ready(function() {
            // File deletion handler
            $('.fileDelete').on('click', function (e) {
                e.preventDefault();
                const fileId = $(this).data('id');
                const fileElement = $('#file_' + fileId);

                Swal.fire({
                    title: 'Datei wirklich entfernen?',
                    text: "Diese Aktion kann nicht rückgängig gemacht werden!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ja, entfernen!',
                    cancelButtonText: 'Abbrechen'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{url("elternrat/file/")}}/' + fileId,
                            type: 'DELETE',
                            data: {
                                "_token": "{{csrf_token()}}",
                            },
                            success: function() {
                                fileElement.fadeOut(300, function() {
                                    $(this).remove();
                                });

                                Swal.fire({
                                    title: 'Gelöscht!',
                                    text: 'Die Datei wurde erfolgreich entfernt.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Fehler!',
                                    text: 'Die Datei konnte nicht gelöscht werden.',
                                    icon: 'error',
                                    confirmButtonColor: '#dc2626'
                                });
                            }
                        });
                    }
                });
            });

            // Comment deletion handler
            $('.deleteComment').on('click', function (e) {
                e.preventDefault();
                const commentId = $(this).data('commentid');
                const commentElement = $('#comment' + commentId);

                Swal.fire({
                    title: 'Kommentar wirklich löschen?',
                    text: "Diese Aktion kann nicht rückgängig gemacht werden!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ja, löschen!',
                    cancelButtonText: 'Abbrechen'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{url("elternrat/comment/")}}/' + commentId,
                            type: 'DELETE',
                            data: {
                                "_token": "{{csrf_token()}}",
                            },
                            success: function() {
                                commentElement.fadeOut(300, function() {
                                    $(this).remove();
                                });

                                Swal.fire({
                                    title: 'Gelöscht!',
                                    text: 'Der Kommentar wurde erfolgreich entfernt.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Fehler!',
                                    text: 'Der Kommentar konnte nicht gelöscht werden.',
                                    icon: 'error',
                                    confirmButtonColor: '#dc2626'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush

