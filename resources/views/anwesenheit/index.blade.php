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

        @if($children->isEmpty() && $groups->isEmpty() && $classes->isEmpty())
            <div class="alert alert-info">
                <h4>Keine Daten verfügbar</h4>
                <p>Es wurden keine Kinder, Gruppen oder Klassen gefunden. Bitte überprüfen Sie die Einstellungen.</p>
                <p>Debug-Info:</p>
                <ul>
                    <li>Anzahl Kinder: {{ $children->count() }}</li>
                    <li>Anzahl Gruppen: {{ $groups->count() }}</li>
                    <li>Anzahl Klassen: {{ $classes->count() }}</li>
                    <li>Konfigurierte Gruppen: {{ implode(', ', $careSettings->groups_list ?? []) }}</li>
                    <li>Konfigurierte Klassen: {{ implode(', ', $careSettings->class_list ?? []) }}</li>
                </ul>
            </div>
        @elseif($children->isEmpty())
            <div class="alert alert-warning">
                <h4>Keine Kinder gefunden</h4>
                <p>Es wurden keine Kinder für die ausgewählten Gruppen und Klassen gefunden.</p>
                @if($careSettings->hide_childs_when_absent && !request()->cookie('showAll'))
                    <p>Möglicherweise sind heute keine Kinder angemeldet. Versuchen Sie, alle Kinder anzuzeigen.</p>
                @endif
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
                <div class="card-body">
                    <ul class="nav nav-pills card-header-tabs  nav-fill"  id="myTab" role="tablist">
                        <li class="nav-item  bg-gradient-directional-blue-grey-light ">
                            <a class="nav-link text-dark active" id="anwesenheit-tab" data-toggle="tab" href="#Anwesenheit" role="tab" aria-controls="Anwesenheit" aria-selected="true">Anwesenheit</a>
                        </li>
                        <li class="nav-item bg-gradient-directional-blue-grey-light">
                            <a class="nav-link text-dark" id="abfrage-tab" data-toggle="tab" href="#Abfrage" role="tab" aria-controls="Abfrage" aria-selected="false">Ferien</a>
                        </li>

                        <li class="nav-item bg-gradient-directional-grey-blue">
                            <a class="nav-link text-dark" id="vollmacht-tab" data-toggle="tab" href="#vollmacht" role="tab" aria-controls="vollmacht" aria-selected="false">Abholvollmacht</a>
                        </li>

                    </ul>
                </div>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="Anwesenheit" role="tabpanel" aria-labelledby="anwesenheit-tab">
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
                                    <button type="button" class="btn btn-danger  btn-block" id="logoutButton" style="display: none;">
                                        <i class="fa-solid fa-shoe-prints"></i> <div id="checkoutButtonChildName" class="d-inline"></div> <div class="d-inline text-medium">Abmelden</div>
                                    </button>
                                    <button type="button" class="btn btn-success btn-block text-greater" id="checkinButton" style="display: none;">
                                        <i class="fa-solid fa-child-reaching"></i> <div id="checkinButtonChildName" class="d-inline"></div> <div class="d-inline text-medium"> Anmelden </div>
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
                                                <div class="col-6">
                                                    <label for="ab">ab ... Uhr</label>
                                                    <input type="time" class="form-control w-100" id="ab" name="ab">
                                                </div>
                                                <div class="col-6 ">
                                                    <label for="spät.">bis ... Uhr</label>
                                                    <input type="time" class="form-control w-100" id="spät." name="spaet">
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Schickzeit eintragen</button>
                                    </form>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6>Notiz hinzufügen</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="" id="noticeForm">
                                        @csrf
                                        <div class="form-group">
                                            <label for="date">Datum</label>
                                            <div class="">
                                                <input id="date" type="date" class="form-control" name="date"
                                                       value="{{now()->format('Y-m-d')}}" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="notice">Notiz</label>
                                            <div class="">
                                                <textarea id="notice" class="form-control" name="notice"></textarea>
                                            </div>
                                        </div>
                                        <input type="hidden" name="child_id" id="child_id">
                                        <button type="submit" class="btn btn-primary">Notiz hinzufügen</button>
                                    </form>
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
                    <div class="tab-pane fade" id="Abfrage" role="tabpanel" aria-labelledby="abfrage-tab">
                        <div class="tab-content border-top" id="myTabContent">
                            <div class="modal-body">
                                <div class="table-responsive-md">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>Datum</th>
                                            <th>angemeldet?</th>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="vollmacht" role="tabpanel" aria-labelledby="vollmacht-tab">
                        <div class="modal-body">
                            <b>Abholvollmachten:</b>
                           <ul class="list-group">

                           </ul>
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
            const checkoutButtonChildName = document.getElementById('checkoutButtonChildName');
            const checkinButton = document.getElementById('checkinButton');
            const checkinButtonChildName = document.getElementById('checkinButtonChildName');
            const spinner = document.getElementById('spinner');
            const schickzeitForm = document.getElementById('schickzeitForm');
            const noticeForm = document.getElementById('noticeForm');
            var url_anmelden = "{{url('care/anwesenheit/:childId/anmelden')}}";
            var url_abmelden = "{{url('care/anwesenheit/:childId/abmelden')}}";



            document.querySelectorAll('.child-item').forEach(item => {
                item.addEventListener('click', function () {
                    const childData = JSON.parse(this.dataset.child);
                    const notices = JSON.parse(this.dataset.notices);
                    childName.textContent = `${childData.first_name} ${childData.last_name}`;
                    logoutButton.dataset.childId = childData.id;
                    checkoutButtonChildName.textContent = childData.first_name;
                    checkinButton.dataset.childId = childData.id;
                    checkinButtonChildName.textContent = childData.first_name;


                    if (childData.checked_in === 'false') {
                        logoutButton.style.display = 'none';
                        checkinButton.style.display = 'inline-block';
                    } else {
                        logoutButton.style.display = 'inline-block';
                        checkinButton.style.display = 'none';
                    }

                    //Action for the form
                    schickzeitForm.action = `anwesenheit/${childData.id}/schickzeit`;
                    noticeForm.action = `child/${childData.id}/notice`;

                    const schickzeitenContainer = document.getElementById('schickzeitenContainer');
                    schickzeitenContainer.innerHTML = '';



                    if (childData.schickzeiten.length > 0) {
                        console.log(childData.schickzeiten);
                        childData.schickzeiten.forEach(schickzeit => {
                            const schickzeitElement = document.createElement('p');
                            if (schickzeit.type == 'genau') {
                                schickzeitElement.textContent = `${schickzeit.type}: ${new Date(schickzeit.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} Uhr`;
                            } else {
                                if(schickzeit.time_ab) {
                                    schickzeitElement.textContent += `ab ${toDateWithOutTimeZone(schickzeit.time_ab).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                                }
                                if(schickzeit.time_spaet) {
                                    schickzeitElement.textContent += ` bis ${toDateWithOutTimeZone(schickzeit.time_spaet).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                                }
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

                    const vollmachtList = document.querySelector('#vollmacht .list-group');

                    if (vollmachtList) {
                        vollmachtList.innerHTML = '';
                        if (childData.mandates && childData.mandates.length > 0) {
                            childData.mandates.forEach(function(m) {
                                const li = document.createElement('li');
                                li.className = 'list-group-item';
                                const desc = m.mandate_description ? '<br>' + m.mandate_description : '';
                                li.innerHTML = '<div><b>' + (m.mandate_name || '') + '</b>' + desc + '</div>';
                                vollmachtList.appendChild(li);
                            });
                        } else {
                            const li = document.createElement('li');
                            li.className = 'list-group-item';
                            li.textContent = 'Keine Abholvollmachten hinterlegt';
                            vollmachtList.appendChild(li);
                        }
                    }


                    childModal.modal('show');
                });
            });

            logoutButton.addEventListener('click', function () {
                const childId = this.dataset.childId;


                logoutButton.style.display = 'none';
                spinner.style.display = 'inline-block';

                $.ajax({
                    url: url_abmelden.replace(':childId', childId),
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
                    url: url_anmelden.replace(':childId', childId),
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


        document.addEventListener('DOMContentLoaded', function () {
            const childModal = $('#childModal');
            const tableBody = document.querySelector('table tbody');

            document.querySelectorAll('.child-item').forEach(item => {
                item.addEventListener('click', function () {
                    const childData = JSON.parse(this.dataset.child);
                    const childId = childData.id;


                    const url = `{{ route('checkins.api',['child' => 'childID']) }}`.replace('childID', childId);

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            tableBody.innerHTML = ''; // Tabelle leeren
                            if (data.length === 0) {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="2">Keine Check-Ins gefunden</td>
                                `;
                                tableBody.appendChild(row);
                                return;
                            }

                            data =data.data;
                            data.forEach(checkin => {
                                $toogleUrl = "{{ route('checkIn.shouldBe', ['checkin' => ':checkin']) }}";
                                $toogleUrl.replace(':checkin', checkin.id)
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${new Date(checkin.date).toLocaleDateString('de-DE')}</td>
                                    <td>${checkin.should_be ? 'Ja' : 'Nein'}</td>
                                    <td><button class="btn btn-sm btn-primary toggle-should-be" onclick="toogle_should_be(${checkin.id})"><i class="fa fa-refresh" aria-hidden="true"></i></button></td>
                                `;
                                tableBody.appendChild(row);
                            });
                        })
                        .catch(error => console.error('Fehler beim Laden der Check-Ins:', error));

                    childModal.modal('show');
                });
            });
        });

        function toogle_should_be(checkinId) {
            const url = "{{ route('checkIn.shouldBe', ['checkin' => ':checkin']) }}".replace(':checkin', checkinId);
            console.log('click');
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                }
            }).done(function () {
                window.location.reload();
            });
        }

    </script>
@endpush
