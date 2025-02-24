@extends('layouts.app')
@section('title')
    - Krankmeldung
@endsection

@section('content')
    @can('download krankmeldungen')
        <div class="row">
            <div class="col-12">
                <a href="{{url('krankmeldung/download')}}" class="btn btn-block btn-primary">aktuelle Krankmeldung
                    herunterladen</a>
            </div>
        </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                neue Krankmeldung erstellen:
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url("/krankmeldung")}}" method="post" class="form form-horizontal">
                @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="name">
                                        Name des Schülers / der Schülerin*:
                                    </label>
                                    @if(auth()->user()->children()->count() > 0)
                                        <select name="child_id" id="child" class="form-control" >
                                            @foreach(auth()->user()->children() as $child)
                                                <option value="{{$child->id}}">{{$child->first_name}}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="form-control" name="name" id="name"
                                               @if($krankmeldungen->count() > 0) value="{{$krankmeldungen->first()->name}}"
                                               @else autofocus @endif>
                                    @endif

                                </div>
                            </div>
                        </div>
                        <div class="row">
                            @if($diseases)
                                <div class="col-md-6 col-sm-12">
                                    <label for="start">
                                        besondere Erkrankung:
                                    </label>
                                    <select name="disease_id" id="disease" class="form-control">
                                        <option value="0">keine genannte</option>
                                        @foreach($diseases as $disease)
                                            <option value="{{$disease->id}}">{{$disease->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start">
                                        Krank ab*:
                                    </label>
                                    <input type="date" class="form-control" name="start" id="start"
                                           min="{{\Carbon\Carbon::now()->subDays(3)->format('Y-m-d')}}"
                                           value="{{\Carbon\Carbon::now()->format('Y-m-d')}}" required
                                           @if($krankmeldungen->count() > 0) autofocus @endif>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="ende">
                                        Krank bis*:
                                    </label>
                                    <input type="date" class="form-control" name="ende" id="ende"
                                           value="{{\Carbon\Carbon::now()->format('Y-m-d')}}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                            <textarea class="form-control border-input" name="kommentar">
                                {{old('text')}}
                            </textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block">
                                    Krankmeldung senden
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            @if($krankmeldungen)
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">
                            bisherige Krankmeldungen:
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>Kind</th>
                                <th>Datum</th>
                                <th>Erstellt</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($krankmeldungen as $krankmeldung)
                                <tr>
                                    <td>
                                        {{$krankmeldung->name}}
                                    </td>
                                    <td>
                                        {{$krankmeldung->start->format('d.m.Y')}}
                                        - {{$krankmeldung->ende->format('d.m.Y')}}
                                    </td>
                                    <td>
                                        <p class="d-none d-md-block">
                                            {!! $krankmeldung->kommentar !!}
                                        </p>
                                        <p>
                                            <small>
                                                {{$krankmeldung->created_at->format('d.m.Y H:i ')}} Uhr <br>
                                                von {{$krankmeldung->user->name}}
                                            </small>
                                        </p>

                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $krankmeldungen->links() }}
                    </div>
                </div>
            @endif


@endsection

@push('js')

    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',
            height: 250,
            menubar: false,
        });</script>



    <script>


    </script>


@endpush
