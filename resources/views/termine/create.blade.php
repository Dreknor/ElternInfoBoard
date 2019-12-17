@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                Termin erstellen
            </div>
            <div class="card-body">
                <form action="{{url('/termin')}}" method="post" class="form form-horizontal" id="terminForm">
                    @csrf
                    <div class="row">
                        <div class="col-l-6 col-md-12 col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="terminName">Terminname</label>
                                                <input type="text" id="terminName" class="form-control border-input" name="terminname" value="{{old('terminname')}}" required autofocus>
                                            </div>
                                        </div>
                                        <div class="col-l-6 col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label for="start">Start</label>
                                                <input type="datetime-local" value="{{old('start',\Carbon\Carbon::now()->toDateTimeLocalString())}}" id="start" class="form-control border-input date-input" name="start" required >
                                            </div>
                                        </div>
                                        <div class="col-l-6 col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label for="ende">Ende</label>
                                                <input type="datetime-local" value="{{old('ende',\Carbon\Carbon::now()->addHour()->toDateTimeLocalString())}}" id="ende" class="form-control border-input date-input" name="ende" required >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="fullDay">Ganztägig?</label>
                                                <select name="fullDay" id="fullDay" class=custom-select>
                                                    <option value="" selected>nein</option>
                                                    <option value="1">ja</option>

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
                                    @include('include.formGroups')
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
        </div>
    </div>

@endsection


@push('css')

@endpush

@push('js')

    <!--   <script>
        $(document).ready(function () {

           $('#start').change(function (e) {
               var date = new Date($('#start').val());
               $('#ende').val(date.toISOString().substring(0, 19));
           })
        });


    </script>
-->
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