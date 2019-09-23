@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">
                    {{$user->name}}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Einstellungen
                                </h5>
                            </div>

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
                                <form action="{{url('/einstellungen/')}}" method="post" class="form form-horizontal">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" class="form-control border-input" placeholder="Name" name="name" value="{{$user->name}}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>E-Mail</label>
                                                <input type="text" class="form-control border-input" placeholder="E-Mail" name="email" value="{{$user->email}}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label>Benachrichtigung per E-Mail (letzte E-Mail: {{optional($user->lastEmail)->format('d.m.Y H:i')}})</label>
                                                <select class="custom-select" name="benachrichtigung">
                                                    <option value="daily" @if($user->benachrichtigung == 'daily') selected @endif>Täglich (bei neuen Nachrichten)</option>
                                                    <option value="weekly" @if($user->benachrichtigung == 'weekly') selected @endif>Wöchentlich (Freitags)</option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success btn-block collapse" id="btn-save">speichern</button>
                                        </div>
                                    </div>

                                </form>
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
                                @foreach($user->groups as $gruppe)
                                    <div class="btn btn-outline-info">
                                        {{$gruppe->name}}
                                    </div>
                                @endforeach
                            </div>
                            <div class="card-footer">
                                <p class="footer-default small">
                                    Sollte die Lerngruppe und/oder Alterststufe ihres Kindes nicht korrekt in den Gruppen abgebildet sein, wenden Sie sich bitte an <a href="mailto:info@esz-radebeul.de" class="card-link">info@esz-radebeul.de</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('js')

    <script>
        $(document).ready(function () {


            $("input").keyup(function() {
                checkChanged();
            });
            $("select").change(function() {
                checkChanged();
            });

            function checkChanged() {

                if (!$('input').val()) {
                    $("#btn-save").hide();
                } else {
                    $("#btn-save").show();
            }
            }
        });

    </script>

@endpush