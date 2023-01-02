@extends('layouts.app')
@section('title') - Listen @endsection

@section('content')
    <div class="container-fluid">
        <a class="btn btn-outline-info" href="{{url('listen')}}">zurück zur Übersicht</a>
        <div class="card">
            <div class="card-header border-bottom @if($liste->active == 0) bg-info @endif">
                <h5>
                    {{$liste->listenname}} @if($liste->active == 0)
                        (inaktiv)
                    @endif
                </h5>

                {!! $liste->comment !!}

                @if(auth()->user()->id == $liste->besitzer or auth()->user()->can('edit terminliste'))
                    <button onclick="generatePDF()"class="btn btn-info pull-right">Download as PDF</button>
                @endif
            </div>
            <div class="card-body">
                @if($liste->eintragungen->filter(function ($eintragung){
                            return $eintragung->user_id == auth()->id();
                        })->count() == null or $liste->multiple or $liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                    <div class="container-fluid">
                        <form class="form-horizontal" action="{{url('/listen/'.$liste->id.'/eintragungen/')}}"
                              method="post">
                            @csrf
                            <div class="row">
                                <div class="col-9">
                                    <input name="eintragung" type="text" maxlength="100" class="form-control mt-2 p-3"
                                           placeholder="Eintrag">
                                </div>
                                <div class="col-3">
                                    <button type="submit" class="btn btn-block btn-outline-success">
                                        <i class="fa fa-save"></i>
                                        <div class="d-none d-md-inline">
                                            speichern
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                @endif
            </div>
            <div class="card-body" id="export">
                @if($liste->eintragungen->count()> 0)
                    <ul class="list-group">

                        @foreach($liste->eintragungen as $eintrag)
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col pt-2">
                                        {{$eintrag->eintragung}}
                                    </div>
                                    <div class="col-auto pull-right">
                                        @if($eintrag->user_id != null)
                                            @if($liste->visible_for_all or auth()->user()->can('edit terminliste') or $eintrag->user_id == auth()->id())
                                                {{$eintrag->user->name }}
                                            @else
                                                vergeben
                                            @endif
                                        @else
                                            <form method="post" action="{{url("listen/eintragungen/".$eintrag->id)}}"
                                                  class="form-inline m-0 p-0">
                                                @csrf
                                                @method('put')
                                                <button type="submit" class=" btn-link ">reservieren</button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="col-auto pull-right">
                                        @if($eintrag->user_id == auth()->id() or ($eintrag->created_by == auth()->id() and $eintrag->user_id == null))
                                            <form method="post" action="{{url("listen/eintragungen/".$eintrag->id)}}"
                                                  class="form-inline m-0 p-0">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn-link text-danger">löschen</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-info">
                        <p>
                            Es wurden bisher keine Eintragungen angelegt.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
@push('js')
    <script src="{{asset('js/html2pdf.bundle.min.js')}}"></script>
    <script>
        function generatePDF() {
            // Choose the element that our invoice is rendered in.
            const element = document.getElementById('export');
            // Choose the element and save the PDF for our user.
            html2pdf().from(element).save();
        }
    </script>
@endpush
