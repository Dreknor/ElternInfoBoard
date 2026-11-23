<?php

namespace App\Console\Commands;

use App\Model\listen_termine;
use App\Model\Pflichtstunde;
use App\Settings\PflichtstundenSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Legt Pflichtstunden für gebuchte Listentermine nach, bei denen die automatische
 * Erstellung (ListenTermineObserver) nicht ausgelöst wurde.
 *
 * Hintergrund: ListenService::reserveTermin hat seit 25.09.2026 (App) bzw. 06.10.2026 (Web)
 * per Query-Builder gebucht; dabei feuern keine Model-Events.
 *
 * Termine, zu denen bereits eine Pflichtstunde existiert – auch abgelehnt oder gelöscht
 * (Soft Delete) – werden nicht angefasst.
 */
class PflichtstundenListenNacherfassen extends Command
{
    protected $signature = 'pflichtstunden:listen-nacherfassen
                            {--seit=2026-09-25 : Nur Termine, deren Buchung (updated_at) ab diesem Datum erfolgte (Y-m-d)}
                            {--liste=* : Nur diese Listen-ID(s)}
                            {--force : Auch ausführen, wenn „listen_autocreate“ deaktiviert ist}
                            {--dry-run : Nur anzeigen, nichts anlegen}';

    protected $description = 'Legt fehlende automatische Pflichtstunden für gebuchte Listentermine nach';

    public function handle(PflichtstundenSetting $settings): int
    {
        if (! $settings->listen_autocreate && ! $this->option('force')) {
            $this->error('Die Einstellung „listen_autocreate“ ist deaktiviert. Abbruch (mit --force trotzdem ausführen).');

            return Command::FAILURE;
        }

        try {
            $seit = Carbon::createFromFormat('Y-m-d', (string) $this->option('seit'))->startOfDay();
        } catch (\Throwable $e) {
            $this->error('Ungültiges Datum für --seit (erwartet Y-m-d).');

            return Command::FAILURE;
        }

        $vorhanden = Pflichtstunde::withoutGlobalScopes()->withTrashed()
            ->whereNotNull('listen_termin_id')
            ->select('listen_termin_id');

        $termine = listen_termine::query()
            ->with('liste')
            ->whereNotNull('reserviert_fuer')
            ->where('updated_at', '>=', $seit)
            ->whereHas('liste', function ($q) {
                $q->where('type', 'termin')->where('creates_pflichtstunden', true);
                if ($ids = array_filter((array) $this->option('liste'))) {
                    $q->whereIn('id', $ids);
                }
            })
            ->whereNotIn('id', $vorhanden)
            ->orderBy('termin')
            ->get();

        if ($termine->isEmpty()) {
            $this->info('Keine fehlenden Pflichtstunden gefunden.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Termin-ID', 'Liste', 'Termin', 'Dauer', 'User-ID', 'Name'],
            $termine->map(fn (listen_termine $t) => [
                $t->id,
                $t->liste->listenname,
                $t->termin?->format('d.m.Y H:i'),
                (int) $t->duration.' min',
                $t->reserviert_fuer,
                $t->eingetragenePerson?->name ?? '–',
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->warn("Dry-run: {$termine->count()} Pflichtstunde(n) würden angelegt.");

            return Command::SUCCESS;
        }

        $angelegt = 0;
        foreach ($termine as $t) {
            // Gleiche Felder wie ListenTermineObserver::createPflichtstunde
            Pflichtstunde::create([
                'user_id' => $t->reserviert_fuer,
                'listen_termin_id' => $t->id,
                'start' => $t->termin,
                'end' => $t->termin->copy()->addMinutes((int) $t->duration),
                'description' => 'Automatisch erstellt: '.$t->liste->listenname.
                                ($t->comment ? ' - '.$t->comment : ''),
                'approved' => false,
            ]);
            $angelegt++;
        }

        $this->info("{$angelegt} Pflichtstunde(n) nacherfasst (nicht genehmigt).");

        return Command::SUCCESS;
    }
}
