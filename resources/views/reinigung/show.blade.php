@extends('layouts.app')

@section('content')
    <div class="container-fluid">
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
                                            <td scope="col">
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
                                                Bitte 1x Wäsche mitnehmen
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

@endsection

@section('css')
    <link href="//rawgithub.com/indrimuska/jquery-editable-select/master/dist/jquery-editable-select.min.css" rel="stylesheet">

@endsection
@push('js')
@endpush