@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <a href="{{ url()->previous()}}" class="btn btn-primary">zurück</a>
        <div class="card">
            <div class="card-header">
                <h6>
                    Schickzeit für {{$day}} für {{$child->first_name}} {{$child->last_name}}
                </h6>
            </div>
            <div class="body">
                @include('schickzeiten.infos')
            </div>
            <div class="card-body">
                <div class="container-fluid">
                    <form method="post" action="{{url('schickzeiten/'.$child->id.'/'.$day)}}" class="form-horizontal">
                        @csrf
                        <div class="form-row mt-2" id="abZeit">
                            <label for="type">Typ</label>
                            <select name="type" class="custom-select" id="type">
                                <option value="genau" @if($schickzeit?->type == 'genau') selected @endif>genau</option>
                                <option value="ab" @if($schickzeit?->type != 'genau') selected @endif >ab ... bis ... Uhr</option>
                            </select>
                        </div>
                        <div class="form-row mt-2 @if($schickzeit?->type != 'genau') collapse @endif" id="genauZeit">
                            <label for="time">Zeit</label>
                            <input name="time" id="time" type="time" class="form-control" min="{{$vorgaben->schicken_ab}}" max="{{$vorgaben->schicken_bis}}"  value="{{$schickzeit?->time?->format('H:i')}}">
                        </div>
                        <div class="form-row  mt-2 collapse @if($schickzeit?->type != 'genau') show @endif" id="spaet_row">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="ab">ab ... Uhr</label>
                                        <input name="time_ab" type="time" class="form-control" min="{{$vorgaben->schicken_ab}}" max="{{$vorgaben->schicken_bis}}" id="spät."
                                               value="{{$schickzeit?->time_ab?->format('H:i')}}">
                                    </div>
                                    <div class="col-md-6 ">
                                        <label for="spät.">spätestens (optional)</label>
                                        <input name="time_spaet" type="time" class="form-control" min="{{$vorgaben->schicken_ab}}" max="{{$vorgaben->schicken_bis}}" id="spät."
                                               value="{{$schickzeit?->time_spaet?->format('H:i')}}">
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="form-row mt-3">
                            <button type="submit" class="btn btn-success btn-block">Speichern</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            $("#type").change(function() {
                $('#spaet_row').toggle();
                $('#genauZeit').toggle();
            });
        });
    </script>
@endpush
