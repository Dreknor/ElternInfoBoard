@extends('layouts.app')
@section('title') - Krankmeldung @endsection

@section('content')


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
                                    <input type="text" class="form-control" name="name" id="name" autofocus required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start">
                                        Krank ab*:
                                    </label>
                                    <input type="date" class="form-control" name="start" id="start" min="{{\Carbon\Carbon::now()->subDays(3)->format('Y-m-d')}}" value="{{\Carbon\Carbon::now()->format('Y-m-d')}}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ende">
                                        Krank bis*:
                                    </label>
                                    <input type="date" class="form-control" name="ende" id="ende" value="{{\Carbon\Carbon::now()->format('Y-m-d')}}" required>
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
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>Kind</th>
                                <th>Datum</th>
                                <th></th>
                                <th class="d-sm-none">Erstellt</th>
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
                                        {!! $krankmeldung->kommentar !!}
                                    </td>
                                    <td class="d-sm-none">
                                        <small>
                                            {{$krankmeldung->created_at->format('d.m.Y h:i ')}} Uhr <br>
                                            von {{$krankmeldung->user->name}}
                                        </small>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="4">
                                {{ $krankmeldungen->links() }}
                            </td>
                        </tr>
                        </tfoot>
                    </table>
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
