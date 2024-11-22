@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h5 class="card-title
                        ">
                            Einstellungen
                        </h5>
                    </div>
                    <div class="card-body border-bottom">
                        <ul class="nav nav-tabs" id="SettingsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="home-tab" data-toggle="tab" data-target="#home"
                                        type="button" role="tab" aria-controls="home" aria-selected="true">Home
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-tab" data-toggle="tab" data-target="#profile"
                                        type="button" role="tab" aria-controls="profile" aria-selected="false">Profile
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="messages-tab" data-toggle="tab" data-target="#messages"
                                        type="button" role="tab" aria-controls="messages" aria-selected="false">Messages
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="settings-tab" data-toggle="tab" data-target="#settings"
                                        type="button" role="tab" aria-controls="settings" aria-selected="false">Settings
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                <div class="" id="GeneralSettings">
                                    <form action="{{url('settings/general')}}" method="post" class="form-horizontal"
                                          id="" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-row mt-1 p-2 border">
                                            <div class="col-md-6 col-sm-12">
                                                <label class="label-control w-100 ">
                                                    App-Name
                                                    <input type="text" class="form-control" name="app_name"
                                                           value="{{$settings->app_name}}">
                                                </label>
                                            </div>
                                            <div class="col-md-6 col-sm-12 m-auto">
                                                <div class="small">
                                                    Hier wird der Name des Boards geändert
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-row mt-1 p-2 border">
                                            <div class="col-md-6 col-sm-12">
                                                <label class="label-control w-100 ">
                                                    App-Logo
                                                    <input type="file" class="form-control" name="app_logo"
                                                           accept="image/*">
                                                </label>
                                            </div>
                                            <div class="col-md-6 col-sm-12 m-auto">
                                                <div class="small">
                                                    Hier wird das Logo des Boards geändert
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-row mt-1 p-2 border">
                                            <div class="col-md-6 col-sm-12">
                                                <label class="label-control w-100 ">
                                                    Favicon
                                                    <input type="file" class="form-control" name="favicon"
                                                           accept="image/*" max="">
                                                </label>
                                            </div>
                                            <div class="col-md-6 col-sm-12 m-auto">
                                                <div class="small">
                                                    Favicons sind kleine Symbole, die in der Adressleiste des Browsers
                                                    angezeigt werden. Sie können auch in Lesezeichen und in der
                                                    Registerkarte des Browsers angezeigt werden. Das Bild sollte
                                                    quadratisch sein und etwa 260 x 260 Pixel groß sein.
                                                </div>
                                            </div>
                                        </div>


                                        <div class="form-row">
                                            <button type="submit" class="btn btn-success btn-block">
                                                Einstellungen speichern
                                            </button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                            <div class="tab-pane" id="profile" role="tabpanel" aria-labelledby="profile-tab">...</div>
                            <div class="tab-pane" id="messages" role="tabpanel" aria-labelledby="messages-tab">...</div>
                            <div class="tab-pane" id="settings" role="tabpanel" aria-labelledby="settings-tab">...</div>
                        </div>
                    </div>

                    <div class="card-body">

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            $('#SettingsTab a').on('click', function (e) {
                e.preventDefault()
                console.log('clicked')
                console.log($(this))
                $(this).tab('show')
            })
        });
    </script>
@endpush
