@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <a href="{{url('groups')}}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> zurück
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h4>Benutzer zu Gruppe {{$group->name}} hinzufügen</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover" id="userTable">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Gruppen</th>
                                <th>Verknüpft</th>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($users as $user)
                                <tr>
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
                                        @if(!is_null($user->sorgeberechtigter2))
                                            {{$user->sorgeberechtigter2->name}}
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{url('groups/'.$group->id.'/addUser')}}" method="post"
                                              class="form-inline">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{$user->id}}">
                                            <button type="submit" class="btn btn-sm btn-outline-success">
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
