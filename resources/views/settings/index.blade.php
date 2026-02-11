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
                                <button class="nav-link" id="Email-tab" data-toggle="tab" data-target="#email"
                                        type="button" role="tab" aria-controls="profile" aria-selected="false">Email
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="notify-tab" data-toggle="tab" data-target="#notify"
                                        type="button" role="tab" aria-controls="notify" aria-selected="false">Benachrichtigungen
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="schickzeiten-tab" data-toggle="tab" data-target="#schickzeiten"
                                        type="button" role="tab" aria-controls="schicken" aria-selected="false">Schickzeiten
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="care-tab" data-toggle="tab" data-target="#care"
                                        type="button" role="tab" aria-controls="care" aria-selected="false">Care
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="keycloak-tab" data-toggle="tab" data-target="#keycloak"
                                        type="button" role="tab" aria-controls="care" aria-selected="false">OIDC
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pflichtstunden-tab" data-toggle="tab" data-target="#pflichtstunden"
                                        type="button" role="tab" aria-controls="pflichtstunden" aria-selected="false">Pflichtstunden
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="schoolyear-tab" data-toggle="tab" data-target="#schoolyear"
                                        type="button" role="tab" aria-controls="schoolyear" aria-selected="false">Schuljahreswechsel
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="stundenplan-tab" data-toggle="tab" data-target="#stundenplan"
                                        type="button" role="tab" aria-controls="stundenplan" aria-selected="false">Stundenplan
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            @include('settings.tabs.home-tab')
                            @include('settings.tabs.email-tab')
                            @include('settings.tabs.notify-tab')
                            @include('settings.tabs.schickzeiten-tab')
                            @include('settings.tabs.care-tab')
                            @include('settings.tabs.keycloak-tab')
                            @include('settings.tabs.schoolyear-tab')
                            @include('settings.tabs.pflichtstunden-tab')
                            @include('settings.tabs.stundenplan-tab')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.0.1/js/plugins/piexif.min.js" type="text/javascript"></script>

    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea:not(.no-tinymce)',
            lang:'de',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink  link charmap',
                'searchreplace visualblocks code',
                'insertdatetime  paste code wordcount',
                'contextmenu textcolor',
            ],

            toolbar: 'undo redo | formatselect | bold italic ',
            contextmenu: " link  inserttable | cell row column deletetable",

        });


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
