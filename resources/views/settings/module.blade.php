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
                                        <th>mobile Navigation</th>
                                        <th>Aktiv</th>
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
                                                @if(array_key_exists('nav', $modul->options))
                                                    <label class="switch">
                                                        <input type="checkbox" class="bottomMenuButton"
                                                               id="{{$modul->setting}}"
                                                               @if(array_key_exists('bottom-nav' , $modul->options['nav']) and $modul->options['nav']['bottom-nav']== "true") checked @endif>
                                                        <span class="slider round"></span>
                                                    </label>
                                                @else
                                                    ---
                                                @endif
                                            </td>
                                            <td>
                                                <!-- Rounded switch -->
                                                <label class="switch">
                                                    <input type="checkbox" class="activButton" id="{{$modul->setting}}"
                                                           @if($modul->options['active'] ==1) checked @endif>
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
        $('input.activButton').on('click', function (e) {
            var Id = this.id;
            location.href = '{{url("/modules/modul")}}' + '/' + Id

        });

        $('input.bottomMenuButton').on('click', function (e) {
            var Id = this.id;
            location.href = '{{url("/modules/modul/bottomnav")}}' + '/' + Id

        });

    </script>
@endpush
