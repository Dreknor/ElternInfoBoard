@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <iframe src="{{config('app.mitarbeiterboard').'/'.$gruppen}}" width="100%" height="600px"></iframe>
    </div>
@endsection
