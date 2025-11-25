<?php

namespace App\Observers;

use App\Model\Liste;
use App\Model\Pflichtstunde;
use App\Settings\PflichtstundenSetting;
use Illuminate\Support\Facades\Log;

class ListeObserver
{
    /**
     * Handle the Liste "created" event.
     */
    public function created(Liste $liste): void
    {
        //
    }

    /**
     * Handle the Liste "updated" event.
     */
    public function updated(Liste $liste): void
    {
        // Prüfen, ob creates_pflichtstunden geändert wurde
        if (!$liste->isDirty('creates_pflichtstunden')) {
            return;
        }

        // Nur für Terminlisten
        if ($liste->type !== 'termin') {
            return;
        }

        $settings = app(PflichtstundenSetting::class);

        // Nur wenn listen_autocreate aktiviert ist
        if (!$settings->listen_autocreate) {
            return;
        }

        // Alle gebuchten Termine dieser Liste holen
        $termine = $liste->termine()->whereNotNull('reserviert_fuer')->get();

        if ($termine->isEmpty()) {
            return;
        }

        if ($liste->creates_pflichtstunden) {
            // Einstellung wurde aktiviert - Pflichtstunden für bestehende Termine erstellen
            $this->createPflichtstundenForTermine($termine);
        } else {
            // Einstellung wurde deaktiviert - bestehende Pflichtstunden ablehnen
            $this->rejectPflichtstundenForTermine($termine);
        }
    }

    /**
     * Handle the Liste "deleted" event.
     */
    public function deleted(Liste $liste): void
    {
        //
    }

    /**
     * Handle the Liste "restored" event.
     */
    public function restored(Liste $liste): void
    {
        //
    }

    /**
     * Handle the Liste "force deleted" event.
     */
    public function forceDeleted(Liste $liste): void
    {
        //
    }

    /**
     * Erstellt Pflichtstunden für bestehende Termine
     */
    private function createPflichtstundenForTermine($termine): void
    {
        $created = 0;
        $reactivated = 0;

        foreach ($termine as $termin) {
            try {
                // Prüfen, ob bereits eine Pflichtstunde existiert
                $existingPflichtstunde = Pflichtstunde::where('listen_termin_id', $termin->id)->first();

                if ($existingPflichtstunde) {
                    // Falls abgelehnt, reaktivieren
                    if ($existingPflichtstunde->rejected) {
                        $existingPflichtstunde->update([
                            'rejected' => false,
                            'rejected_at' => null,
                            'rejected_by' => null,
                            'rejection_reason' => null,
                        ]);
                        $reactivated++;
                    }
                    continue;
                }

                // Neue Pflichtstunde erstellen
                $start = $termin->termin;
                $end = $termin->termin->copy()->addMinutes($termin->duration);

                Pflichtstunde::create([
                    'user_id' => $termin->reserviert_fuer,
                    'listen_termin_id' => $termin->id,
                    'start' => $start,
                    'end' => $end,
                    'description' => 'Automatisch erstellt: ' . $termin->liste->listenname .
                                    ($termin->comment ? ' - ' . $termin->comment : ''),
                    'approved' => false,
                ]);
                $created++;

            } catch (\Exception $e) {
                Log::error('Fehler beim Erstellen der Pflichtstunde für bestehenden Termin', [
                    'listen_termin_id' => $termin->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($created > 0 || $reactivated > 0) {
            Log::info('Pflichtstunden für bestehende Termine verarbeitet', [
                'liste_id' => $termine->first()->liste->id,
                'created' => $created,
                'reactivated' => $reactivated
            ]);
        }
    }

    /**
     * Lehnt Pflichtstunden für bestehende Termine ab
     */
    private function rejectPflichtstundenForTermine($termine): void
    {
        $rejected = 0;

        foreach ($termine as $termin) {
            try {
                $pflichtstunde = Pflichtstunde::where('listen_termin_id', $termin->id)->first();

                if (!$pflichtstunde) {
                    continue;
                }

                // Nur ablehnen, wenn noch nicht genehmigt
                if (!$pflichtstunde->approved && !$pflichtstunde->rejected) {
                    $pflichtstunde->update([
                        'rejected' => true,
                        'rejected_at' => now(),
                        'rejected_by' => auth()->id(),
                        'rejection_reason' => 'Automatische Pflichtstunden-Erfassung wurde für diese Liste deaktiviert',
                    ]);
                    $rejected++;
                }

            } catch (\Exception $e) {
                Log::error('Fehler beim Ablehnen der Pflichtstunde für bestehenden Termin', [
                    'listen_termin_id' => $termin->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($rejected > 0) {
            Log::info('Pflichtstunden für bestehende Termine abgelehnt', [
                'liste_id' => $termine->first()->liste->id,
                'rejected' => $rejected
            ]);
        }
    }
}
