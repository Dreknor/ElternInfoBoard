@extends('layouts.app')

@section('content')
    <form action="{{url('/users/')}}" method="post" class="form form-horizontal" autocomplete="off">
        @csrf
        <div class="container-fluid">
            <div class="card">
                <div class="card-header border-bottom">
                    <h5 class="card-title">
                        neuen Benutzer anlegen
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" class="form-control border-input" placeholder="Name" name="name" required autocomplete="off" value="{{old('name')}}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>E-Mail</label>
                                                <input type="email" class="form-control border-input" placeholder="E-Mail" name="email" required autocomplete="off"  value="{{old('email')}}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Passwort</label>
                                                <input type="password" name="password"  class="form-control border-input" required  autocomplete="new-password" >
                                            </div>

                                        </div>
                                    </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>Passwort wiederholen</label>
                                                    <input type="password" name="password_confirmation"  class="form-control border-input" required>
                                                </div>

                                            </div>
                                        </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success btn-block" id="btn-save">speichern</button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                        <div class="col-5 offset-1">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        Gruppen
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <input type="checkbox" name="gruppen[]" value="all" id="checkboxAll"/>
                                    <label for="checkboxAll" id="labelCheckAll"><b>Alle Gruppen zuweisen</b></label>
                                    @foreach($gruppen as $gruppe)
                                        <div>
                                            <input type="checkbox" id="{{$gruppe->name}}" name="gruppen[]" value="{{$gruppe->id}}">
                                            <label for="{{$gruppe->name}}">{{$gruppe->name}}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="card-footer">

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>

@endsection

@push('js')



@endpush