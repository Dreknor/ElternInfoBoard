@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <a href="{{url('reinigung')}}" class="btn btn-primary">zurück</a>
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            Automatisch Reinigungsplan erstellen
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="bg-danger text-white p-2">
                            Achtung! Die automatische Reinigungserstellung ist eine experimentelle Funktion. Es wird
                            empfohlen, die erstellten Pläne zu überprüfen.
                        <p>
                            Die automatische Reinigungserstellung erstellt für den gewählten Zeitraum einen
                            Reinigungsplan.
                            Dabei wird für jede Woche ein Eintrag erstellt, der die Reinigungsaufgabe und die Familie
                            enthält.
                        </p>
                        <p>
                            Wähle den Zeitraum, für den der Reinigungsplan erstellt werden soll. Dann lege fest, welche
                            Aufgaben verteilt werden sollen und ob bestimmete Gruppen ausgeschlossen werden sollen. Die
                            Auswahl mehrerer Aufgaben und ausgeschlossener Gruppen ist möglich.
                        </p>
                    </div>
                    <div class="card-body">
                        <form id="createForm" class="form-horizontal"
                              action="{{url('reinigung/'.$bereich->first()->bereich.'/auto')}}" method="post">
                            @csrf
                            <div class="form-row">
                                <label class="label text-danger">
                                    Startdatum*:
                                </label>
                                <input class="form-control" name="start" type="date" value="{{now()->format('Y-m-d')}}">
                            </div>
                            <div class="form-row">
                                <label class="label text-danger">
                                    Enddatum*:
                                </label>
                                <input class="form-control" name="end" type="date"
                                       value="{{\Carbon\Carbon::createFromFormat('d.m', '01.08')->addYear()->format('Y-m-d')}}">
                            </div>
                            <div class="form-row mt-2">
                                <label class="label text-danger">
                                    Aufgaben*:
                                </label>
                                <select name="aufgaben[]" class="custom-select" multiple>
                                    @foreach($aufgaben as $task)
                                        <option value="{{$task->id}}">
                                            {{$task->task}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row mt-3">
                                <div class="col-12">
                                <label class="label">
                                    Ausgeschlossene Gruppen:
                                </label>
                                <select name="exclude[]" class="custom-select" multiple size="{{$bereich->count()+1}}">
                                    <option value="" selected>
                                        keine
                                    </option>
                                    @foreach($bereich as $group)
                                        <option value="{{$group->id}}">
                                            {{$group->name}} ({{$group->users->count()}})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            </div>


                        </form>
                    </div>
                    <div class="card-footer">
                        <button type="submit" form="createForm" class="btn btn-success btn-block">
                            generieren
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
