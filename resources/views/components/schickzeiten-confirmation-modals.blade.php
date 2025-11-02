{{-- Bestätigungsmodal für Änderung von regelmäßigen Schickzeiten (Elternseite) --}}
@if(session('type') == 'confirm')
    <div class="modal fade show" id="confirmModal" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bestätigung erforderlich</h5>
                </div>
                <div class="modal-body">
                    <p>{{session('Meldung')}}</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="{{route('schickzeiten.store', ['child' => session('confirm_data.child_id'), 'weekday' => session('confirm_data.weekday') == 1 ? 'Montag' : (session('confirm_data.weekday') == 2 ? 'Dienstag' : (session('confirm_data.weekday') == 3 ? 'Mittwoch' : (session('confirm_data.weekday') == 4 ? 'Donnerstag' : 'Freitag')))])}}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="type" value="{{session('confirm_data.type')}}">
                        <input type="hidden" name="time" value="{{session('confirm_data.time')}}">
                        <input type="hidden" name="time_ab" value="{{session('confirm_data.time_ab')}}">
                        <input type="hidden" name="time_spaet" value="{{session('confirm_data.time_spaet')}}">
                        <input type="hidden" name="update_daily_times" value="yes">
                        <button type="submit" class="btn btn-primary">Ja, anpassen</button>
                    </form>
                    <form method="POST" action="{{route('schickzeiten.store', ['child' => session('confirm_data.child_id'), 'weekday' => session('confirm_data.weekday') == 1 ? 'Montag' : (session('confirm_data.weekday') == 2 ? 'Dienstag' : (session('confirm_data.weekday') == 3 ? 'Mittwoch' : (session('confirm_data.weekday') == 4 ? 'Donnerstag' : 'Freitag')))])}}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="type" value="{{session('confirm_data.type')}}">
                        <input type="hidden" name="time" value="{{session('confirm_data.time')}}">
                        <input type="hidden" name="time_ab" value="{{session('confirm_data.time_ab')}}">
                        <input type="hidden" name="time_spaet" value="{{session('confirm_data.time_spaet')}}">
                        <input type="hidden" name="update_daily_times" value="delete">
                        <button type="submit" class="btn btn-danger">Ja, löschen</button>
                    </form>
                    <form method="POST" action="{{route('schickzeiten.store', ['child' => session('confirm_data.child_id'), 'weekday' => session('confirm_data.weekday') == 1 ? 'Montag' : (session('confirm_data.weekday') == 2 ? 'Dienstag' : (session('confirm_data.weekday') == 3 ? 'Mittwoch' : (session('confirm_data.weekday') == 4 ? 'Donnerstag' : 'Freitag')))])}}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="type" value="{{session('confirm_data.type')}}">
                        <input type="hidden" name="time" value="{{session('confirm_data.time')}}">
                        <input type="hidden" name="time_ab" value="{{session('confirm_data.time_ab')}}">
                        <input type="hidden" name="time_spaet" value="{{session('confirm_data.time_spaet')}}">
                        <input type="hidden" name="update_daily_times" value="no">
                        <button type="submit" class="btn btn-secondary">Nein, unverändert lassen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Bestätigungsmodal für Löschung von regelmäßigen Schickzeiten (Elternseite, alte Verwaltung) --}}
@if(session('type') == 'confirm_delete')
    <div class="modal fade show" id="confirmDeleteModal" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bestätigung erforderlich</h5>
                </div>
                <div class="modal-body">
                    <p>{{session('Meldung')}}</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="{{url('schickzeiten/'.session('confirm_delete_data.day').'/'.session('confirm_delete_data.child'))}}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="delete_daily_times" value="yes">
                        <button type="submit" class="btn btn-danger">Ja, auch löschen</button>
                    </form>
                    <form method="POST" action="{{url('schickzeiten/'.session('confirm_delete_data.day').'/'.session('confirm_delete_data.child'))}}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="delete_daily_times" value="no">
                        <button type="submit" class="btn btn-secondary">Nein, behalten</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Bestätigungsmodal für Löschung von regelmäßigen Schickzeiten (neue Child-basierte Verwaltung) --}}
