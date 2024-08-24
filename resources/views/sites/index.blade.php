@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-auto">
                        <h3>Seitenübersicht</h3>
                    </div>
                    @can('create sites')
                        <div class="col">
                            <div class="pull-right">
                                <a href="#createSite" class="btn btn-primary">Neue Seite erstellen</a>
                            </div>
                        </div>
                    @endcan
                </div>


            </div>
            @if(count($sites) < 1)
                <div class="card-body">
                    <p>Keine Seiten vorhanden</p>
                </div>
            @else
                <div class="card-body">
                    <ul class="list-group d-flex flex-row flex-wrap">

                        @foreach($sites as $site)
                                <li class="list-group-item w-50  list-group-item-action @if(!$site->is_active) bg-blue-400 @endif ">
                                    <a href="{{ route('sites.show', $site->id) }}">
                                        <div class="row">
                                        <div class="col-12">
                                            <h6>
                                                {{ $site->name }}
                                            </h6>
                                            <span class="pull-right
                                                @if(!$site->is_active)
                                                    text-danger
                                                @endif
                                            ">
                                                @if(!$site->is_active)
                                                    unveröffentlicht
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    </a>
                                </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @can('create sites')
            <div class="card bg-light" id="createSite">
                <div class="card-header">
                    <h3>Neue Seite erstellen</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('sites.store') }}" method="post">
                        @csrf
                        <div class="form-group row">
                            <div class="col-sm-12 col-md-6">
                                <label for="title">Titel</label>
                                <input type="text" name="name" id="title" class="form-control" value="{{ old('name') }}">
                                @error('title')
                                <span class="help-block
                                @error('title')
                                    has-error
                                @enderror
                                ">
                                    {{ $message }}
                                </span>
                                @enderror
                            </div>
                                <div class="col-sm-12 col-md-6">
                                    @include('include.formGroups')
                                </div>
                            </div>

                        <div class="form-group row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Speichern</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

    </div>
@endsection
