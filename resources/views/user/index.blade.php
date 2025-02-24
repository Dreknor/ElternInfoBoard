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
                            <th>Verknüpft</th>
                            <th>E-Mail</th>
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
                                                <a href="{{url("showUser/$user->id")}}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
            </div>
        </div>
    </div>

@endsection

@push('js')
 <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
 <script src="https://cdn.datatables.net/buttons/1.6.2/js/dataTables.buttons.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
 <script src="https://cdn.datatables.net/buttons/1.6.2/js/buttons.html5.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
 <script src="https://cdn.datatables.net/buttons/1.6.2/js/buttons.print.min.js"></script>


 <script>
     $(document).ready( function () {
         $('#userTable').DataTable( {
             dom: 'Bfrtip',
             buttons: [
                  'csv', 'pdf', 'print'
             ]
         } );
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
