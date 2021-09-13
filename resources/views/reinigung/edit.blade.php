@extends('layouts.app')

@section('content')
    <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                Reinigungsplan {{$Bereich}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped w-100">
                                <thead>
                                    <tr>
                                        <th>Woche</th>
                                        <th>Familie</th>
                                        <th>Reinigungsarbeit</th>
                                        <th>Familie</th>
                                        <th>Reinigungsarbeit</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <form action="{{url("reinigung/$Bereich")}}" method="post" id="editform">
                                        @csrf
                                    @for($Woche = $datum->copy(); $Woche->lessThanOrEqualTo($ende); $Woche->addWeek())

                                        @php
                                            $familien=$Familien->filter(function ($familie) use ($Woche) {
                                                    return $familie->datum->equalTo($Woche->copy()->startOfWeek());
                                                });

                                            $familie1 = $familien->first();


                                            $familie2 = $familien->last();

                                            if ($familie1 == $familie2) {
                                                $familie2=null;
                                            }

                                        @endphp
                                        <tr>
                                            <td class="text-monospace">
                                                {{$Woche->startOfWeek()->format('d.m.')}} - {{$Woche->endOfWeek()->format('d.m.Y')}}
                                                <input type="hidden" name="datum" value="{{$Woche->startOfWeek()->format('d.m.Y')}}">
                                            </td>
                                            <td class="familie" >
                                                <select name="usersID_first" class="w-100" autocomplete="new-password">
                                                    <option></option>

                                                @foreach($users as $user)
                                                        <option value="{{$user->id}}" @if(isset($familie1) and $familie1->users_id == $user->id) selected @endif>{{$user->name}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="aufgabe_first" class="w-100">
                                                    <option></option>

                                                @foreach($aufgaben as $aufgabe)
                                                        <option value="{{$aufgabe->task}}" @if(isset($familie1) and $familie1->aufgabe == $aufgabe->task) selected @endif>{{$aufgabe->task}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="familie">
                                                <select name="usersID_last" class="w-100">
                                                    <option></option>

                                                @foreach($users as $user)
                                                        <option value="{{$user->id}}" @if(isset($familie2) and $familie2->users_id == $user->id) selected @endif>{{$user->name}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="aufgabe_last"  class="w-100" autocomplete="new-password">
                                                    <option></option>
                                                    @foreach($aufgaben as $aufgabe)
                                                        <option value="{{$aufgabe->task}}" @if(isset($familie2) and $familie2->aufgabe == $aufgabe->task) selected @endif>{{$aufgabe->task}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endfor
                                    </form>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <button type="submit" form="editform" class="btn btn-success btn-block">
                                speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
    </div>

@endsection

@section('css')
    <link href="//rawgithub.com/indrimuska/jquery-editable-select/master/dist/jquery-editable-select.min.css" rel="stylesheet">

@endsection
@push('js')
    <script src="//rawgithub.com/indrimuska/jquery-editable-select/master/dist/jquery-editable-select.min.js"></script>
    <script>
        $(document).ready(function () {
           //$('.select').editableSelect();
        });
    </script>
@endpush
