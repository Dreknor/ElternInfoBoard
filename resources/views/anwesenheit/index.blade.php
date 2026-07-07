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
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"
             style="max-width: min(75vw, 900px); width: min(75vw, 900px); margin: 1.5rem auto; padding: 0;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="childModalLabel">Übersicht</h5>
                    <button type="button" class="close modal-close-btn" data-dismiss="modal" aria-label="Schließen">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-tabs-wrapper">
                    <ul class="nav nav-pills nav-fill modal-tabs" id="myTab" role="tablist">
                        <li class="nav-item bg-gradient-directional-blue-grey-light">
                            <a class="nav-link text-dark active" id="anwesenheit-tab" data-toggle="tab" href="#Anwesenheit" role="tab" aria-controls="Anwesenheit" aria-selected="true">
                                <i class="fas fa-child mr-1"></i> Anwesenheit
                            </a>
                        </li>
                        <li class="nav-item bg-gradient-directional-blue-grey-light">
                            <a class="nav-link text-dark" id="abfrage-tab" data-toggle="tab" href="#Abfrage" role="tab" aria-controls="Abfrage" aria-selected="false">
                                <i class="fas fa-calendar-alt mr-1"></i> Ferien
                            </a>
                        </li>
                        <li class="nav-item bg-gradient-directional-blue-grey-light">
                            <a class="nav-link text-dark" id="regelmaessig-tab" data-toggle="tab" href="#Regelmaessig" role="tab" aria-controls="Regelmaessig" aria-selected="false">
                                <i class="fas fa-clock mr-1"></i> Schickzeiten
                            </a>
                        </li>
                        <li class="nav-item bg-gradient-directional-grey-blue">
                            <a class="nav-link text-dark" id="vollmacht-tab" data-toggle="tab" href="#vollmacht" role="tab" aria-controls="vollmacht" aria-selected="false">
                                <i class="fas fa-user-check mr-1"></i> Vollmacht
                            </a>
                        </li>
                    </ul>
                </div><!-- /.modal-tabs-wrapper -->
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
                                    <button type="button" class="btn btn-danger btn-block action-btn" id="logoutButton" style="display: none;">
                                        <i class="fa-solid fa-shoe-prints fa-lg"></i>
                                        <span class="action-btn-name" id="checkoutButtonChildName"></span>
                                        <span class="action-btn-label">Abmelden</span>
                                    </button>
                                    <button type="button" class="btn btn-success btn-block action-btn" id="checkinButton" style="display: none;">
                                        <i class="fa-solid fa-child-reaching fa-lg"></i>
                                        <span class="action-btn-name" id="checkinButtonChildName"></span>
                                        <span class="action-btn-label">Anmelden</span>
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
                                        <div class="form-group d-none" id="genau_row">
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
                                <button type="button" class="btn btn-secondary btn-close-modal" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i> Schließen
                                </button>
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

                    <!-- Tab: Regelmäßige Schickzeiten -->
                    <div class="tab-pane fade" id="Regelmaessig" role="tabpanel" aria-labelledby="regelmaessig-tab">
                        <div class="modal-body">
                            <h6>Regelmäßige Schickzeiten:</h6>
                            <div id="regularSchickzeitenContainer" class="mt-2">
                                <!-- Wird per JavaScript gefüllt -->
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

        /**
         * Parst Zeitangaben sowohl im Format "HH:MM:SS" als auch als vollständigen
         * ISO-Datetime-String (z.B. "2026-05-19T14:30:00.000000Z").
         * Hintergrund: Das Feld `time` wird durch einen PHP-Accessor als Carbon-Objekt
         * serialisiert und landet als ISO-String im JSON.
         */
        function parseTimeValue(val) {
            if (!val) return null;
            if (typeof val !== 'string') return null;
            if (val.includes('T')) {
                // ISO-Datetime-String
                const d = new Date(val);
                return isNaN(d.getTime()) ? null : d;
            }
            // "HH:MM:SS" oder "HH:MM"
            return toDateWithOutTimeZone(val);
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
                        checkinButton.style.display = 'flex';
                    } else {
                        logoutButton.style.display = 'flex';
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

                    // Regelmäßige Schickzeiten befüllen
                    const weekdayNames = {0: 'Sonntag', 1: 'Montag', 2: 'Dienstag', 3: 'Mittwoch', 4: 'Donnerstag', 5: 'Freitag', 6: 'Samstag'};
                    const regularContainer = document.getElementById('regularSchickzeitenContainer');
                    regularContainer.innerHTML = '';
                    const regularSchickzeiten = childData.regular_schickzeiten || [];
                    if (regularSchickzeiten.length > 0) {
                        // Gruppieren nach Wochentag
                        const byWeekday = {};
                        regularSchickzeiten.forEach(sz => {
                            const day = sz.weekday;
                            if (!byWeekday[day]) byWeekday[day] = [];
                            byWeekday[day].push(sz);
                        });
                        Object.keys(byWeekday).sort().forEach(day => {
                            const daySection = document.createElement('div');
                            daySection.className = 'mb-2';
                            const dayHeader = document.createElement('strong');
                            dayHeader.textContent = weekdayNames[day] || `Tag ${day}`;
                            daySection.appendChild(dayHeader);
                            byWeekday[day].forEach(sz => {
                                const entry = document.createElement('p');
                                entry.className = 'mb-0 ml-2 text-sm';
                                if (sz.type === 'genau' && sz.time) {
                                    const t = parseTimeValue(sz.time);
                                    entry.textContent = t
                                        ? `Genau: ${t.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} Uhr`
                                        : `Genau: ${sz.time} Uhr`;
                                } else {
                                    let text = '';
                                    if (sz.time_ab) text += `ab ${toDateWithOutTimeZone(sz.time_ab).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} Uhr`;
                                    if (sz.time_spaet) text += ` – spät. ${toDateWithOutTimeZone(sz.time_spaet).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} Uhr`;
                                    entry.textContent = text || '–';
                                }
                                daySection.appendChild(entry);
                            });
                            regularContainer.appendChild(daySection);
                        });
                    } else {
                        regularContainer.innerHTML = '<p class="text-muted">Keine regelmäßigen Schickzeiten hinterlegt.</p>';
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
                    logoutButton.style.display = 'flex';
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
                    checkinButton.style.display = 'flex';
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
                                const row = document.createElement('tr');
                                row.dataset.checkinId = checkin.id;
                                const shouldBeLabel = checkin.should_be === true ? 'Ja' : (checkin.should_be === false ? 'Nein' : 'Offen');
                                const shouldBeClass = checkin.should_be === true ? 'text-success font-weight-bold' : (checkin.should_be === false ? 'text-danger' : 'text-muted');
                                row.innerHTML = `
                                    <td>${new Date(checkin.date).toLocaleDateString('de-DE')}</td>
                                    <td class="${shouldBeClass}">${shouldBeLabel}</td>
                                    <td><button class="btn btn-sm btn-primary toggle-should-be" onclick="toogle_should_be(${checkin.id}, this)"><i class="fa fa-refresh" aria-hidden="true"></i></button></td>
                                `;
                                tableBody.appendChild(row);
                            });
                        })
                        .catch(error => console.error('Fehler beim Laden der Check-Ins:', error));

                    childModal.modal('show');
                });
            });
        });

        function toogle_should_be(checkinId, btn) {
            const url = "{{ route('checkIn.shouldBe', ['checkin' => ':checkin']) }}".replace(':checkin', checkinId);

            // Button während der Anfrage deaktivieren
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                }
            }).done(function (response) {
                // Zeile direkt aktualisieren – kein Seiten-Reload, Modal bleibt offen
                if (btn) {
                    const row = btn.closest('tr');
                    if (row && response.data) {
                        const shouldBe = response.data.should_be;
                        const label = shouldBe === true ? 'Ja' : (shouldBe === false ? 'Nein' : 'Offen');
                        const cls   = shouldBe === true ? 'text-success font-weight-bold' : (shouldBe === false ? 'text-danger' : 'text-muted');
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 2) {
                            cells[1].textContent  = label;
                            cells[1].className    = cls;
                        }
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-refresh" aria-hidden="true"></i>';
                }
            }).fail(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-refresh" aria-hidden="true"></i>';
                }
                alert('Fehler beim Aktualisieren.');
            });
        }

    </script>
@endpush
