@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-6">

        <!-- Statistik-Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <!-- Gesamt Familien -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Gesamt Familien</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats['totalFamilies'] }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Geleistete Stunden -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Geleistete Stunden</p>
                        <p class="text-3xl font-bold text-blue-600">{{ round($stats['totalHoursCompleted'], 1) }}h</p>
                        <p class="text-xs text-gray-500 mt-1">
                            von {{ round($stats['totalHoursRequired'], 1) }}h erforderlich
                        </p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-clock text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Vollständig erfüllt -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Erfüllt (100%)</p>
                        <p class="text-3xl font-bold text-green-600">{{ $stats['completed'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $stats['totalFamilies'] > 0 ? round(($stats['completed'] / $stats['totalFamilies']) * 100) : 0 }}% aller Familien
                        </p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Teilweise erfüllt -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">In Arbeit</p>
                        <p class="text-3xl font-bold text-yellow-600">{{ $stats['partial'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Ø {{ $stats['avgPercent'] }}% Erfüllung
                        </p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <i class="fas fa-hourglass-half text-2xl text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <!-- Zu zahlender Beitrag -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Zu zahlen</p>
                        <p class="text-3xl font-bold text-red-600">{{ number_format($stats['totalBeitrag'], 2, ',', '.') }} €</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ round($stats['totalHoursMissing'], 1) }}h offen
                        </p>
                    </div>
                    <div class="bg-red-100 rounded-full p-3">
                        <i class="fas fa-euro-sign text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formular: Pflichtstunden für Nutzer erfassen -->
        @can('edit Pflichtstunden')
        <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4 rounded-t-xl">
                <h3 class="text-xl font-bold flex items-center gap-3">
                    <i class="fas fa-user-clock text-2xl"></i>
                    Pflichtstunden für Nutzer erfassen
                </h3>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('pflichtstunden.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="user_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user text-blue-600 mr-2"></i>
                                Nutzer auswählen
                            </label>
                            <select name="user_id"
                                    id="user_id"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none @error('user_id') border-red-500 @enderror"
                                    required>
                                <option value="">-- Nutzer auswählen --</option>
                                @foreach($allGroupedUsers as $group)
                                    <option value="{{ $group['user']->id }}">
                                        {{ $group['user']->name }}
                                        @if($group['partner'])
                                            / {{ $group['partner']->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="admin_start" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar-day text-blue-600 mr-2"></i>
                                Startdatum und -uhrzeit
                            </label>
                            <input type="datetime-local"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none @error('start') border-red-500 @enderror"
                                   id="admin_start"
                                   name="start"
                                   value="{{ old('start') }}"
                                   required>
                            @error('start')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="admin_end" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar-check text-blue-600 mr-2"></i>
                                Enddatum und -uhrzeit
                            </label>
                            <input type="datetime-local"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none @error('end') border-red-500 @enderror"
                                   id="admin_end"
                                   name="end"
                                   value="{{ old('end') }}"
                                   required>
                            @error('end')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="admin_description" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-align-left text-blue-600 mr-2"></i>
                                Grund/Beschreibung
                            </label>
                            <textarea class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none resize-none @error('description') border-red-500 @enderror"
                                      id="admin_description"
                                      name="description"
                                      rows="3"
                                      placeholder="Beschreiben Sie den Grund für die Pflichtstunden..."
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-save"></i>
                                Pflichtstunden erfassen
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <!-- Unbestätigte Pflichtstunden -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
            <div class="bg-gradient-to-r from-orange-500 to-amber-600 text-white px-6 py-4 rounded-t-xl">
                <h3 class="text-xl font-bold flex items-center gap-3">
                    <i class="fas fa-clock text-2xl"></i>
                    Unbestätigte Pflichtstunden
                </h3>
            </div>
            <div class="p-6">
                @if($pflichtstunden->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-4xl mb-3"></i>
                        <p class="text-lg font-medium">Keine unbestätigten Pflichtstunden vorhanden</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Datum</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stunden</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Person</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Grund</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            @foreach ($pflichtstunden as $pflichtstunde)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @if($pflichtstunde->start->isSameDay($pflichtstunde->end))
                                            <div class="font-medium">{{ $pflichtstunde->start->format('d.m.Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $pflichtstunde->start->format('H:i') }} - {{ $pflichtstunde->end->format('H:i') }}</div>
                                        @else
                                            <div class="text-xs">{{ $pflichtstunde->start->format('d.m.Y H:i') }}</div>
                                            <div class="text-xs">{{ $pflichtstunde->end->format('d.m.Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            @if($pflichtstunde->duration > 60)
                                                {{ floor($pflichtstunde->duration / 60) }}h {{ $pflichtstunde->duration % 60 }}m
                                            @else
                                                {{ $pflichtstunde->duration }}m
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $pflichtstunde->user->name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ Str::limit($pflichtstunde->description, 50) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('pflichtstunden.approve', $pflichtstunde) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors duration-200"
                                                        onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich bestätigen?');">
                                                    <i class="fas fa-check"></i>
                                                    Bestätigen
                                                </button>
                                            </form>
                                            <div x-data="{ showReject: false }" class="inline-flex items-center gap-2">
                                                <button @click="showReject = !showReject"
                                                        type="button"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                                    <i class="fas fa-times"></i>
                                                    Ablehnen
                                                </button>
                                                <form x-show="showReject"
                                                      x-transition
                                                      action="{{ route('pflichtstunden.reject', $pflichtstunde) }}"
                                                      method="POST"
                                                      class="inline-flex items-center gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input name="rejection_reason"
                                                           type="text"
                                                           class="px-2 py-1 text-xs border border-gray-300 rounded-lg focus:border-red-500 focus:ring-1 focus:ring-red-200"
                                                           placeholder="Grund..."
                                                           required>
                                                    <button type="submit"
                                                            class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg"
                                                            onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich ablehnen?');">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        <!-- Übersicht der Pflichtstunden mit Suchfunktion und Pagination -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200"
             x-data="{
                 search: '',
                 currentPage: 1,
                 perPage: 20,
                 allUsers: [
                     @foreach ($groupedUsers as $group)
                     {
                         userName: '{{ addslashes($group['user']->name) }}',
                         partnerName: '{{ $group['partner'] ? addslashes($group['partner']->name) : '' }}',
                         totalMinutes: {{ $group['totalMinutes'] }},
                         openMinutes: {{ $group['openMinutes'] }},
                         beitrag: {{ $group['beitrag'] }},
                         percent: {{ $group['percent'] }}
                     },
                     @endforeach
                 ],
                 get filteredUsers() {
                     if (this.search === '') return this.allUsers;
                     return this.allUsers.filter(group => {
                         const searchLower = this.search.toLowerCase();
                         const userName = group.userName.toLowerCase();
                         const partnerName = group.partnerName ? group.partnerName.toLowerCase() : '';
                         return userName.includes(searchLower) || partnerName.includes(searchLower);
                     });
                 },
                 get paginatedUsers() {
                     const filtered = this.filteredUsers;
                     const start = (this.currentPage - 1) * this.perPage;
                     const end = start + this.perPage;
                     return filtered.slice(start, end);
                 },
                 get totalPages() {
                     return Math.ceil(this.filteredUsers.length / this.perPage);
                 },
                 nextPage() {
                     if (this.currentPage < this.totalPages) {
                         this.currentPage++;
                         this.scrollToTable();
                     }
                 },
                 prevPage() {
                     if (this.currentPage > 1) {
                         this.currentPage--;
                         this.scrollToTable();
                     }
                 },
                 goToPage(page) {
                     this.currentPage = page;
                     this.scrollToTable();
                 },
                 scrollToTable() {
                     document.querySelector('#userTable').scrollIntoView({ behavior: 'smooth', block: 'start' });
                 }
             }"
             x-init="$watch('search', () => currentPage = 1)">

            <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white px-6 py-4 rounded-t-xl flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center gap-3">
                    <i class="fas fa-chart-bar text-2xl"></i>
                    Übersicht der Pflichtstunden
                </h3>
                <div x-data="{ showExportMenu: false }" class="relative">
                    <button @click="showExportMenu = !showExportMenu"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white text-green-600 hover:bg-green-50 font-semibold rounded-lg transition-colors duration-200 shadow-md">
                        <i class="fas fa-file-excel"></i>
                        Excel-Export
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="showExportMenu"
                         @click.away="showExportMenu = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
                         style="display: none;">
                        <div class="py-2">
                            <a href="{{ route('pflichtstunden.export') }}"
                               class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-green-50 transition-colors duration-150">
                                <i class="fas fa-calendar-day text-green-600"></i>
                                <div>
                                    <div class="font-medium">Aktueller Zeitraum</div>
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::createFromFormat('m-d', $pflichtstunden_settings->pflichtstunden_start)->format('d.m.') }} - {{ \Carbon\Carbon::createFromFormat('m-d', $pflichtstunden_settings->pflichtstunden_ende)->format('d.m.Y') }}</div>
                                </div>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="{{ route('pflichtstunden.export', ['year' => date('Y') - 1]) }}"
                               class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150">
                                <i class="fas fa-calendar-alt text-blue-600"></i>
                                <div>
                                    <div class="font-medium">Vorjahr</div>
                                    <div class="text-xs text-gray-500">Zeitraum {{ date('Y') - 1 }}</div>
                                </div>
                            </a>
                            <a href="{{ route('pflichtstunden.export', ['year' => date('Y') - 2]) }}"
                               class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150">
                                <i class="fas fa-calendar-alt text-blue-600"></i>
                                <div>
                                    <div class="font-medium">{{ date('Y') - 2 }}</div>
                                    <div class="text-xs text-gray-500">Zeitraum {{ date('Y') - 2 }}</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suchfeld -->
            <div class="px-6 pt-6" id="userTable">
                <div class="flex items-center gap-4">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text"
                               x-model="search"
                               class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                               placeholder="Nutzer durchsuchen (Name)...">
                    </div>
                    <div class="text-sm text-gray-600 whitespace-nowrap">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        <span x-text="filteredUsers.length"></span> von <span x-text="allUsers.length"></span> Einträgen
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name/Familie</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Geleistet</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Offen</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Beitrag</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Erfüllung</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="group in paginatedUsers" :key="group.userName">
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900" x-text="group.userName"></div>
                                        <div x-show="group.partnerName" class="text-sm text-gray-500">
                                            <span>+ </span><span x-text="group.partnerName"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <template x-if="group.totalMinutes >= 60">
                                                <span x-text="Math.floor(group.totalMinutes / 60) + 'h ' + (group.totalMinutes % 60) + 'm'"></span>
                                            </template>
                                            <template x-if="group.totalMinutes < 60">
                                                <span x-text="group.totalMinutes + 'm'"></span>
                                            </template>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <template x-if="group.openMinutes > 0">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <template x-if="group.openMinutes >= 60">
                                                    <span x-text="Math.floor(group.openMinutes / 60) + 'h ' + (group.openMinutes % 60) + 'm'"></span>
                                                </template>
                                                <template x-if="group.openMinutes < 60">
                                                    <span x-text="group.openMinutes + 'm'"></span>
                                                </template>
                                            </span>
                                        </template>
                                        <template x-if="group.openMinutes === 0">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                0 Min.
                                            </span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold">
                                        <template x-if="group.beitrag > 0">
                                            <span class="text-red-600" x-text="group.beitrag.toFixed(2).replace('.', ',') + ' €'"></span>
                                        </template>
                                        <template x-if="group.beitrag === 0">
                                            <span class="text-green-600">0,00 €</span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 bg-gray-200 rounded-full h-6 overflow-hidden">
                                                <div class="h-full flex items-center justify-center text-xs font-semibold text-white transition-all duration-300"
                                                     :class="{
                                                         'bg-green-500': group.percent >= 100,
                                                         'bg-yellow-500': group.percent >= 50 && group.percent < 100,
                                                         'bg-red-500': group.percent < 50
                                                     }"
                                                     :style="'width: ' + Math.min(100, group.percent) + '%'">
                                                    <span x-text="group.percent + '%'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div x-show="filteredUsers.length > 0" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Zeige
                        <span class="font-medium" x-text="((currentPage - 1) * perPage) + 1"></span>
                        bis
                        <span class="font-medium" x-text="Math.min(currentPage * perPage, filteredUsers.length)"></span>
                        von
                        <span class="font-medium" x-text="filteredUsers.length"></span>
                        Einträgen
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Previous Button -->
                        <button @click="prevPage()"
                                :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-chevron-left"></i>
                            Zurück
                        </button>

                        <!-- Page Numbers -->
                        <div class="flex items-center gap-1">
                            <template x-for="page in totalPages" :key="page">
                                <button @click="goToPage(page)"
                                        x-show="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                                        :class="page === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        class="w-10 h-10 rounded-lg font-medium transition-colors duration-200"
                                        x-text="page">
                                </button>
                            </template>
                        </div>

                        <!-- Next Button -->
                        <button @click="nextPage()"
                                :disabled="currentPage === totalPages"
                                :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg transition-colors duration-200">
                            Weiter
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Keine Ergebnisse -->
                <div x-show="filteredUsers.length === 0" class="text-center py-8 text-gray-500">
                    <i class="fas fa-search text-4xl mb-3"></i>
                    <p class="text-lg font-medium">Keine Nutzer gefunden</p>
                    <p class="text-sm">Versuchen Sie eine andere Suche</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        /* Select2 Container - Base */
        .select2-container {
            z-index: 99999 !important;
            width: 100% !important;
        }

        /* Select2 Selection Box */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 2px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.5rem 1rem !important;
            background-color: #ffffff !important;
            line-height: 26px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            color: #374151 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 10px !important;
            top: 1px !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        /* Select2 Dropdown - Kritisch für Sichtbarkeit */
        .select2-dropdown {
            z-index: 99999 !important;
            border: 2px solid #3b82f6 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            background-color: #ffffff !important;
            margin-top: 4px !important;
        }

        /* Select2 Search Field */
        .select2-search--dropdown {
            padding: 8px !important;
            background-color: #ffffff !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 2px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
            outline: none !important;
            background-color: #ffffff !important;
            color: #374151 !important;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        /* Select2 Results */
        .select2-results {
            background-color: #ffffff !important;
        }

        .select2-results__options {
            max-height: 300px !important;
            overflow-y: auto !important;
        }

        .select2-results__option {
            padding: 10px 12px !important;
            background-color: #ffffff !important;
            color: #374151 !important;
        }

        .select2-results__option--highlighted {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #dbeafe !important;
            color: #1e40af !important;
        }

        .select2-results__option--highlighted[aria-selected=true] {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }

        /* No Results Message */
        .select2-results__option--load-more,
        .select2-results__option--searching,
        .select2-results__option--no-results {
            padding: 10px 12px !important;
            background-color: #ffffff !important;
            color: #6b7280 !important;
        }

        /* Sicherstellen dass Dropdown immer sichtbar ist */
        .select2-container--open {
            z-index: 99999 !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #3b82f6 transparent !important;
        }

        /* Clear Button */
        .select2-container--default .select2-selection--single .select2-selection__clear {
            color: #ef4444 !important;
            font-size: 18px !important;
            line-height: 26px !important;
            margin-right: 10px !important;
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Select2 initialisieren
            $('#user_id').select2({
                placeholder: '🔍 Nutzer suchen und auswählen...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('body'),
                theme: 'default',
                language: {
                    noResults: function() {
                        return "❌ Keine Nutzer gefunden";
                    },
                    searching: function() {
                        return "🔍 Suche...";
                    },
                    inputTooShort: function() {
                        return "Bitte mehr Zeichen eingeben";
                    }
                },
                // Dropdown-Position korrigieren
                dropdownAutoWidth: false,
                // Sicherstellen dass Dropdown über allem erscheint
                containerCssClass: 'select2-container--custom',
                dropdownCssClass: 'select2-dropdown--custom'
            });

            // Beim Öffnen Z-Index setzen
            $('#user_id').on('select2:open', function (e) {
                $('.select2-dropdown--custom').css({
                    'z-index': '99999',
                    'background-color': '#ffffff'
                });
            });

            // Focus-State verbessern
            $('#user_id').on('select2:opening', function (e) {
                $(this).data('select2').$dropdown.css({
                    'z-index': '99999',
                    'background-color': '#ffffff'
                });
            });
        });
    </script>
@endpush

