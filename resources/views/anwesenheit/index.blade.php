@extends('layouts.anwesenheit')

@section('content')
    <div class="container mt-5">
        <div class="row">
            @if(!$careSettings->view_detailed_care)
                @include('anwesenheit.partials.simple_list')
            @else
                @include('anwesenheit.partials.detailed_view')
            @endif
        </div>
    </div>
@endsection
