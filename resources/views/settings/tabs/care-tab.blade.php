<div class="tab-pane" id="care" role="tabpanel" aria-labelledby="care-tab">
    <form action="{{url('settings/care')}}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    ausführliche Ansicht der Anwesenheit
                    <input type="checkbox" class="form-control" name="view_detailed_care"
                           value="1" @if($careSettings->view_detailed_care) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn diese Option aktiviert ist, wird die Anwesenheit in der Betreuungsansicht detailliert angezeigt. Dies umfasst das Bild des Kindes und auch die nächste Schickzeit.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Nicht anwesende Kinder ausblenden
                    <input type="checkbox" class="form-control" name="hide_childs_when_absent"
                           value="1" @if($careSettings->hide_childs_when_absent) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn ein Kind nicht anwesend ist, wird es in der Betreuungsansicht ausgeblendet. So können nur die Kinder angezeigt werden, die tatsächlich anwesend sind.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Gruppen und Klassen ausblenden wenn keine Kinder vorhanden
                    <input type="checkbox" class="form-control" name="hide_groups_when_empty"
                           value="1" @if($careSettings->hide_groups_when_empty) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn keine Kinder in einer Gruppe oder Klasse vorhanden sind, wird die Gruppe oder Klasse in der Betreuungsansicht ausgeblendet. So können nur die Gruppen und Klassen angezeigt werden, in denen tatsächlich Kinder anwesend sind.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Info, wenn keine Kinder anwesend sind
                    <input type="checkbox" class="form-control" name="show_message_on_empty_group"
                           value="1" @if($careSettings->show_message_on_empty_group) checked @endif>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn leere Gruppen oder Klassen nicht ausgeblendet werden, wird eine Nachricht angezeigt, dass keine Kinder anwesend sind.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Gruppen auswählen
                    <select name="groups_list[]" class="form-control" multiple>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @if(in_array($group->id, $careSettings->groups_list)) selected @endif>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wählen Sie die Gruppen aus, die angezeigt werden sollen.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Klassen auswählen
                    <select name="class_list[]" class="custom-select" multiple >
                        @foreach($groups as $class)
                            <option value="{{ $class->id }}" @if(in_array($class->id, $careSettings->class_list)) selected @endif>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wählen Sie die Klassen aus, die angezeigt werden sollen.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Bundesland (für Ferienabfrage)
                    <select name="bundesland" class="form-control">
                        @foreach(\App\Services\HolidayService::bundeslaender() as $code => $name)
                            <option value="{{ $code }}" @if($careSettings->bundesland === $code) selected @endif>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wählen Sie das Bundesland aus, anhand dessen die Schulferien abgerufen werden. Dies wird für den automatischen täglichen Check-In und die Anzeige von Arbeitsgemeinschaften in der Anwesenheitsliste berücksichtigt.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Ende der Betreuungszeit
                    <input type="time" class="form-control" name="end_time"
                           value="{{$careSettings->end_time}}" >
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Geben Sie die Uhrzeit an, zu der die Betreuungszeit endet. Wird ein Kind nach dieser Uhrzeit abgemeldet, wird eine Nachricht an den unten angegebenen Mitarbeiter gesendet.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    Mitarbeiter auswählen
                    <select name="info_to" class="form-control">
                        <option value="">Bitte auswählen...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @if($user->id == $careSettings->info_to) selected @endif>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wählen Sie den Mitarbeiter aus, der die Nachricht erhalten soll.
                </div>
            </div>
        </div>

        <div class="form-row">
            <button type="submit" class="btn btn-success btn-block">
                Save Settings
            </button>
        </div>
    </form>
</div>
