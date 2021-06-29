@extends('layouts.app')
@section('title') - Downloads @endsection

@section('content')

    <div class="container-fluid ">
        <div class="card">
            <div class="card-header border-bottom">
                Datei-Downloads
            </div>

            <div class="card-body blur">
                <ul class="list-group">

                    @foreach($medien as $medium)
                        <li class="list-group-item">
                            <a href="{{url('/image/'.$medium->id)}}" target="_blank" class="mx-auto ">
                                <i class="fas fa-file-download"></i>
                                {{$medium->name}}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

@endsection

