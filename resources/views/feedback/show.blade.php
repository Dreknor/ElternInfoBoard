@extends('layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                Nachricht erstellen an:
            </h6>
        </div>
        <div class="card-body">
            <form action="{{url("/feedback")}}" method="post" class="form form-horizontal">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <select name="mitarbeiter" class="custom-select">
                                <option value="">Sekretariat</option>
                                @foreach($mitarbeiter as $Mitarbeiter)
                                    <option value="{{$Mitarbeiter->id}}">{{$Mitarbeiter->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <textarea class="form-control border-input" name="text">
                                {{old('text')}}
                            </textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            Feedback senden
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

@endsection

@push('css')


@endpush

@push('js')

    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',
            height: 500,
            menubar: false,
        });</script>



    <script>


    </script>


@endpush