@extends('layouts.app')

@section('title', '- ' . __('stundenplan.title'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 py-6 space-y-6">
    <!-- Hauptbereich -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header mit Gradient -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-0">
                        <i class="fas fa-calendar-alt"></i>
                        {{ __('stundenplan.title') }}
                    </h2>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-white text-sm">
                        <i class="fas fa-clock mr-2"></i>
                        {{ __('stundenplan.updated_at') }}: {{ $data['Basisdaten']['Zeitstempel'] }}
                    </div>
                    @can('edit settings')
                        <a href="{{ url('stundenplan/import') }}" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors duration-200 flex items-center gap-2 text-sm font-medium">
                            <i class="fas fa-upload"></i>
                            Import
                        </a>
                    @endcan
                </div>
            </div>
            <p class="text-blue-100 mt-2 text-sm">
                <i class="fas fa-calendar-check mr-2"></i>
                {{ __('stundenplan.valid_from') }}: {{ $data['Basisdaten']['DatumVon'] }} - {{ $data['Basisdaten']['DatumBis'] }}
            </p>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-gray-50 border-b border-gray-200">
            <div class="px-6" x-data="{ activeTab: '{{ $currentView }}' }">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button @click="activeTab = 'class'"
                            :class="activeTab === 'class' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        {{ __('stundenplan.view_by_class') }}
                    </button>
                    <button @click="activeTab = 'teacher'"
                            :class="activeTab === 'teacher' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-chalkboard-teacher"></i>
                        {{ __('stundenplan.view_by_teacher') }}
                    </button>
                    <button @click="activeTab = 'room'"
                            :class="activeTab === 'room' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center gap-2">
                        <i class="fas fa-door-open"></i>
                        {{ __('stundenplan.view_by_room') }}
                    </button>
                </nav>

                <!-- Content Area -->
                <div class="py-6">
                    <!-- Class View -->
                    <div x-show="activeTab === 'class'" x-cloak>
                        <div class="mb-6">
                            <label for="class-select" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-filter mr-2"></i>
                                {{ __('stundenplan.select_class') }}
                            </label>
                            <select id="class-select"
                                    class="block w-full md:w-64 px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                    onchange="loadClassTimetable(this.value)">
                                <option value="">{{ __('stundenplan.select_class') }}</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class }}">{{ $class }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="class-timetable-container">
                            <div class="bg-gray-50 rounded-lg p-12 text-center">
                                <i class="fas fa-calendar-alt text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg">{{ __('stundenplan.select_class') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher View -->
                    <div x-show="activeTab === 'teacher'" x-cloak>
                        <div class="mb-6">
                            <label for="teacher-select" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-filter mr-2"></i>
                                {{ __('stundenplan.select_teacher') }}
                            </label>
                            <select id="teacher-select"
                                    class="block w-full md:w-64 px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                <option value="">{{ __('stundenplan.select_teacher') }}</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher }}">{{ $teacher }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-12 text-center border-2 border-blue-200">
                            <i class="fas fa-chalkboard-teacher text-6xl text-blue-400 mb-4"></i>
                            <p class="text-blue-700 text-lg font-medium">{{ __('stundenplan.teacher_view_development') }}</p>
                            <p class="text-blue-600 text-sm mt-2">{{ __('stundenplan.feature_coming_soon') }}</p>
                        </div>
                    </div>

                    <!-- Room View -->
                    <div x-show="activeTab === 'room'" x-cloak>
                        <div class="mb-6">
                            <label for="room-select" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-filter mr-2"></i>
                                {{ __('stundenplan.select_room') }}
                            </label>
                            <select id="room-select"
                                    class="block w-full md:w-64 px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                <option value="">{{ __('stundenplan.select_room') }}</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room }}">{{ $room }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bg-green-50 rounded-lg p-12 text-center border-2 border-green-200">
                            <i class="fas fa-door-open text-6xl text-green-400 mb-4"></i>
                            <p class="text-green-700 text-lg font-medium">{{ __('stundenplan.room_view_development') }}</p>
                            <p class="text-green-600 text-sm mt-2">{{ __('stundenplan.feature_coming_soon') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadClassTimetable(className) {
    if (!className) {
        document.getElementById('class-timetable-container').innerHTML = `
            <div class="bg-gray-50 rounded-lg p-12 text-center">
                <i class="fas fa-calendar-alt text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('stundenplan.select_class') }}</p>
            </div>
        `;
        return;
    }

    // Show loading state
    document.getElementById('class-timetable-container').innerHTML = `
        <div class="bg-blue-50 rounded-lg p-12 text-center border-2 border-blue-200">
            <i class="fas fa-spinner fa-spin text-6xl text-blue-500 mb-4"></i>
            <p class="text-blue-700 text-lg font-medium">{{ __('stundenplan.loading') }}</p>
        </div>
    `;

    // Fetch timetable for selected class
    window.location.href = `/stundenplan/klassen/${className}`;
}
</script>
@endsection

