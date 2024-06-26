@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                Termin bearbeiten
            </div>
            <div class="card-body">
                <form action="{{url('/termin/'.$termin->id)}}" method="post" class="form form-horizontal"
                      id="terminForm">
                    @csrf
                    @method('put')
                    <div class="row">
                        <div class="col-l-6 col-md-12 col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="terminName">Terminname</label>
                                                <input type="text" id="terminName" class="form-control border-input"
                                                       name="terminname"
                                                       value="{{old('terminname',$termin->terminname)}}" required
                                                       autofocus>
                                            </div>
                                        </div>
                                        <div class="col-l-6 col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label for="start">Start</label>
                                                <input type="datetime-local"
                                                       value="{{old('start',$termin->start->toDateTimeLocalString())}}"
                                                       id="start" class="form-control border-input date-input"
                                                       name="start" required>
                                            </div>
                                        </div>
                                        <div class="col-l-6 col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label for="ende">Ende</label>
                                                <input type="datetime-local"
                                                       value="{{old('ende',$termin->ende->toDateTimeLocalString())}}"
                                                       id="ende" class="form-control border-input date-input"
                                                       name="ende" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="fullDay">Ganztägig?</label>
                                                <select name="fullDay" id="fullDay" class="custom-select">
                                                    <option value="" @if($termin->fullDay == null) selected @endif>
                                                        nein
                                                    </option>
                                                    <option value="1" @if($termin->fullDay == true) selected @endif>ja
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="public">öffentlich?</label>
                                                <select name="public" id="public" class="custom-select">
                                                    <option value="" @if($termin->public == null) selected @endif>nein
                                                    </option>
                                                    <option value="1" @if($termin->public == true) selected @endif>ja
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-l-6 col-md-12 col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    @include('include.formGroups',['groups'=>$gruppen,'selectedGroups'=>$termin->groups])
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn btn-primary btn-block" id="submitBtn">
                                Termin speichern
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        @if(auth()->user()->can('edit termin'))
                            <form action="{{url("termin/$termin->id")}}" method="post" class="form-inline">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn-link text-danger">
                                    Termin löschen<i class="far fa-trash-alt"></i>
                                </button>
                            </form>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection


@push('css')

@endpush

@push('js')

    <script>
        $('#submitBtn').on('click', function (event) {
            $("#terminForm").submit();
        })
    </script>

    <script>
        $('.date-input').on('change', function (event) {
            event.target.value = event.target.value.substr(0, 19);
        })
    </script>

@endpush