@if(session('type') == 'confirm_delete_schickzeit')
    <div class="modal fade show" id="confirmDeleteSchickzeitModal" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bestätigung erforderlich</h5>
                </div>
                <div class="modal-body">
                    <p>{{session('Meldung')}}</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="{{route('schickzeiten.destroy', ['schickzeit' => session('confirm_delete_schickzeit_data.schickzeit_id')])}}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="delete_daily_times" value="yes">
                        <button type="submit" class="btn btn-danger">Ja, auch löschen</button>
                    </form>
                    <form method="POST" action="{{route('schickzeiten.destroy', ['schickzeit' => session('confirm_delete_schickzeit_data.schickzeit_id')])}}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="delete_daily_times" value="no">
                        <button type="submit" class="btn btn-secondary">Nein, behalten</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Bestätigungsmodal für Änderung von regelmäßigen Schickzeiten (Verwaltungsseite) --}}
@if(session('type') == 'confirm_verwaltung')
    <div class="modal fade show" id="confirmVerwaltungModal" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bestätigung erforderlich</h5>
                </div>
                <div class="modal-body">
                    <p>{{session('Meldung')}}</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="{{url('verwaltung/schickzeiten/'.session('confirm_verwaltung_data.parent'))}}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="child" value="{{session('confirm_verwaltung_data.child')}}">
                        <input type="hidden" name="weekday" value="{{session('confirm_verwaltung_data.weekday')}}">
                        <input type="hidden" name="type" value="{{session('confirm_verwaltung_data.type')}}">
                        <input type="hidden" name="time" value="{{session('confirm_verwaltung_data.time')}}">
                        <input type="hidden" name="time_spaet" value="{{session('confirm_verwaltung_data.time_spaet')}}">
                        <input type="hidden" name="update_daily_times" value="yes">
                        <button type="submit" class="btn btn-primary">Ja, anpassen</button>
                    </form>
                    <form method="POST" action="{{url('verwaltung/schickzeiten/'.session('confirm_verwaltung_data.parent'))}}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="child" value="{{session('confirm_verwaltung_data.child')}}">
                        <input type="hidden" name="weekday" value="{{session('confirm_verwaltung_data.weekday')}}">
                        <input type="hidden" name="type" value="{{session('confirm_verwaltung_data.type')}}">
                        <input type="hidden" name="time" value="{{session('confirm_verwaltung_data.time')}}">
                        <input type="hidden" name="time_spaet" value="{{session('confirm_verwaltung_data.time_spaet')}}">
                        <input type="hidden" name="update_daily_times" value="delete">
                        <button type="submit" class="btn btn-danger">Ja, löschen</button>
                    </form>
                    <form method="POST" action="{{url('verwaltung/schickzeiten/'.session('confirm_verwaltung_data.parent'))}}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="child" value="{{session('confirm_verwaltung_data.child')}}">
                        <input type="hidden" name="weekday" value="{{session('confirm_verwaltung_data.weekday')}}">
                        <input type="hidden" name="type" value="{{session('confirm_verwaltung_data.type')}}">
                        <input type="hidden" name="time" value="{{session('confirm_verwaltung_data.time')}}">
                        <input type="hidden" name="time_spaet" value="{{session('confirm_verwaltung_data.time_spaet')}}">
                        <input type="hidden" name="update_daily_times" value="no">
                        <button type="submit" class="btn btn-secondary">Nein, unverändert lassen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Bestätigungsmodal für Löschung von regelmäßigen Schickzeiten (Verwaltungsseite) --}}
@if(session('type') == 'confirm_delete_verwaltung')
    <div class="modal fade show" id="confirmDeleteVerwaltungModal" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bestätigung erforderlich</h5>
                </div>
                <div class="modal-body">
                    <p>{{session('Meldung')}}</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="{{url('verwaltung/schickzeiten/'.session('confirm_delete_verwaltung_data.day').'/'.session('confirm_delete_verwaltung_data.child').'/'.session('confirm_delete_verwaltung_data.parent'))}}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="delete_daily_times" value="yes">
                        <button type="submit" class="btn btn-danger">Ja, auch löschen</button>
                    </form>
                    <form method="POST" action="{{url('verwaltung/schickzeiten/'.session('confirm_delete_verwaltung_data.day').'/'.session('confirm_delete_verwaltung_data.child').'/'.session('confirm_delete_verwaltung_data.parent'))}}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="delete_daily_times" value="no">
                        <button type="submit" class="btn btn-secondary">Nein, behalten</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

