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
        @if($user->reinigung()->whereDate('datum', '>', Carbon\Carbon::yesterday())->count() > 0)
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6>
                                Eigene Reinigungstermine
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                @foreach($user->reinigung()->whereDate('datum', '>', Carbon\Carbon::yesterday())->get() as $reinigung)
                                    <li class="list-group-item">
                                        Woche: {{$reinigung->datum->startOfWeek()->format('d.m.')}}
                                        - {{$reinigung->datum->endOfWeek()->format('d.m.Y')}}
                                    </li>
                                @endforeach
                            </ul>
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
                            @can('edit reinigung')
                                <div class="pull-right small">
                                    <a href="{{url('reinigung/'.$Bereich.'/export')}}" class="btn btn-sm">
                                        Export
                                    </a>
                                </div>
                            @endcan
                            <h5 class="card-title">
                                Reinigungsplan {{$Bereich}}

                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table w-100 table-responsive-sm">
                                <thead>
                                <tr class="d-none d-sm-block">
                                    <th>Woche</th>
                                    <th></th>
                                </tr>
                                <tr class="d-block d-sm-none">
                                    <th class="w-100">Woche</th>
                                </tr>
                                </thead>
                                <tbody>
                                @for($Woche = $datum->copy(); $Woche->lessThanOrEqualTo($ende); $Woche->addWeek())
                                    <tr class="d-none d-sm-block">
                                        <th>
                                            <div class="card bg-light ">
                                                <div class="card-header">
                                                    {{$Woche->copy()->startOfWeek()->format('d.m.')}} - {{$Woche->copy()->endOfWeek()->format('d.m.Y')}}
                                                    </div>
                                                    @can('edit reinigung')
                                                        <div class="card-body">
                                                            <a href="{{url('reinigung/create/'.$Bereich.'/'.$Woche->startOfWeek()->format('Ymd'))}}" class="btn btn-sm">
                                                                <i class="fas fa-plus-circle"></i>
                                                            </a>
                                                        </div>
                                                    @endcan
                                                </div>
                                            </th>
                                        <td>
                                            <div class="row">
                                                @foreach($Familien[$Bereich]->filter(function ($value) use ($Woche){
                                                    if ($Woche->startOfWeek()->eq($value->datum->startOfWeek())) {return $value;}
                                                }) as $reinigung)
                                                    <div class="col">
                                                        <div
                                                            class="card @if($reinigung->user->id == auth()->id() or auth()->user()->sorg2 == auth()->id()) bg-warning @else bg-light @endif">
                                                            <div class="card-header">
                                                                <h6>
                                                                    Familie {{$reinigung->user->familie_name}}
                                                                    @can('edit reinigung')
                                                                        <div class="pull-right">
                                                                            <a class="link text-danger"
                                                                               href="{{url('reinigung/'.$Bereich.'/'.$reinigung->id.'/trash')}}">
                                                                                <i class="fa fa-trash"></i>
                                                                            </a>
                                                                        </div>
                                                                    @endcan
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                {{$reinigung->aufgabe}}
                                                            </div>
                                                            <div class="card-footer">
                                                                {{$reinigung->bemerkung}}
                                                            </div>
                                                        </div>
                                                    </div>

                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="d-block d-sm-none">
                                        <th class="w-100">
                                            <div class="card bg-light ">
                                                <div class="card-header">
                                                    {{$Woche->copy()->startOfWeek()->format('d.m.')}}
                                                    - {{$Woche->copy()->endOfWeek()->format('d.m.Y')}}
                                                </div>
                                                @can('edit reinigung')
                                                    <div class="card-body">
                                                        <a href="{{url('reinigung/create/'.$Bereich.'/'.$Woche->startOfWeek()->format('Ymd'))}}"
                                                           class="btn btn-sm">
                                                            <i class="fas fa-plus-circle"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                            </div>
                                        </th>
                                    </tr>
                                    <tr class="d-block d-sm-none">
                                        <td class="w-100">
                                            <div class="row">
                                                @foreach($Familien[$Bereich]->filter(function ($value) use ($Woche){
                                                    if ($Woche->startOfWeek()->eq($value->datum->startOfWeek())) {return $value;}
                                                }) as $reinigung)
                                                    <div class="col">
                                                        <div
                                                            class="card @if($reinigung->user->id == auth()->id() or auth()->user()->sorg2 == auth()->id()) bg-warning @else bg-light @endif">
                                                            <div class="card-header">
                                                                <h6>
                                                                    Familie {{$reinigung->user->familie_name}}
                                                                    @can('edit reinigung')
                                                                        <div class="pull-right">
                                                                            <a class="link text-danger"
                                                                               href="{{url('reinigung/'.$Bereich.'/'.$reinigung->id.'/trash')}}">
                                                                                <i class="fa fa-trash"></i>
                                                                            </a>
                                                                        </div>
                                                                    @endcan
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                {{$reinigung->aufgabe}}
                                                            </div>
                                                            <div class="card-footer">
                                                                {{$reinigung->bemerkung}}
                                                            </div>
                                                        </div>
                                                    </div>

                                                @endforeach
                                            </div>
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
