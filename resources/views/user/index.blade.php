@extends('layouts.app')
@section('title') - Benutzer @endsection

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title">
                            Benutzerkonten
                        </h5>
                    </div>
                    <div class="col">
                        <p class=" pull-right">
                            <a href="{{url('users/create')}}" class="btn btn-primary">
                                <i class="fa fa-dd-user"></i>
                                Benutzer anlegen
                            </a>
                        </p>

                    </div>
                    @can('import user')
                        <div class="col">
                            <p class=" pull-right">
                                <a href="{{url('users/import')}}" class="btn btn-outline-info">
                                    <i class="far fa-address-book"></i>
                                    Benutzer importieren
                                </a>
                            </p>

                        </div>
                        <div class="col">
                            <p class=" pull-right">
                                <a href="{{url('users/importVerein')}}" class="btn btn-outline-warning">
                                    <i class="far fa-address-book"></i>
                                    Vereinsmitglieder importieren
                                </a>
                            </p>

                        </div>
                    @endcan
                    @can('edit user')
                        <div class="col">
                            <p class=" pull-right">
                                <a href="{{url('users/mass/delete')}}" class="btn btn-warning">
                                    <i class="far fa-trash"></i>
                                    mehrere Benutzer löschen
                                </a>
                            </p>
                        </div>
                        <div class="col">
                            <p class=" pull-right">
                                <a href="{{url('users/vereinsmitglieder/non-members')}}" class="btn btn-outline-success">
                                    <i class="fas fa-users"></i>
                                    Nicht-Vereinsmitglieder
                                </a>
                            </p>
                        </div>
                    @endcan
                </div>
            </div>
            <div class="card-body">

                {{-- Such- und Filterformular --}}
                <form method="get" action="{{ url('users') }}" class="mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small font-weight-bold">Suche (Name / E-Mail)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Name oder E-Mail…"
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small font-weight-bold">Rolle</label>
                            <select name="role" class="custom-select">
                                <option value="">– Alle Rollen –</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}"
                                        @selected(request('role') === $role->name)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small font-weight-bold">Gruppe</label>
                            <select name="group" class="custom-select">
                                <option value="">– Alle Gruppen –</option>
                                @foreach($groups as $gruppe)
                                    <option value="{{ $gruppe->id }}"
                                        @selected(request('group') == $gruppe->id)>
                                        {{ $gruppe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Filtern
                            </button>
                            @if(request()->hasAny(['search','role','group']))
                                <a href="{{ url('users') }}" class="btn btn-outline-secondary btn-block">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                {{-- Ergebnisanzeige --}}
                <p class="text-muted small mb-2">
                    {{ $users->total() }} Benutzer gefunden
                    @if(request()->hasAny(['search','role','group']))
                        <span class="badge badge-info ml-1">gefiltert</span>
                    @endif
                </p>

                <table class="table table-hover" id="userTable">
                    <thead>
                    <tr>
                        <td></td>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Gruppen</th>
                        <th>Rechte</th>
                        <th>Verknüpft</th>
                        <th>E-Mail</th>
                        <td></td>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr @if($user->is_active === false) class="table-warning" title="Konto deaktiviert" @endif>
                                <td>
                                    <a href="{{url('/users/').'/'.$user->id}}" class="btn-link">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                                <td>
                                    {{$user->name}}
                                    @if($user->is_active === false)
                                        <span class="badge badge-danger ml-1" title="Konto deaktiviert">
                                            <i class="fas fa-ban"></i> Inaktiv
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{$user->email}}
                                </td>
                                <td class="small">
                                    @foreach($user->groups as $gruppe)
                                        <div class="btn btn-outline-info btn-sm">
                                            {{$gruppe->name}}
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <div class="btn btn-outline-warning btn-sm">
                                            {{$role->name}}
                                        </div>
                                    @endforeach

                                        @foreach($user->permissions as $permission)
                                            <div class="btn btn-outline-danger btn-sm">
                                                {{$permission->name}}
                                            </div>
                                        @endforeach
                                </td>

                                <td>
                                    @if(!is_null($user->sorgeberechtigter2))
                                        {{$user->sorgeberechtigter2->name}}
                                    @endif
                                </td>
                                <td>
                                    <a class="btn  @if(is_null($user->lastEmail) or $user->lastEmail->lessThan(\Carbon\Carbon::parse('last friday'))) btn-danger @else btn-success @endif  btn-sm"
                                       href="{{url('email/daily/'.$user->id)}}">
                                        letzte Mail: {{$user->lastEmail?->format('d.m.Y')}} - Email senden?
                                    </a>
                                </td>
                                <td>
                                    <div class="row">
                                        <div class="col-auto">
                                            <form action="{{url('users').'/'.$user->id}}" method="post"
                                                  class="form-inline">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-sm btn-danger user_ajax-delete"
                                                        data-id="{{$user->id}}">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <div class="col-auto mt-2">
                                            @can('loginAsUser')
                                                <form method="POST" action="{{ url('showUser/'.$user->id) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-info" title="Als dieser User anmelden">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                        <div class="col-auto mt-2">
                                            @can('testing')
                                                <a href="{{url("push/$user->id")}}" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-info-circle"></i>
                                                </a>
                                            @endcan
                                        </div>
                                    </div>


                                </td>


                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
 @can('edit user')
     <script src="{{asset('js/plugins/sweetalert2.all.min.js')}}"></script>

     <script>
         $('.user-delete').on('click', function () {
             var userID = $(this).data('id');
             var button = $(this);

             swal.fire({
                 title: "Benutzer wirklich entfernen?",
                 type: "warning",
                 showCancelButton: true,
                 cancelButtonText: "Benutzer behalten",
                 confirmButtonText: "Benutzer entfernen!",
                 confirmButtonColor: "danger"
             }).then((confirmed) => {
                 if (confirmed.value) {
                     $.ajax({
                         url: '{{url("/users/")}}'+'/'+ userID,
                         type: 'DELETE',
                         data: {
                             "_token": "{{csrf_token()}}",
                         },
                         success: function(result) {
                             $(button).parents('tr').fadeOut();
                         }
                     });
                 }
             });
         });
     </script>
 @endcan
@endpush


