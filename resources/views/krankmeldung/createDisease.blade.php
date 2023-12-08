@extends('layouts.app')

@section('title')
    - meldepfl. Krankheit erstellen
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                neue meldepflichtige Krankheit erstellen:
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url('diseases/create')}}" method="post" class="form form-horizontal">
                @csrf
                <div class="form-row">
                    <label for="disease">
                        Name der Krankheit:
                    </label>
                    <select class="custom-select" name="disease_id" , id="disease">
                        @foreach($diseases as $disease)
                            <option value="{{$disease->id}}">{{$disease->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <button type="submit" class="btn btn-primary">Krankheit erstellen</button>
                </div>
            </form>
        </div>
    </div>

@endsection
