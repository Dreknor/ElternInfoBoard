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

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Berechtigung
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Rollen
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($Rechte as $Recht)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-key text-purple-600 text-sm"></i>
                                                <span class="text-sm font-medium text-gray-900">{{$Recht->name}}</span>
                                            </div>
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

            <!-- New Permission Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 border-b border-purple-800">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-key"></i>
                        Neues Recht anlegen
                    </h5>
                </div>
                <div class="p-6">
                    <form action="{{url('roles/permission')}}" method="post" class="space-y-4">
                        @csrf
                        <div>
                            <label for="permission-name" class="block text-sm font-medium text-gray-700 mb-2">
                                Name der Berechtigung
                            </label>
                            <input type="text"
                                   id="permission-name"
                                   name="name"
                                   placeholder="z.B. benutzer.bearbeiten"
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        </div>
                        <button type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg hover:from-purple-700 hover:to-purple-800 transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-plus-circle"></i>
                            <span>Berechtigung anlegen</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>




@endsection

@push('js')
    <script>
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
