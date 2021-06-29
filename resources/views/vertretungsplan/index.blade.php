@extends('layouts.app')
@section('title') - Vertretungsplan @endsection

@section('content')
    <div class="container-fluid">
        <iframe src="{{config('app.mitarbeiterboard').'/vertretungsplan'.$gruppen}}" width="100%" height="600px" frameborder="0"></iframe>
    </div>
@endsection
