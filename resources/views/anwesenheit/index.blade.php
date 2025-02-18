@extends('layouts.anwesenheit')

@section('content')
    <div class="container-fluid">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            @if(!$careSettings->view_detailed_care)
                @include('anwesenheit.partials.simple_list')
            @else
                @include('anwesenheit.partials.detailed_view')
            @endif
        </div>
        <div class="row">
            <div class="col-md-12">
                @if($careSettings->hide_childs_when_absent && !request()->cookie('showAll'))
                    <a href="{{ route('anwesenheit.index', ['showAll' => 1]) }}" class="btn btn-primary">Alle Kinder
                        anzeigen</a>
                @elseif(request()->cookie('showAll'))
                    <a href="{{ route('anwesenheit.index', ['showAll' => 'off']) }}" class="btn btn-primary"
                       id="removeCookie">Nur anwesende Kinder anzeigen</a>
                @endif
            </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="childModal" tabindex="-1" role="dialog" aria-labelledby="childModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="childModalLabel">Übersicht</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3 id="childName"></h3>
                        </div>
                        <div class="card-body">
                            <h6>Schickzeiten für heute:</h6>
                            <div id="schickzeitenContainer" class="mt-3">
                                <!-- Schickzeiten will be displayed here -->
                            </div>
                        </div>
                        <div class="card-body">
                            <h6>Nachricht:</h6>
                            <div id="noticesContainer">
                                <!-- Notices will be displayed here -->
                            </div>
                        </div>
                        <div class="card-footer border-top">
                            <button type="button" class="btn btn-danger" id="logoutButton" style="display: none;">
                                <i class="fa-solid fa-shoe-prints"></i> Abmelden
                            </button>
                            <button type="button" class="btn btn-success" id="checkinButton" style="display: none;">
                                <i class="fa-solid fa-child-reaching"></i>
                                Anmelden
                            </button>
                        </div>
                    </div>

                    <hr>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6>Schickzeit eintragen</h6>
                        </div>
                        <div class="card-body">
                            <form id="schickzeitForm" method="post" action="">
                                @csrf
                                <div class="form-group">
                                    <label for="type">Typ</label>
                                    <select class="form-control" id="type" name="type" required>
                                        <option value="ab">von ... bis ... Uhr</option>
                                        <option value="genau">genau</option>
                                    </select>
                                </div>
                                <div class="form-group collapse" id="genau_row">
                                    <label for="schickzeitTime">Zeit</label>
                                    <input type="time" class="form-control w-100" id="schickzeitTime" name="time">
                                </div>

                                <div class="form-group" id="spaet_row">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="ab">ab ... Uhr</label>
                                            <input type="time" class="form-control w-100" id="ab" name="ab">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="spät.">bis ... Uhr</label>
                                            <input type="time" class="form-control w-100" id="spät." name="spaet">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Schickzeit eintragen</button>
                            </form>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">schließen</button>

                    <div id="spinner" class="spinner-border text-danger" role="status" style="display: none;">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        function toDateWithOutTimeZone(date) {
            let tempTime = date.split(":");
            let dt = new Date();
            dt.setHours(tempTime[0]);
            dt.setMinutes(tempTime[1]);
            dt.setSeconds(tempTime[2]);
            return dt;
        }

        $(document).ready(function () {
            $("#type").change(function () {
                $('#spaet_row').toggle();
                $('#genau_row').toggle();
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            const childModal = $('#childModal');
            const childName = document.getElementById('childName');
            const logoutButton = document.getElementById('logoutButton');
            const spinner = document.getElementById('spinner');
            const schickzeitForm = document.getElementById('schickzeitForm');

            document.querySelectorAll('.child-item').forEach(item => {
                item.addEventListener('click', function () {

                    const childData = JSON.parse(this.dataset.child);
                    const notices = JSON.parse(this.dataset.notices);
                    childName.textContent = `${childData.first_name} ${childData.last_name}`;
                    logoutButton.dataset.childId = childData.id;
                    checkinButton.dataset.childId = childData.id;



                    if (childData.checked_in === 'false') {
                        logoutButton.style.display = 'none';
                        checkinButton.style.display = 'inline-block';
                    } else {
                        logoutButton.style.display = 'inline-block';
                        checkinButton.style.display = 'none';
                    }

                    //Action for the form
                    schickzeitForm.action = `anwesenheit/${childData.id}/schickzeit`;

                    const schickzeitenContainer = document.getElementById('schickzeitenContainer');
                    schickzeitenContainer.innerHTML = '';
                    if (childData.schickzeiten.length > 0) {
                        childData.schickzeiten.forEach(schickzeit => {
                            const schickzeitElement = document.createElement('p');
                            if (schickzeit.type === 'ab' && schickzeit.time_ab && schickzeit.time_spaet) {
                                schickzeitElement.textContent = `${schickzeit.type}: ${toDateWithOutTimeZone(schickzeit.time_ab).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} Uhr - ${toDateWithOutTimeZone(schickzeit.time_spaet).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} Uhr`;

                            } else {
                                schickzeitElement.textContent = `${schickzeit.type}: ${new Date(schickzeit.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} Uhr`;
                            }
                            schickzeitenContainer.appendChild(schickzeitElement);
                        });
                    }

                    const noticesContainer = document.getElementById('noticesContainer');
                    noticesContainer.innerHTML = 'keine Nachrichten';
                    if (notices) {
                        noticesContainer.innerHTML = '';
                            const noticeElement = document.createElement('p');
                            noticeElement.textContent = notices.notice;
                            noticesContainer.appendChild(noticeElement);

                    }

                    childModal.modal('show');
                });
            });

            logoutButton.addEventListener('click', function () {
                const childId = this.dataset.childId;

                logoutButton.style.display = 'none';
                spinner.style.display = 'inline-block';

                $.ajax({
                    url: `anwesenheit/${childId}/abmelden`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    }
                }).done(function () {
                    childModal.modal('hide');
                    window.location.reload();
                }).always(function () {
                    spinner.style.display = 'none';
                    logoutButton.style.display = 'inline-block';
                });
            });


            checkinButton.addEventListener('click', function () {
                const childId = this.dataset.childId;

                checkinButton.style.display = 'none';
                spinner.style.display = 'inline-block';

                $.ajax({
                    url: `anwesenheit/${childId}/anmelden`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    }
                }).done(function () {
                    childModal.modal('hide');
                    window.location.reload();
                }).always(function () {
                    spinner.style.display = 'none';
                    checkinButton.style.display = 'inline-block';
                });
            });
        });
    </script>
@endpush
