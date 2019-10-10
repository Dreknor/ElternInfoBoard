@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title">
            Rückmeldung bearbeiten
        </h5>
    </div>
    <form method="post" action="{{url('userrueckmeldung').'/'.$Rueckmeldung->id}}"  class="form form-horizontal">
        @csrf
        @method('put')
        <div class="col-md-12">
            <div class="form-group">
                <textarea class="form-control border-input" name="text" rows="15">{{$Rueckmeldung->text}}</textarea>
            </div>
        </div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-success btn-block">Rückmeldung senden</button>
        </div>
    </form>
</div>

@endsection

@push('js')
    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',
            plugins: "autoresize",
            menubar: false,

        });</script>


@endpush