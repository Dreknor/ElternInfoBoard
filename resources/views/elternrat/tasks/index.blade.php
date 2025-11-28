@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Page Header -->
        <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-tasks text-rose-600"></i>
                    Aufgaben
                </h1>
                <p class="text-sm text-gray-600 mt-1">Aufgabenverwaltung für den Elternrat</p>
            </div>
            <a href="{{url('elternrat')}}"
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>Zurück</span>
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-circle text-blue-600 text-2xl"></i>
                    <span class="text-3xl font-bold text-blue-900">{{$openTasks}}</span>
                </div>
                <div class="text-sm text-blue-800 font-semibold">Offen</div>
            </div>
            <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg p-4 border border-amber-200">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-spinner text-amber-600 text-2xl"></i>
                    <span class="text-3xl font-bold text-amber-900">{{$inProgressTasks}}</span>
                </div>
                <div class="text-sm text-amber-800 font-semibold">In Arbeit</div>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    <span class="text-3xl font-bold text-green-900">{{$completedTasks}}</span>
                </div>
                <div class="text-sm text-green-800 font-semibold">Erledigt</div>
            </div>
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    <span class="text-3xl font-bold text-red-900">{{$overdueTasks}}</span>
                </div>
                <div class="text-sm text-red-800 font-semibold">Überfällig</div>
            </div>
        </div>

        <!-- Create Task Form -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle text-rose-600"></i>
                Neue Aufgabe erstellen
            </h3>
            <form action="{{route('elternrat.tasks.store')}}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                    <div class="lg:col-span-4">
                        <input type="text"
                               name="title"
                               placeholder="Aufgabentitel..."
                               required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    </div>
                    <div class="lg:col-span-2">
                        <select name="priority" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 bg-white">
                            <option value="low">🟢 Niedrig</option>
                            <option value="medium" selected>🟡 Mittel</option>
                            <option value="high">🔴 Hoch</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <input type="date"
                               name="due_date"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500">
                    </div>
                    <div class="lg:col-span-3">
                        <select name="assigned_to" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 bg-white">
                            <option value="">Nicht zugewiesen</option>
                            @foreach($users as $user)
                                <option value="{{$user->id}}">{{$user->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <button type="submit"
                                class="w-full h-full px-6 py-2.5 bg-gradient-to-r from-rose-600 to-pink-600 text-white rounded-lg hover:from-rose-700 hover:to-pink-700 font-semibold shadow-md transition-all">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <textarea name="description"
                              rows="2"
                              placeholder="Beschreibung (optional)..."
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 resize-none"></textarea>
                </div>
            </form>
        </div>

        <!-- Tasks List -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden" x-data="{ filter: 'all' }">
            <!-- Filter Tabs -->
            <div class="border-b border-gray-200 bg-gray-50">
                <nav class="flex flex-wrap">
                    <button @click="filter = 'all'"
                            :class="filter === 'all' ? 'border-rose-500 text-rose-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition-colors">
                        Alle
                    </button>
                    <button @click="filter = 'open'"
                            :class="filter === 'open' ? 'border-rose-500 text-rose-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition-colors">
                        Offen
                    </button>
                    <button @click="filter = 'in_progress'"
                            :class="filter === 'in_progress' ? 'border-rose-500 text-rose-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition-colors">
                        In Arbeit
                    </button>
                    <button @click="filter = 'completed'"
                            :class="filter === 'completed' ? 'border-rose-500 text-rose-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition-colors">
                        Erledigt
                    </button>
                </nav>
            </div>

            <div class="divide-y divide-gray-200">
                @forelse($tasks as $task)
                    <div class="p-4 hover:bg-gray-50 transition-colors @if($task->status === 'completed') bg-gray-50 opacity-60 @endif"
                         x-show="filter === 'all' || filter === '{{$task->status}}'">
                        <div class="flex items-start gap-4">
                            <!-- Status Dropdown -->
                            <div class="flex-shrink-0" x-data="{ open: false }">
                                <div class="relative">
                                    <button @click="open = !open" type="button" class="text-3xl hover:scale-110 transition-transform focus:outline-none">
                                        @if($task->status === 'completed')
                                            <i class="fas fa-check-circle text-green-500"></i>
                                        @elseif($task->status === 'in_progress')
                                            <i class="fas fa-spinner text-blue-500"></i>
                                        @else
                                            <i class="far fa-circle text-gray-400"></i>
                                        @endif
                                    </button>

                                    <!-- Dropdown Menu -->
                                    <div x-show="open"
                                         @click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10 overflow-hidden"
                                         style="display: none;">

                                        <!-- Offen -->
                                        <form action="{{route('elternrat.tasks.status', $task)}}" method="POST" class="m-0">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="open">
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 {{$task->status === 'open' ? 'bg-gray-50 font-semibold' : ''}}">
                                                <i class="far fa-circle text-gray-400"></i>
                                                <span>Offen</span>
                                            </button>
                                        </form>

                                        <!-- In Arbeit -->
                                        <form action="{{route('elternrat.tasks.status', $task)}}" method="POST" class="m-0">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="in_progress">
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 {{$task->status === 'in_progress' ? 'bg-blue-50 font-semibold' : ''}}">
                                                <i class="fas fa-spinner text-blue-500"></i>
                                                <span>In Arbeit</span>
                                            </button>
                                        </form>

                                        <!-- Erledigt -->
                                        <form action="{{route('elternrat.tasks.status', $task)}}" method="POST" class="m-0">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2 rounded-b-lg {{$task->status === 'completed' ? 'bg-green-50 font-semibold' : ''}}">
                                                <i class="fas fa-check-circle text-green-500"></i>
                                                <span>Erledigt</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Task Content -->
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-800 {{$task->status === 'completed' ? 'line-through' : ''}}">
                                    {{$task->title}}
                                </h4>
                                @if($task->description)
                                    <p class="text-sm text-gray-600 mt-1">{{$task->description}}</p>
                                @endif
                                <div class="flex flex-wrap gap-3 text-xs text-gray-600 mt-2">
                                    @if($task->due_date)
                                        <span class="flex items-center gap-1 {{$task->isOverdue() ? 'text-red-600 font-bold' : ''}}">
                                            <i class="fas fa-calendar"></i>
                                            {{$task->due_date->format('d.m.Y')}}
                                            @if($task->isOverdue())
                                                <span class="ml-1 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold">ÜBERFÄLLIG</span>
                                            @elseif($task->isDueSoon())
                                                <span class="ml-1 px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-semibold">BALD FÄLLIG</span>
                                            @endif
                                        </span>
                                    @endif
                                    @if($task->assignedUser)
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-user"></i>
                                            {{$task->assignedUser->name}}
                                        </span>
                                    @endif
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-user-edit"></i>
                                        Erstellt von {{$task->creator->name}}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <!-- Priority Badge -->
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($task->priority === 'high') bg-red-100 text-red-800
                                    @elseif($task->priority === 'medium') bg-amber-100 text-amber-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    @if($task->priority === 'high') 🔴
                                    @elseif($task->priority === 'medium') 🟡
                                    @else 🟢
                                    @endif
                                    {{ucfirst($task->priority)}}
                                </span>

                                <!-- Status Badge -->
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($task->status === 'completed') bg-green-100 text-green-800
                                    @elseif($task->status === 'in_progress') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    @if($task->status === 'completed') Erledigt
                                    @elseif($task->status === 'in_progress') In Arbeit
                                    @else Offen
                                    @endif
                                </span>

                                <!-- Delete Button -->
                                @if(auth()->user()->can('delete elternrat file') || $task->created_by === auth()->id())
                                    <form action="{{route('elternrat.tasks.destroy', $task)}}" method="POST"
                                          onsubmit="return confirm('Aufgabe wirklich löschen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 p-2 hover:bg-red-50 rounded transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-gray-500">
                        <i class="fas fa-clipboard-check text-6xl mb-4 text-gray-300"></i>
                        <p class="text-lg font-semibold mb-2">Keine Aufgaben vorhanden</p>
                        <p class="text-sm">Erstellen Sie eine neue Aufgabe, um loszulegen</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($tasks->hasPages())
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    {{$tasks->links()}}
                </div>
            @endif
        </div>
    </div>
@endsection

