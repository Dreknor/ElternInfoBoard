@extends('layouts.app')
@section('title') - Nicht-Vereinsmitglieder @endsection

@section('content')
    <div class="container-fluid px-4 py-6"
         x-data="{
             selectedRole: '',
             users: {{ Js::from($usersData) }},
             loadingUserId: null,

             get filteredUsers() {
                 return this.users
                     .filter(user =>
                         !user.removed && (this.selectedRole === '' || user.roles.includes(this.selectedRole))
                     )
                     .sort((a, b) => a.name.localeCompare(b.name, 'de', { sensitivity: 'base' }));
             },

             get userCount() {
                 return this.filteredUsers.length;
             },

             async addToVerein(userId, userName) {
                 if (!confirm(`Soll ${userName} zur Gruppe Vereinsmitglied hinzugefügt werden?`)) {
                     return;
                 }

                 this.loadingUserId = userId;

                 try {
                     const response = await fetch('{{ url('users/vereinsmitglieder/add') }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}'
                         },
                         body: JSON.stringify({ user_id: userId })
                     });

                     const data = await response.json();

                     if (data.success) {
                         this.showAlert('success', data.message);

                         // Finde den hinzugefügten User
                         const addedUser = this.users.find(u => u.id === userId);

                         // Entferne den User und seinen Sorg2 aus der Liste
                         setTimeout(() => {
                             this.users = this.users.map(u => {
                                 // Markiere den User selbst als removed
                                 if (u.id === userId) {
                                     return { ...u, removed: true };
                                 }
                                 // Markiere auch den Sorg2 als removed, wenn dieser User der Sorg2 des hinzugefügten Users ist
                                 if (addedUser && addedUser.sorg2 && u.name === addedUser.sorg2) {
                                     return { ...u, removed: true };
                                 }
                                 return u;
                             });
                             this.loadingUserId = null;
                         }, 800);
                     } else {
                         this.showAlert('danger', data.message);
                         this.loadingUserId = null;
                     }
                 } catch (error) {
                     this.showAlert('danger', 'Ein Fehler ist aufgetreten');
                     this.loadingUserId = null;
                 }
             },

             showAlert(type, message) {
                 const alertColors = {
                     success: 'bg-green-50 border-green-200 text-green-800',
                     danger: 'bg-red-50 border-red-200 text-red-800',
                     info: 'bg-blue-50 border-blue-200 text-blue-800'
                 };

                 const iconMap = {
                     success: 'check-circle',
                     danger: 'exclamation-circle',
                     info: 'info-circle'
                 };

                 const alertDiv = document.createElement('div');
                 alertDiv.className = `\${alertColors[type]} border rounded-lg px-4 py-3 mb-4 flex items-center justify-between shadow-md`;
                 alertDiv.innerHTML = `
                     <span class='flex items-center gap-2'>
                         <i class='fas fa-\${iconMap[type]}'></i>
                         \${message}
                     </span>
                     <button onclick='this.parentElement.remove()' class='text-gray-500 hover:text-gray-700'>
                         <i class='fas fa-times'></i>
                     </button>
                 `;

                 this.$refs.alertContainer.prepend(alertDiv);

                 setTimeout(() => {
                     alertDiv.style.transition = 'opacity 0.4s';
                     alertDiv.style.opacity = '0';
                     setTimeout(() => alertDiv.remove(), 400);
                 }, 5000);
             }
         }">

        <!-- Alert Container -->
        <div x-ref="alertContainer"></div>

        <!-- Header -->
        <div class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                        <i class="fas fa-users text-blue-600"></i>
                        Benutzer ohne Vereinsmitgliedschaft
                    </h1>
                    <p class="text-gray-600">
                        Benutzer, die weder selbst noch über Sorg2 in der Gruppe "Vereinsmitglied" sind
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{url('users')}}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-arrow-left"></i>
                        Zurück
                    </a>
                    <form action="{{url('users/vereinsmitglieder/sync-role')}}" method="post" class="inline">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Sollen alle bestehenden Vereinsmitglieder auf die Rolle überprüft werden?')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                            <i class="fas fa-sync"></i>
                            Rollen synchronisieren
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter & Stats Card -->
        <div class="bg-white rounded-lg shadow-lg border border-gray-200 mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 rounded-t-lg">
                <h2 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-filter"></i>
                    Filter & Statistik
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Rollen-Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user-tag text-blue-600"></i>
                            Nach Rolle filtern
                        </label>
                        <select x-model="selectedRole"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                            <option value="">Alle anzeigen</option>
                            @foreach($roles as $role)
                                <option value="{{$role->name}}">{{$role->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Benutzer-Anzahl -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-chart-bar text-blue-600"></i>
                            Anzahl
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-3 rounded-lg shadow-md">
                                <div class="text-3xl font-bold" x-text="userCount"></div>
                            </div>
                            <span class="text-gray-600 font-medium">Benutzer</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Table Card -->
        <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                <h2 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-table"></i>
                    Benutzerliste
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">E-Mail</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Gruppen</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Rollen</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Verknüpft</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Aktion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(user, index) in filteredUsers" :key="user.id">
                            <tr class="hover:bg-gray-50 transition-colors duration-150"
                                x-show="!user.removed"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a :href="`{{url('/users')}}/${user.id}`"
                                       class="text-blue-600 hover:text-blue-800 font-medium hover:underline"
                                       x-text="user.name">
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600" x-text="user.email"></td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="group in user.groups">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                  x-text="group">
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="role in user.roles">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800"
                                                  x-text="role">
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                    <span x-text="user.sorg2 || '-'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="addToVerein(user.id, user.name)"
                                            :disabled="loadingUserId === user.id"
                                            :class="loadingUserId === user.id ? 'opacity-75 cursor-not-allowed' : ''"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md disabled:hover:bg-green-600">
                                        <i class="fas"
                                           :class="loadingUserId === user.id ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                                        <span x-text="loadingUserId === user.id ? 'Wird hinzugefügt...' : 'Hinzufügen'"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="filteredUsers.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="fas fa-users text-gray-300 text-5xl"></i>
                                    <p class="text-gray-500 font-medium">Keine Benutzer gefunden</p>
                                    <p class="text-sm text-gray-400">Alle Benutzer sind bereits Vereinsmitglieder oder es wurden keine passenden Benutzer für den gewählten Filter gefunden.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info Footer -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg px-6 py-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-600 text-xl mt-1"></i>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1">Hinweise:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700">
                        <li>Benutzer werden nur angezeigt, wenn weder sie selbst noch ihr verknüpfter Sorg2 in der Gruppe "Vereinsmitglied" sind</li>
                        <li>Nach dem Hinzufügen wird der Benutzer automatisch aus der Liste entfernt</li>
                        <li>Die Synchronisation prüft alle bestehenden Gruppenmitglieder und weist fehlende Rollen zu</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

