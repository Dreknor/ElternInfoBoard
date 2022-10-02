@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <a href="{{ url()->previous()}}" class="btn btn-primary">zurück</a>
        <div class="card">
            @error('time_spaet')
            {{old('time_spaet')}}
            @enderror
            <div class="card-header">
                <h6>
                    Schickzeit für {{$day}} für {{$child}}
                </h6>
            </div>
            <div class="body">
                @include('schickzeiten.infos')
                <p class="container-fluid">
                    Änderungen werden berücksichtigt ab: <b>{{\Carbon\Carbon::now()->next('monday')->format('d.m.Y')}}</b>
                </p>
            </div>
            <div class="card-body">
                <div class="container-fluid">
                    <form method="post" action="{{url('verwaltung/schickzeiten/'.$parent)}}" class="form-horizontal">
                        @csrf
                        <div class="form-row">
                            <label for="child">Name des Kindes</label>
                            <input name="child" value="{{$child}}" readonly class="form-control" id="child">
                        </div>
                        <div class="form-row mt-2">
                            <label for="weekday">Wochentag</label>
                            <input name="weekday" value="{{$day}}" readonly class="form-control" id="weekday">
                        </div>
                        <div class="form-row mt-2">
                            <label for="type">Typ</label>
                            <select name="type" class="custom-select" id="type">
                                <option value="genau">genau</option>
                                <option value="ab" @if($schickzeit and $schickzeit->type == "ab") selected @endif>ab ... Uhr</option>
                            </select>
                        </div>
                        <div class="form-row mt-2">
                            <label for="time">Zeit</label>
                            <input name="time" id="time" type="time" class="form-control" min="{{config('schicken.ab')}}" max="{{config('schicken.max')}}" required value="{{$schickzeit?->time?->format('H:i')}}">
                        </div>
                        <div class="form-row mt-2 collapse @if(($schickzeit_spaet and $schickzeit_spaet !="") or ($schickzeit and $schickzeit->type == "ab")) show @endif>" id="spaet_row">
                            <label for="spaet">spätestens (optional)</label>
                            <input name="time_spaet" type="time" class="form-control" min="14:00:00" max="16:30:00"  id="spaet"  value="{{$schickzeit_spaet?->time?->format('H:i')}}">
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
            });

        });

    </script>

@endpush
