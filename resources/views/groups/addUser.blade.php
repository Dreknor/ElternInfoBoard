@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <!-- Zurück Button -->
        <div class="mb-4">
            <a href="{{url('groups')}}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left"></i>
                Zurück
            </a>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800">
                <h4 class="text-xl font-bold text-white mb-0">
                    Benutzer zu Gruppe {{$group->name}} hinzufügen
                </h4>
            </div>

            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full" id="userTable">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">E-Mail</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Gruppen</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Verknüpft</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-gray-800">
                                        {{$user->name}}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{$user->email}}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->groups as $gruppe)
                                                <span class="inline-flex items-center px-2.5 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                                    {{$gruppe->name}}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 text-sm">
                                        @if(!is_null($user->sorgeberechtigter2))
                                            {{$user->sorgeberechtigter2->name}}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <form action="{{url('groups/'.$group->id.'/addUser')}}"
                                              method="post">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{$user->id}}">
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center p-2 text-green-600 hover:bg-green-50 rounded-lg transition-all duration-200">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#userTable').DataTable({
                dom: 'Bfrtip',
            });
        });
    </script>
@endpush

@section('css')
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet"/>
@endsection
