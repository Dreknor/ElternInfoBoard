@extends('layouts.app')
@section('title') - Gruppen @endsection

@section('content')
    <div class="container-fluid px-4 py-3 space-y-4">
        @foreach($groups as $group)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Card Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800">
                    <div class="flex items-center justify-between">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            @if(!$group->protected)
                                <i class="fas fa-unlock-alt"></i>
                            @else
                                <i class="fas fa-lock"></i>
                            @endif
                            {{$group->name}}
                        </h5>
                        @canany(['edit groups', 'delete groups'])
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" @click.away="open = false"
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-white hover:bg-white/20 transition-all duration-200">
                                    <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                </button>
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute left-0 md:left-auto md:right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
                                     style="display: none;">
                                    @if(auth()->user()->can('create own group') and $group->owner_id == auth()->user()->id)
                                        <a href="{{url('groups/'.$group->id.'/add')}}"
                                           class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                            <i class="fas fa-user-plus text-blue-600"></i>
                                            <span>Hinzufügen</span>
                                        </a>
                                    @endcan
                                    @can('edit groups')
                                        @php
                                            $messengerActive = \App\Model\Module::firstWhere('setting', 'Eltern-Nachrichten')?->options['active'] ?? false;
                                        @endphp
                                        @if($messengerActive && !$group->owner_id)
                                            <form action="{{ route('groups.toggle-chat', $group) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                        class="flex items-center gap-3 px-4 py-2 text-sm w-full text-left {{ $group->has_chat ? 'text-orange-700 hover:bg-orange-50' : 'text-green-700 hover:bg-green-50' }} transition-colors">
                                                    <i class="fas {{ $group->has_chat ? 'fa-comment-slash text-orange-500' : 'fa-comments text-green-600' }}"></i>
                                                    <span>{{ $group->has_chat ? 'Gruppen-Chat deaktivieren' : 'Gruppen-Chat aktivieren' }}</span>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                    @if( auth()->user()->can('delete groups') or $group->owner_id == auth()->user()->id)
                                        <div class="border-t border-gray-200 mt-2 pt-2 px-4">
                                            <p class="text-xs text-red-700 font-medium mb-2">
                                                Soll diese Gruppe gelöscht werden? Dies muss per Passwort bestätigt werden.
                                            </p>
                                            <form method="post"
                                                  action="{{url('groups/'.$group->id.'/delete')}}"
                                                  class="space-y-2">
                                                @csrf
                                                @method('delete')
                                                <input name="passwort" type="password"
                                                       placeholder="Passwort eingeben"
                                                       class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all duration-200 outline-none text-sm">
                                                <button type="submit"
                                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                                                    <i class="fas fa-trash"></i>
                                                    Gruppe endgültig löschen
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endcan
                    </div>
                    @canany(['edit groups', 'create own group'])
                        <div class="mt-2 pt-2 border-t border-blue-500">
                            <p class="text-sm text-blue-50 mb-0">
                                <i class="fas fa-users mr-1"></i>
                                Es gibt {{$group->users->count()}} Benutzer
                                @if($group->has_chat)
                                    &nbsp;·&nbsp;<i class="fas fa-comments mr-1"></i>Chat aktiv
                                @endif
                            </p>
                        </div>
                    @endcan
                </div>
                <!-- Card Body -->
                <div class="p-4">
                    @can('edit groups')
                        <div class="overflow-x-auto">
                            <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">E-Mail</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Telefon</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($group->users as $user)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3">
                                                @can('edit user')
                                                    <a href="{{url('users/'.$user->id)}}"
                                                       class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium">
                                                        <i class="fas fa-user-edit"></i>
                                                        {{$user->name}}
                                                    </a>
                                                @else
                                                    {{$user->name}}
                                                @endcan
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($user->publicMail !="")
                                                    <a href="mailto:{{$user->publicMail}}"
                                                       class="text-blue-600 hover:text-blue-700 hover:underline">
                                                        {{$user->publicMail}}
                                                    </a>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($user->publicPhone !="")
                                                    <a href="tel:{{$user->publicPhone}}"
                                                       class="text-blue-600 hover:text-blue-700 hover:underline">
                                                        {{$user->publicPhone}}
                                                    </a>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                @if(auth()->user()->can('create own group') and $group->owner_id == auth()->user()->id)
                                                    <form action="{{url('groups/'.$group->id.'/removeUser')}}"
                                                          method="post">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{$user->id}}">
                                                        <button type="submit"
                                                                class="inline-flex items-center justify-center p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($group->users->filter(function ($user){
                                if ($user->publicMail !="" or $user->publicPhone !=""){ return $user; }
                            }) as $user)
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-blue-500 hover:shadow-md transition-all duration-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        @can('edit user')
                                            <a href="{{url('user/'.$user->id)}}"
                                               class="inline-flex items-center justify-center p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200">
                                                <i class="fas fa-user-edit"></i>
                                            </a>
                                        @endcan
                                        <span class="font-semibold text-gray-800">{{$user->name}}</span>
                                    </div>
                                    @if($user->publicMail !="")
                                        <div class="flex items-center gap-2 text-sm text-gray-600 mb-1">
                                            <i class="fas fa-envelope text-blue-600"></i>
                                            <a href="mailto:{{$user->publicMail}}"
                                               class="text-blue-600 hover:text-blue-700 hover:underline break-all">
                                                {{$user->publicMail}}
                                            </a>
                                        </div>
                                    @endif
                                    @if($user->publicPhone !="")
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <i class="fas fa-phone text-green-600"></i>
                                            <a href="tel:{{$user->publicPhone}}"
                                               class="text-blue-600 hover:text-blue-700 hover:underline">
                                                {{$user->publicPhone}}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endcan
                </div>
            </div>
        @endforeach
        @if(auth()->user()->can('edit groups') or auth()->user()->can('create own group'))
            <!-- Eigene Gruppe anlegen -->
            @can('create own group')
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    @if ($errors->any())
                        <div class="p-4">
                            <div class="flex items-start gap-3 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                                <i class="fas fa-times-circle text-red-600 mt-1"></i>
                                <div class="flex-1">
                                    <ul class="list-disc list-inside text-red-700 text-sm mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3 border-b border-green-800">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-plus-circle"></i>
                            Eigene Gruppe anlegen
                        </h5>
                    </div>
                    <div class="p-4">
                        <p class="text-gray-600 text-sm mb-4">
                            Eigene Gruppen werden vom Ersteller verwaltet (Personen hinzufügen etc.). Die Gruppe ist
                            nicht öffentlich und kann nur vom Ersteller gesehen und genutzt werden.
                            Persönliche Gruppen werden grundsätzlich
                            zum {{config('app.own_groups_delete', \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', '2020-07-01 00:00:00')->format('d.m.Y'))}} gelöscht.
                        </p>
                        <form action="{{url('groups/own')}}" method="post" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                <input type="text"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 outline-none"
                                       placeholder="Name der Gruppe"
                                       name="name"
                                       value="{{old('name')}}"
                                       required>
                            </div>
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-save"></i>
                                Speichern
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
            <!-- Globale Gruppe anlegen -->
            @can('edit groups')
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-4 py-3 border-b border-purple-800">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-globe"></i>
                            Globale Gruppe anlegen
                        </h5>
                    </div>
                    <div class="p-4">
                        <p class="text-gray-600 text-sm mb-4">
                            Die hier angelegten Gruppen stehen allen Benutzern zur Verfügung.
                        </p>
                        <form action="{{url('groups')}}" method="post" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                    <input type="text"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-200 outline-none"
                                           placeholder="Name der Gruppe"
                                           name="name"
                                           value="{{old('name')}}"
                                           required>
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bereich</label>
                                    <input type="text"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-200 outline-none"
                                           name="bereich"
                                           placeholder="Bereich der Gruppe"
                                           value="{{old('bereich')}}">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Geschützt</label>
                                    <label class="relative inline-flex items-center cursor-pointer mt-2">
                                        <input type="checkbox" name="protected" value="1"
                                               class="sr-only peer" {{ old('protected') ? 'checked="checked"' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                    </label>
                                </div>
                            </div>
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-save"></i>
                                Speichern
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
        @endif
    </div>
@endsection

