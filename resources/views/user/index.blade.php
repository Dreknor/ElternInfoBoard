@extends('layouts.app')

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
                                <a href="{{url('users/import')}}" class="btn btn-secondary">
                                    <i class="far fa-address-book"></i>
                                    Benutzer importieren
                                </a>
                            </p>

                        </div>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <table class="table table-hover" id="userTable">
                    <thead>
                        <tr>
                            <td></td>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Gruppen</th>
                            <th>Rechte</th>
                            <th>zuletzt online</th>
                            <th>Verknüpft</th>
                            <td></td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    <a href="{{url('/users/').'/'.$user->id}}" class="btn-link">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                                <td>
                                    {{$user->name}}
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
                                    @foreach($user->permissions as $permission)
                                        {{$permission->name}}
                                        @if(!$loop->last)
                                        ,
                                        @endif
                                    @endforeach
                                </td>
                                <td>
                                    {{optional($user->last_online_at)->format('d.m.Y H:i')}}
                                </td>
                                <td>
                                    @if(!is_null($user->sorgeberechtigter2))
                                       {{$user->sorgeberechtigter2->name}}
                                    @endif
                                </td>
                                <td>
                                    <div class="btn btn-sm btn-danger user-delete" data-id="{{$user->id}}">
                                        <i class="fas fa-user-slash"></i>
                                    </div>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('js')
 <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
 <script>
     $(document).ready( function () {
         $('#userTable').DataTable();
     } );
 </script>


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

@section('css')
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />

@endsection
