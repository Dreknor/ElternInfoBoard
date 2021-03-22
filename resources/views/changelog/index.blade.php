@extends('layouts.app')

@section('content')
    <div class="container-fluid">

            <div class="card">
                <div class="card-header">
                    <h5>
                        Letzte Änderungen am {{config('app.name')}}
                    </h5>
                </div>
                @can('add changelog')
                    <div class="card-body">
                        <a href="{{url('changelog/create')}}" class="btn btn-primary btn-block">
                            neues Changelog erstellen
                        </a>
                    </div>
                @endcan
                @foreach($changelogs as $changelog)
                    <div class="card-body border-top">
                        <h6>
                           {{$changelog->header}}
                        </h6>
                        <p>
                            <small>
                                {{$changelog->updated_at->format('d.m.Y H:i')}}
                            </small>
                        </p>
                        <p>
                            {!! $changelog->text !!}
                        </p>
                    </div>
                @endforeach

                <div class="card-footer">
                    {{$changelogs->links()}}
                </div>
            </div>
        </div>
    </div>
@endsection
