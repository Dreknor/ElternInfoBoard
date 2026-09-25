<div class="tab-pane" id="pflichtstunden" role="tabpanel" aria-labelledby="pflichtstunden-tab">
    <form action="{{url('settings/pflichtstunden')}}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="text" class="form-control" name="pflichtstunden_start"
                           value="{{$pflichtstundenSettings->pflichtstunden_start ?? '08-01'}}">
                    Startdatum der Pflichtstunden (Format: Monat-Tag)
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier wird das Startdatum der Pflichtstunden im Format Monat-Tag (z.B. 08-01 für den 1. August) festgelegt. Dieses Datum markiert den Beginn des Zeitraums, in dem die Pflichtstunden gezählt werden.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="text" class="form-control" name="pflichtstunden_ende"
                           value="{{$pflichtstundenSettings->pflichtstunden_ende ?? '07-31'}}">
                    Enddatum der Pflichtstunden (Format: Monat-Tag)
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier wird das Enddatum der Pflichtstunden im Format Monat-Tag (z.B. 07-31 für den 31. Juli) festgelegt. Dieses Datum markiert das Ende des Zeitraums, in dem die Pflichtstunden gezählt werden.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="number" class="form-control" name="pflichtstunden_anzahl"
                           value="{{$pflichtstundenSettings->pflichtstunden_anzahl ?? 0}}">
                    Anzahl der Pflichtstunden pro Jahr
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier wird die Anzahl der Pflichtstunden festgelegt, die pro Jahr erfüllt werden müssen. Diese Zahl gibt an, wie viele Stunden insgesamt im definierten Zeitraum (zwischen Start- und Enddatum) abgeleistet werden müssen.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="number" class="form-control" name="pflichtstunden_betrag" step="0.01"
                           value="{{$pflichtstundenSettings->pflichtstunden_betrag ?? 0}}">
                    Betrag je Pflichtstunden
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Der Betrag, der je nicht geleisteter Pflichtstunde berechnet wird. Dieser Wert wird verwendet, um die finanziellen Konsequenzen für nicht erfüllte Pflichtstunden zu bestimmen.
                </div>
            </div>
        </div>

        {{-- Berechnungsgrundlage (Konzept kind-zentriertes Familienmodell §6.2, E1/E8) --}}
        <div class="mt-3 p-3 border rounded bg-light" x-data="pflichtstundenBasis()">
            <h6 class="font-weight-bold">Berechnungsgrundlage</h6>
            <p class="small text-muted mb-2">
                Legt fest, wie das Soll berechnet wird. Änderungen wirken <strong>ab sofort für den laufenden Zeitraum</strong>
                (keine anteilige Aufteilung); bereits erfasste Stunden bleiben unverändert.
                @if($pflichtstundenSettings->pflichtstunden_basis_changed_at)
                    <br>Letzte Umstellung: {{ \Carbon\Carbon::parse($pflichtstundenSettings->pflichtstunden_basis_changed_at)->format('d.m.Y H:i') }}
                    @isset($pflichtstundenBasisChangedBy) durch {{ $pflichtstundenBasisChangedBy?->name }} @endisset
                @endif
            </p>

            <div class="form-row">
                <div class="col-md-6">
                    <label class="d-block font-weight-bold small">Soll-Stunden gelten …</label>
                    <label class="d-block"><input type="radio" name="pflichtstunden_basis" value="family" x-model="basis"
                        @checked(($pflichtstundenSettings->pflichtstunden_basis ?? 'family') === 'family')> pro Familie</label>
                    <label class="d-block"><input type="radio" name="pflichtstunden_basis" value="child" x-model="basis"
                        @checked(($pflichtstundenSettings->pflichtstunden_basis ?? 'family') === 'child')> pro Kind</label>
                </div>
                <div class="col-md-6">
                    <label class="d-block font-weight-bold small">Kinder mehrerer Familien (z. B. getrennte Eltern)</label>
                    <label class="d-block"><input type="radio" name="pflichtstunden_geteilte_kinder" value="separate" x-model="geteilt"
                        @checked(($pflichtstundenSettings->pflichtstunden_geteilte_kinder ?? 'separate') === 'separate')>
                        jede Familie trägt das volle Soll</label>
                    <label class="d-block"><input type="radio" name="pflichtstunden_geteilte_kinder" value="combined" x-model="geteilt"
                        @checked(($pflichtstundenSettings->pflichtstunden_geteilte_kinder ?? 'separate') === 'combined')>
                        Familien mit gemeinsamem Kind leisten gemeinsam (Stunden werden addiert)</label>
                    <label class="d-block"><input type="radio" name="pflichtstunden_geteilte_kinder" value="split" x-model="geteilt"
                        @checked(($pflichtstundenSettings->pflichtstunden_geteilte_kinder ?? 'separate') === 'split')>
                        Soll wird anteilig aufgeteilt (bei zwei Familien je die Hälfte)</label>
                </div>
            </div>

            <div class="form-row mt-2" x-show="basis === 'child'">
                <div class="col-md-6">
                    <label class="w-100 small">Höchstens so viele Kinder zählen (leer = unbegrenzt)
                        <input type="number" min="1" class="form-control" name="pflichtstunden_max_kinder" x-model="maxKinder"
                               value="{{ $pflichtstundenSettings->pflichtstunden_max_kinder }}">
                    </label>
                </div>
            </div>

            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label class="w-100 small">Nur Kinder dieser Klassen/Gruppen zählen (leer = alle aktiven Kinder)
                        <select name="pflichtstunden_kinder_gruppen[]" class="form-control" multiple size="5" x-ref="gruppen">
                            @foreach($allGroups ?? [] as $gruppe)
                                <option value="{{ $gruppe->id }}" @selected(in_array($gruppe->id, $pflichtstundenSettings->pflichtstunden_kinder_gruppen ?? []))>{{ $gruppe->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="mt-2">
                <button type="button" class="btn btn-outline-primary btn-sm" @click="loadPreview()">
                    <i class="fas fa-calculator"></i> Vorschau berechnen
                </button>
                <template x-if="preview">
                    <table class="table table-sm mt-2 bg-white">
                        <thead><tr><th></th><th>Einheiten</th><th>Soll (h)</th><th>Ist (h)</th><th>Offen (h)</th><th>Beitrag (€)</th></tr></thead>
                        <tbody>
                            <tr><td>aktuell</td><td x-text="preview.aktuell.einheiten"></td><td x-text="preview.aktuell.soll_stunden"></td><td x-text="preview.aktuell.ist_stunden"></td><td x-text="preview.aktuell.offen_stunden"></td><td x-text="preview.aktuell.beitrag"></td></tr>
                            <tr class="font-weight-bold"><td>neu</td><td x-text="preview.neu.einheiten"></td><td x-text="preview.neu.soll_stunden"></td><td x-text="preview.neu.ist_stunden"></td><td x-text="preview.neu.offen_stunden"></td><td x-text="preview.neu.beitrag"></td></tr>
                        </tbody>
                    </table>
                </template>
            </div>
        </div>

        <script>
            function pflichtstundenBasis() {
                const initial = {
                    basis: @json($pflichtstundenSettings->pflichtstunden_basis ?? 'family'),
                    geteilt: @json($pflichtstundenSettings->pflichtstunden_geteilte_kinder ?? 'separate'),
                    maxKinder: @json($pflichtstundenSettings->pflichtstunden_max_kinder),
                };
                return {
                    ...initial,
                    preview: null,
                    init() {
                        this.$el.closest('form').addEventListener('submit', (event) => {
                            const changed = this.basis !== initial.basis || this.geteilt !== initial.geteilt
                                || String(this.maxKinder ?? '') !== String(initial.maxKinder ?? '');
                            if (changed && !confirm('Die Berechnungsgrundlage wird geändert. Die Änderung wirkt sofort für den laufenden Zeitraum. Fortfahren?')) {
                                event.preventDefault();
                            }
                        });
                    },
                    async loadPreview() {
                        const gruppen = Array.from(this.$refs.gruppen.selectedOptions).map(o => o.value);
                        const response = await fetch(@json(route('settings.pflichtstunden.preview')), {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                            body: JSON.stringify({
                                pflichtstunden_basis: this.basis,
                                pflichtstunden_geteilte_kinder: this.geteilt,
                                pflichtstunden_max_kinder: this.maxKinder || null,
                                pflichtstunden_kinder_gruppen: gruppen,
                            }),
                        });
                        this.preview = response.ok ? await response.json() : null;
                    },
                };
            }
        </script>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-12">
                <label class="label-control w-100">
                    Informationstext für Eltern
                    <textarea class="form-control" name="pflichtstunden_text" rows="6">{{$pflichtstundenSettings->pflichtstunden_text ?? 'Bitte tragen Sie hier Ihre geleisteten Pflichtstunden ein. Pro Familie sind 20 Stunden jährlich zu leisten. Vielen Dank für Ihre Unterstützung!'}}</textarea>
                </label>
            </div>
            <div class="col-md-12 mt-2">
                <div class="small">
                    Dieser Text wird den Eltern oben auf der Pflichtstunden-Seite angezeigt. Hier können Sie Informationen, Hinweise und Anweisungen zu den Pflichtstunden kommunizieren.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-12">
                <label class="label-control w-100">
                    <strong>Bereiche für Pflichtstunden (einer pro Zeile)</strong>
                    <textarea class="form-control no-tinymce" name="pflichtstunden_bereiche" rows="8" placeholder="z.B.&#10;Gartenarbeit&#10;Renovierung&#10;Reinigung&#10;Feste/Events&#10;Administrative Aufgaben">{{ is_array($pflichtstundenSettings->pflichtstunden_bereiche ?? []) ? implode("\n", $pflichtstundenSettings->pflichtstunden_bereiche) : '' }}</textarea>
                </label>
            </div>
            <div class="col-md-12 mt-2">
                <div class="small">
                    Definieren Sie hier die verschiedenen Bereiche, in denen Pflichtstunden geleistet werden können. Geben Sie jeden Bereich in einer neuen Zeile ein. Diese Bereiche können dann bei der Erfassung von Pflichtstunden ausgewählt werden und ermöglichen eine gezielte Filterung in der Verwaltungsansicht.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="listen_autocreate" value="1"
                           @if($pflichtstundenSettings->listen_autocreate ?? false) checked @endif>
                    Listen automatisch erstellen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn aktiviert, werden die Pflichtstunden-Listen automatisch für alle Familien erstellt. Andernfalls müssen die Listen manuell angelegt werden.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <h5 class="label-control w-100">
                    <strong>🎮 Gamification-Einstellungen</strong>
                </h5>
            </div>
        </div>



        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="gamification_show_progress" value="1"
                           @if($pflichtstundenSettings->gamification_show_progress) checked @endif>
                    Fortschritts-Card anzeigen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zeigt einen animierten Fortschrittsbalken mit deinem aktuellen Fortschritt und Achievement-Badges an.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="gamification_show_ranking" value="1"
                           @if($pflichtstundenSettings->gamification_show_ranking) checked @endif>
                    Ranking-Card anzeigen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zeigt deine aktuelle Platzierung unter allen Eltern an mit zusätzlichen Rang-Abzeichen (z.B. 🥇 Platz 1, 🏆 Top 3).
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="gamification_show_comparison" value="1"
                           @if($pflichtstundenSettings->gamification_show_comparison) checked @endif>
                    Vergleichs-Card anzeigen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zeigt einen Vergleich deines Fortschritts mit dem Durchschnitt aller Eltern an und motiviert dich, das Ziel zu erreichen.
                </div>
            </div>
        </div>

        <hr>

        <div class="form-row mt-3">
            <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-save"></i> Einstellungen speichern
            </button>
        </div>

    </form>
</div>
