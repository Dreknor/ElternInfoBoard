@extends('layouts.app')

@section('css')
    <link href="{{asset('css/switch.css')}}" rel="stylesheet" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h5 class="card-title">
                            Module
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-sm">
                            <form class="form-horizontal" method="post" action="{{url('roles')}}">
                                @csrf
                                @method ('put')
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Modulname</th>
                                        <th>Beschreibung</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($module as $modul)
                                        <tr>
                                            <td>
                                                {{$modul->setting}}
                                            </td>
                                            <td>
                                                {{$modul->description}}
                                            </td>
                                            <td>
                                                <!-- Rounded switch -->
                                                <label class="switch">
                                                    <input type="checkbox" id="{{$modul->setting}}" @if($modul->options['active'] ==1) checked @endif>
                                                    <span class="slider round"></span>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="2">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-success btn-block collapse" id="btn-save">speichern</button>
                                            </div>
                                        </td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Scan nach alten oder verwaisten Dateien sowie gelöschten Nachrichten
                </h5>
            </div>
            <div class="card-footer">
                <a href="{{url('settings/scan')}}" class="btn btn-primary btn-block">
                    Scan starten
                </a>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        $('input').on('click', function (e) {
            var Id = this.id;
            console.log(Id);
            console.log(this.checked);
            location.href = '{{url("/settings/modul")}}'+'/'+ Id

        });

    </script>
@endpush
