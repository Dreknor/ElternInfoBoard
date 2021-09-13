@extends('layouts.app')
@section('title') - Reinigung @endsection

@section('content')
    <div class="container-fluid">
        @if(count($Bereiche)<1)
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card btn-outline-info">
                        <div class="card-body">
                            <p>
                                Die Reinigungsliste wird mit Beginn des Schuljahres angezeigt
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @foreach($Bereiche as $Bereich)
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                Reinigungsplan {{$Bereich}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped w-100 table-responsive-sm">
                                <thead>
                                    <tr>
                                        <th>Woche</th>
                                        <th>Familie</th>
                                        <th>Reinigungsarbeit</th>
                                        <th>Familie</th>
                                        <th>Reinigungsarbeit</th>
                                        <th>Bemerkung</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($Woche = $datum->copy(); $Woche->lessThanOrEqualTo($ende); $Woche->addWeek())
                                        @php
                                            $familien=$Familien[$Bereich]->filter(function ($familie) use ($Woche) {
                                                    return $familie->datum->equalTo($Woche->copy()->startOfWeek());
                                                });

                                            $familie1 = $familien->first();


                                            $familie2 = $familien->last();

                                            if ($familie1 == $familie2) {
                                            $familie2=null;
                                            }
                                        @endphp
                                        <tr @if((isset($familie1) and $familie1->users_id == auth()->user()->id) or (isset($familie2) and $familie2->users_id == auth()->user()->id)) class="table-info" @endif>
                                            <td class="text-monospace">
                                                {{$Woche->startOfWeek()->format('d.m.')}} - {{$Woche->endOfWeek()->format('d.m.Y')}}
                                            </td>
                                            <td class="familie editable" @if(isset($familie1)) data-id="{{$familie1->id}}" @else data-datum="{{$Woche->format('Ymd')}}" data-bereich="{{$Bereich}}" @endif>
                                                @if(isset($familie1))
                                                    Familie {{$familie1->user->familie_name}}
                                                @endif
                                            </td>
                                            <td >
                                                @if(isset($familie1))
                                                    {{$familie1->aufgabe}}
                                                @endif
                                            </td>
                                            <td class="familie">
                                                @if(isset($familie2))
                                                    Familie {{$familie2->user->familie_name}}
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($familie2))
                                                    {{$familie2->aufgabe}}
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($familie1) and $familie1->bemerkung != "")
                                                    {{$familie1->bemerkung}}
                                                @elseif(isset($familie2) and $familie2->bemerkung != "")
                                                    {{$familie2->bemerkung}}
                                                @endif
                                            </td>
                                            <td>
                                                @if($user->can('edit reinigung'))
                                                    <a href="{{url("reinigung/create/$Bereich/".$Woche->startOfWeek()->format('Ymd'))}}" class="btn btn-warning">
                                                        <i class="far fa-edit"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @if($user->can('edit reinigung'))
        <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            Neue Aufgabe erstellen
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{url('reinigung/task/')}}" method="post" class="form-horizontal">
                            @csrf
                            <div class="form-row">
                                <label for="task">
                                    Aufgabe
                                </label>
                                <input class="form-control" name="task" id="task" required>
                            </div>
                            <div class="form-row">
                                <button type="submit" class="btn btn-success btn-block">
                                    neue Aufgabe speichern
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
        </div>
    @endif

@endsection

@section('css')
    <link href="//rawgithub.com/indrimuska/jquery-editable-select/master/dist/jquery-editable-select.min.css" rel="stylesheet">

@endsection
@push('js')
@endpush
