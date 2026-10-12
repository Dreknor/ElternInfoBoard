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
                @if($careSettings->hide_childs_when_absent && (!request()->cookie('showAll') || request()->cookie('showAll') === 'expected'))
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
                @if($careSettings->hide_childs_when_absent)
                    @php
                        $currentMode = request()->cookie('showAll');
                        $currentMode = $currentMode === 'expected' && $careSettings->view_detailed_care ? 'expected' : ($currentMode && $currentMode !== 'expected' ? 'all' : 'present');
                    @endphp
                    @if($currentMode !== 'present')
                        <a href="{{ route('anwesenheit.index', ['showAll' => 'off']) }}" class="btn btn-primary"
                           id="removeCookie">Nur anwesende Kinder anzeigen</a>
                    @endif
                    @if($currentMode !== 'expected' && $careSettings->view_detailed_care)
                        <a href="{{ route('anwesenheit.index', ['showAll' => 'expected']) }}" class="btn btn-primary">Anwesende und erwartete Kinder anzeigen</a>
                    @endif
                    @if($currentMode !== 'all')
                        <a href="{{ route('anwesenheit.index', ['showAll' => 1]) }}" class="btn btn-primary">Alle Kinder anzeigen</a>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="childModal" tabindex="-1" role="dialog" aria-labelledby="childModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="childModalLabel">Übersicht</h5>
                    <button type="button" class="close modal-close-btn" data-dismiss="modal" aria-label="Schließen">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-tabs-wrapper">
                    <ul class="nav nav-pills nav-fill modal-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="anwesenheit-tab" data-toggle="tab" href="#Anwesenheit" role="tab" aria-controls="Anwesenheit" aria-selected="true" title="Anwesenheit, Schickzeit und Notiz für heute">
                                <i class="fas fa-child"></i><span>Heute</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="abfrage-tab" data-toggle="tab" href="#Abfrage" role="tab" aria-controls="Abfrage" aria-selected="false" title="Angemeldete Tage aus der Anwesenheitsabfrage">
                                <i class="fas fa-calendar-alt"></i><span>Abfrage</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="regelmaessig-tab" data-toggle="tab" href="#Regelmaessig" role="tab" aria-controls="Regelmaessig" aria-selected="false" title="Regelmäßige Schickzeiten">
                                <i class="fas fa-clock"></i><span>Schickzeiten</span>
                            </a>
                        </li>
                        @if($careSettings->show_mandates)
                        <li class="nav-item">
                            <a class="nav-link" id="vollmacht-tab" data-toggle="tab" href="#vollmacht" role="tab" aria-controls="vollmacht" aria-selected="false" title="Abholvollmachten">
                                <i class="fas fa-user-check"></i><span>Vollmachten</span>
                            </a>
                        </li>
                        @endif
                        @if($careSettings->show_parents)
                        <li class="nav-item">
                            <a class="nav-link" id="eltern-tab" data-toggle="tab" href="#Eltern" role="tab" aria-controls="Eltern" aria-selected="false" title="Erziehungsberechtigte">
                                <i class="fas fa-users"></i><span>Eltern</span>
                            </a>
                        </li>
                        @endif
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
                                                @if($schickzeitenSettings->schicken_erlaube_zeitraum)
                                                    <option value="ab">von ... bis ... Uhr</option>
                                                @endif
                                                <option value="genau">genau</option>
                                            </select>
                                        </div>
                                        <div class="form-group @if($schickzeitenSettings->schicken_erlaube_zeitraum) d-none @endif" id="genau_row">
                                            <label for="schickzeitTime">Zeit</label>
                                            <input type="time" class="form-control w-100" id="schickzeitTime" name="time">
                                        </div>

                                        <div class="form-group @unless($schickzeitenSettings->schicken_erlaube_zeitraum) d-none @endunless" id="spaet_row">
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
                        <div class="modal-body border-top">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Datum</th>
                                        <th>angemeldet?</th>
                                        <th class="text-right">ändern</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>

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
                    @if($careSettings->show_mandates)
                    <div class="tab-pane fade" id="vollmacht" role="tabpanel" aria-labelledby="vollmacht-tab">
                        <div class="modal-body border-top">
                            <b>Abholvollmachten:</b>
                            <ul class="list-group mt-2">

                            </ul>
                        </div>
                    </div>
                    @endif
                    @if($careSettings->show_parents)
                    <div class="tab-pane fade" id="Eltern" role="tabpanel" aria-labelledby="eltern-tab">
                        <div class="modal-body border-top">
                            <b>Erziehungsberechtigte:</b>
                            <ul class="list-group mt-2" id="elternContainer">
                            </ul>
                        </div>
                    </div>
                    @endif
                </div><!-- /.tab-content -->
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /#childModal -->
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


            // Event-Delegation: funktioniert auch für Einträge, die nach dem
            // An-/Abmelden per refreshAttendanceList() neu eingesetzt wurden
            document.addEventListener('click', function (event) {
                const item = event.target.closest('.child-item');
                if (!item) return;

                const childData = JSON.parse(item.dataset.child);
                const notices = JSON.parse(item.dataset.notices);
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
                if (notices && notices.notice) {
                    noticesContainer.innerHTML = '';

                    const noticeElement = document.createElement('div');
                    noticeElement.className = 'rounded-md border border-amber-200 bg-amber-50 p-3';

                    const messageElement = document.createElement('p');
                    messageElement.className = 'mb-2 text-sm text-slate-700 whitespace-pre-wrap';
                    messageElement.textContent = notices.notice;
                    noticeElement.appendChild(messageElement);

                    const metaElement = document.createElement('div');
                    metaElement.className = 'text-xs text-slate-500';
                    const createdAt = notices.created_at_formatted || new Date(notices.created_at).toLocaleString('de-DE', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'});
                    metaElement.textContent = `Von ${notices.author_name || 'Unbekannt'} am ${createdAt}`;
                    noticeElement.appendChild(metaElement);

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

                const elternContainer = document.getElementById('elternContainer');
                if (elternContainer) {
                    elternContainer.innerHTML = '';
                    const parents = childData.parents || [];
                    if (parents.length > 0) {
                        parents.forEach(function(p) {
                            const li = document.createElement('li');
                            li.className = 'list-group-item';
                            const emailLink = p.email
                                ? ` &nbsp;<a href="mailto:${p.email}" class="text-muted small"><i class="fas fa-envelope mr-1"></i>${p.email}</a>`
                                : '';
                            let phoneLinks = '';
                            if (p.phone) {
                                phoneLinks += ` &nbsp;<a href="tel:${p.phone}" class="text-muted small"><i class="fas fa-phone mr-1"></i>${p.phone}</a>`;
                            }
                            if (p.publicPhone && p.publicPhone !== p.phone) {
                                phoneLinks += ` &nbsp;<a href="tel:${p.publicPhone}" class="text-muted small"><i class="fas fa-phone mr-1"></i>${p.publicPhone}</a>`;
                            }
                            li.innerHTML = '<i class="fas fa-user mr-2"></i><b>' + (p.name || '') + '</b>' + emailLink + phoneLinks;
                            elternContainer.appendChild(li);
                        });
                    } else {
                        const li = document.createElement('li');
                        li.className = 'list-group-item text-muted';
                        li.textContent = 'Keine Erziehungsberechtigten hinterlegt';
                        elternContainer.appendChild(li);
                    }
                }

                childModal.modal('show');
            });

            function changeAttendance(button, url) {
                const childId = button.dataset.childId;

                button.style.display = 'none';
                spinner.style.display = 'inline-block';

                $.ajax({
                    url: url.replace(':childId', childId),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    }
                }).done(function () {
                    childModal.modal('hide');
                    document.querySelectorAll('.child-item').forEach(function (item) {
                        if (JSON.parse(item.dataset.child).id == childId) {
                            item.classList.add('child-item-updating');
                        }
                    });
                    refreshAttendanceList();
                }).fail(function () {
                    alert('Die Anwesenheit konnte nicht gespeichert werden. Bitte erneut versuchen.');
                }).always(function () {
                    spinner.style.display = 'none';
                    button.style.display = 'flex';
                });
            }

            logoutButton.addEventListener('click', function () {
                changeAttendance(this, url_abmelden);
            });

            checkinButton.addEventListener('click', function () {
                changeAttendance(this, url_anmelden);
            });
        });

        /**
         * Rendert die Seite im Hintergrund neu und tauscht nur die Kinderliste aus.
         * Ersetzt das frühere window.location.reload(): kein Neuladen von CSS/JS,
         * kein Aufblitzen, Scrollposition, Suche und Filter bleiben erhalten.
         */
        let attendanceRefreshSeq = 0;

        function refreshAttendanceList() {
            const seq = ++attendanceRefreshSeq;

            fetch(window.location.href, {cache: 'no-store', credentials: 'same-origin'})
                .then(function (response) {
                    if (!response.ok || response.redirected) throw new Error('HTTP ' + response.status);
                    return response.text();
                })
                .then(function (html) {
                    // Eine spätere Aktualisierung ist bereits unterwegs – diese verwerfen
                    if (seq !== attendanceRefreshSeq) return;

                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.getElementById('groups-container');
                    const current = document.getElementById('groups-container');
                    if (!fresh || !current) throw new Error('Kinderliste nicht gefunden');

                    current.innerHTML = fresh.innerHTML;

                    const lastUpdated = document.getElementById('last-updated-time');
                    if (lastUpdated) {
                        lastUpdated.textContent = new Date().toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
                    }

                    document.dispatchEvent(new CustomEvent('anwesenheit:list-updated'));
                })
                .catch(function (error) {
                    // z.B. abgelaufene Sitzung → vollständig neu laden
                    console.error('Aktualisieren der Kinderliste fehlgeschlagen:', error);
                    window.location.reload();
                });
        }


        // Gemeinsame Variable für das aktuell geöffnete Kind (wird beim Modal-Öffnen gesetzt)
        let currentCheckinChildId = null;
        const checkinsApiUrlTemplate = `{{ route('checkins.api',['child' => 'childID']) }}`;

        function renderCheckinRow(row, checkin) {
            const shouldBeLabel = checkin.should_be === true ? 'Ja' : (checkin.should_be === false ? 'Nein' : 'Offen');
            const shouldBeClass = checkin.should_be === true ? 'text-success font-weight-bold' : (checkin.should_be === false ? 'text-danger' : 'text-muted');
            const date = new Date(checkin.date).toLocaleDateString('de-DE', {weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric'});
            row.dataset.checkinId = checkin.id;
            row.innerHTML = `
                <td>${date}</td>
                <td class="${shouldBeClass}">${shouldBeLabel}</td>
                <td class="text-right"><button type="button" class="btn btn-sm btn-primary toggle-should-be" onclick="toogle_should_be(${checkin.id}, this)" title="Anmeldung ändern"><i class="fa fa-refresh" aria-hidden="true"></i></button></td>
            `;
        }

        function reloadCheckinsTable() {
            if (!currentCheckinChildId) return;
            const tableBody = document.querySelector('#Abfrage table tbody');
            if (!tableBody) return;

            const childId = currentCheckinChildId;
            const url = checkinsApiUrlTemplate.replace('childID', childId);

            // no-store: Antwort darf weder aus HTTP- noch aus Browser-Cache kommen
            fetch(url, {cache: 'no-store', headers: {'Accept': 'application/json'}})
                .then(response => response.json())
                .then(data => {
                    // Inzwischen wurde ein anderes Kind geöffnet – veraltete Antwort verwerfen
                    if (childId !== currentCheckinChildId) return;

                    tableBody.innerHTML = '';
                    const checkins = data.data;
                    if (!checkins || checkins.length === 0) {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td colspan="3">Keine Check-Ins gefunden</td>`;
                        tableBody.appendChild(row);
                        return;
                    }

                    checkins.forEach(checkin => {
                        const row = document.createElement('tr');
                        renderCheckinRow(row, checkin);
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => console.error('Fehler beim Laden der Check-Ins:', error));
        }

        document.addEventListener('click', function (event) {
            const item = event.target.closest('.child-item');
            if (!item) return;

            currentCheckinChildId = JSON.parse(item.dataset.child).id;
            reloadCheckinsTable();
        });

        function toogle_should_be(checkinId, btn) {
            const url = "{{ route('checkIn.shouldBe', ['checkin' => ':checkin']) }}".replace(':checkin', checkinId);

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
                if (!response || !response.success) {
                    resetToggleButton(btn);
                    alert(response && response.message ? response.message : 'Fehler beim Aktualisieren.');
                    return;
                }
                // Zeile sofort mit dem neuen Stand aus der Antwort aktualisieren
                const row = btn ? btn.closest('tr') : null;
                if (row && response.data) {
                    renderCheckinRow(row, response.data);
                }
            }).fail(function () {
                resetToggleButton(btn);
                alert('Fehler beim Aktualisieren.');
            });
        }

        function resetToggleButton(btn) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-refresh" aria-hidden="true"></i>';
            }
        }

    </script>
@endpush
