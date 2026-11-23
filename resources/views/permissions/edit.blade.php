@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-shield-alt text-purple-600"></i>
                Rollen- und Rechteverwaltung
            </h1>
            <p class="text-sm text-gray-600 mt-1">Verwalten Sie Benutzerrollen und deren Berechtigungen</p>
        </div>

        <!-- Main Permissions Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 border-b border-purple-800">
                <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-user-shield"></i>
                    Rollen und Rechte
                </h5>
            </div>

            <!-- Card Body -->
            <div class="p-6">
                <form class="space-y-4" method="post" action="{{url('roles')}}">
                    @csrf
                    @method('put')

                    <div class="space-y-6">
                        @foreach($permissionsByModule as $moduleName => $modulePermissions)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                                    <h3 class="text-sm font-semibold text-gray-800">{{ $moduleName ?: 'Allgemein' }}</h3>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                    Berechtigung
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                    Beschreibung
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                    Rollen
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($modulePermissions as $Recht)
                                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center gap-2">
                                                            <i class="fas fa-key text-purple-600 text-sm"></i>
                                                            <span class="text-sm font-medium text-gray-900">{{$Recht->name}}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-600">
                                                        {{ $Recht->description ?: 'Keine Beschreibung hinterlegt.' }}
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                                                            @foreach($Rollen as $Rolle)
                                                                <label class="flex items-center gap-2 cursor-pointer group">
                                                                    <input type="checkbox"
                                                                           name="{{$Rolle->name}}[]"
                                                                           value="{{$Recht->name}}"
                                                                           @if($Rolle->hasPermissionTo($Recht->name)) checked @endif
                                                                           class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2 transition-all">
                                                                    <span class="text-sm text-gray-700 group-hover:text-purple-600 transition-colors">{{$Rolle->name}}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Save Button (Hidden by default) -->
                    <div class="pt-4 border-t border-gray-200">
                        <button type="submit"
                                id="btn-save"
                                class="hidden w-full md:w-auto px-6 py-3 bg-gradient-to-r from-teal-500 to-teal-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-teal-600 hover:to-teal-700 transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            <span>Änderungen speichern</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- New Role and Permission Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- New Role Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-user-plus"></i>
                        Neue Rolle anlegen
                    </h5>
                </div>
                <div class="p-6">
                    <form action="{{url('roles')}}" method="post" class="space-y-4">
                        @csrf
                        <div>
                            <label for="role-name" class="block text-sm font-medium text-gray-700 mb-2">
                                Rollenname
                            </label>
                            <input type="text"
                                   id="role-name"
                                   name="name"
                                   placeholder="z.B. Moderator"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                        <button type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-plus-circle"></i>
                            <span>Rolle anlegen</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Manage Roles Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden"
                 x-data="rollenVerwaltung(@js($rollenVerwaltung))">
                <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4 border-b border-gray-900">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-users-cog"></i>
                        Rollen verwalten
                    </h5>
                </div>
                <div class="p-6">
                    <ul class="divide-y divide-gray-200">
                        <template x-for="rolle in rollen" :key="rolle.id">
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <span class="text-sm font-medium text-gray-900" x-text="rolle.name"></span>
                                    <span class="ml-2 text-xs text-gray-500"
                                          x-text="rolle.users_count === 1 ? '1 Benutzer' : rolle.users_count + ' Benutzer'"></span>
                                </div>
                                <template x-if="rolle.locked">
                                    <span class="text-gray-400 text-sm flex items-center gap-1"
                                          title="Systemrolle – wird im Programm verwendet und kann nicht gelöscht werden">
                                        <i class="fas fa-lock"></i>
                                        <span class="hidden sm:inline">Systemrolle</span>
                                    </span>
                                </template>
                                <template x-if="!rolle.locked">
                                    <button type="button"
                                            @click="open(rolle)"
                                            class="px-3 py-1.5 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors flex items-center gap-1">
                                        <i class="fas fa-trash-alt"></i>
                                        <span>Löschen</span>
                                    </button>
                                </template>
                            </li>
                        </template>
                    </ul>
                </div>

                <!-- Bestätigungsdialog -->
                <div x-show="aktiv" x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                     @keydown.escape.window="close()">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
                         @click.outside="close()">
                        <template x-if="aktiv">
                            <form method="post" :action="'{{ url('roles') }}/' + aktiv.id">
                                @csrf
                                @method('delete')
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h5 class="text-lg font-bold text-gray-800 mb-0">
                                        Rolle „<span x-text="aktiv.name"></span>“ löschen?
                                    </h5>
                                </div>
                                <div class="px-6 py-4 space-y-4 text-sm text-gray-700">
                                    <p>Die Rolle wird endgültig gelöscht. Das lässt sich nicht rückgängig machen.</p>

                                    <template x-if="aktiv.users_count > 0">
                                        <div class="space-y-3">
                                            <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-red-800">
                                                <p class="font-semibold mb-1">
                                                    Diese Rolle ist <span x-text="aktiv.users_count"></span>
                                                    <span x-text="aktiv.users_count === 1 ? 'Benutzer' : 'Benutzern'"></span> zugewiesen.
                                                </p>
                                                <p x-show="aktiv.users_without_other_role > 0" class="mb-0">
                                                    <span x-text="aktiv.users_without_other_role"></span>
                                                    davon <span x-text="aktiv.users_without_other_role === 1 ? 'hat' : 'haben'"></span>
                                                    danach keine Rolle mehr.
                                                </p>
                                            </div>

                                            <details class="border border-gray-200 rounded-lg">
                                                <summary class="px-3 py-2 cursor-pointer font-medium">Betroffene Benutzer anzeigen</summary>
                                                <ul class="px-3 pb-2 max-h-48 overflow-y-auto list-disc list-inside">
                                                    <template x-for="name in aktiv.users"><li x-text="name"></li></template>
                                                </ul>
                                            </details>

                                            <div>
                                                <p class="font-medium mb-1">Diese Benutzer verlieren die Rechte der Rolle:</p>
                                                <p x-show="aktiv.permissions.length === 0" class="text-gray-500 mb-0">Die Rolle hat keine Rechte.</p>
                                                <div class="flex flex-wrap gap-1" x-show="aktiv.permissions.length > 0">
                                                    <template x-for="recht in aktiv.permissions">
                                                        <span class="px-2 py-0.5 bg-gray-100 rounded text-xs" x-text="recht"></span>
                                                    </template>
                                                </div>
                                            </div>

                                            <div>
                                                <label for="confirm-role-name" class="block font-medium mb-1">
                                                    Zur Bestätigung den Rollennamen „<span x-text="aktiv.name"></span>“ eingeben:
                                                </label>
                                                <input type="text" id="confirm-role-name" name="confirm_name"
                                                       x-model="bestaetigung" autocomplete="off"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
                                    <button type="button" @click="close()"
                                            class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Abbrechen
                                    </button>
                                    <button type="submit"
                                            :disabled="aktiv.users_count > 0 && bestaetigung.trim() !== aktiv.name"
                                            class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                        <i class="fas fa-trash-alt"></i>
                                        <span>Endgültig löschen</span>
                                    </button>
                                </div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        function rollenVerwaltung(rollen) {
            return {
                rollen: rollen,
                aktiv: null,
                bestaetigung: '',
                open(rolle) {
                    this.aktiv = rolle;
                    this.bestaetigung = '';
                },
                close() {
                    this.aktiv = null;
                },
            };
        }

        $(document).ready(function () {
            // Cache jQuery selectors
            const $checkboxes = $('input[type="checkbox"]');
            const $btnSave = $("#btn-save");

            // Store initial state
            const initialState = [];
            $checkboxes.each(function(index) {
                initialState[index] = $(this).is(':checked');
            });

            // Add change event listener
            $checkboxes.on('change', function () {
                checkChanged();
            });

            function checkChanged() {
                let hasChanges = false;

                $checkboxes.each(function(index) {
                    if ($(this).is(':checked') !== initialState[index]) {
                        hasChanges = true;
                        return false; // break the loop
                    }
                });

                if (hasChanges) {
                    $btnSave.removeClass('hidden').addClass('inline-flex');
                } else {
                    $btnSave.addClass('hidden').removeClass('inline-flex');
                }
            }
        });
    </script>

@endpush
