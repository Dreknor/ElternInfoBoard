<?php

namespace App\Observers;

use App\Model\listen_termine;
use App\Model\Pflichtstunde;
use App\Settings\PflichtstundenSetting;
use Illuminate\Support\Facades\Log;

class ListenTermineObserver
{
    /**
     * Handle the listen_termine "created" event.
     */
    public function created(listen_termine $listen_termine): void
    {
        //
    }

    /**
     * Handle the listen_termine "updated" event.
     */
    public function updated(listen_termine $listenTermin): void
    {
        $settings = app(PflichtstundenSetting::class);

        Log::debug('ListenTermineObserver::updated aufgerufen', [
            'listen_termin_id' => $listenTermin->id,
            'listen_autocreate' => $settings->listen_autocreate ?? 'not set',
            'dirty_attributes' => $listenTermin->getDirty(),
            'original_reserviert_fuer' => $listenTermin->getOriginal('reserviert_fuer'),
            'new_reserviert_fuer' => $listenTermin->reserviert_fuer,
        ]);

        // Nur aktiv, wenn listen_autocreate gesetzt ist
        if (! $settings->listen_autocreate) {
            Log::debug('listen_autocreate ist nicht aktiv');

            return;
        }

        // Prüfen, ob die Liste Pflichtstunden erstellen soll
        if (! $listenTermin->liste || ! $listenTermin->liste->creates_pflichtstunden) {
            Log::debug('Liste erstellt keine Pflichtstunden', [
                'liste_id' => $listenTermin->liste?->id,
                'creates_pflichtstunden' => $listenTermin->liste?->creates_pflichtstunden ?? 'null',
            ]);

            return;
        }

        // Fall 1: Termin wurde neu reserviert (reserviert_fuer wurde gesetzt)
        if ($listenTermin->isDirty('reserviert_fuer') && $listenTermin->reserviert_fuer !== null) {
            Log::info('Termin wurde reserviert, erstelle Pflichtstunde', [
                'listen_termin_id' => $listenTermin->id,
                'reserviert_fuer' => $listenTermin->reserviert_fuer,
            ]);
            $this->createPflichtstunde($listenTermin);
        }

        // Fall 2: Termin wurde abgesagt (reserviert_fuer wurde auf null gesetzt)
        if ($listenTermin->isDirty('reserviert_fuer') && $listenTermin->reserviert_fuer === null && $listenTermin->getOriginal('reserviert_fuer') !== null) {
            Log::info('Termin wurde abgesagt, lehne Pflichtstunde ab', [
                'listen_termin_id' => $listenTermin->id,
                'original_reserviert_fuer' => $listenTermin->getOriginal('reserviert_fuer'),
            ]);
            $this->rejectPflichtstunde($listenTermin);
        }
    }

    /**
     * Handle the listen_termine "deleted" event.
     */
    public function deleted(listen_termine $listenTermin): void
    {
        $settings = app(PflichtstundenSetting::class);

        // Nur aktiv, wenn listen_autocreate gesetzt ist
        if (! $settings->listen_autocreate) {
            return;
        }

        // Wenn der Termin gelöscht wird und eine Reservierung bestand, Pflichtstunde ablehnen
        if ($listenTermin->reserviert_fuer !== null) {
            $this->rejectPflichtstunde($listenTermin, 'Termin wurde gelöscht');
        }
    }

    /**
     * Handle the listen_termine "restored" event.
     */
    public function restored(listen_termine $listen_termine): void
    {
        //
    }

    /**
     * Handle the listen_termine "force deleted" event.
     */
    public function forceDeleted(listen_termine $listen_termine): void
    {
        //
    }

    /**
     * Erstellt einen Pflichtstunden-Eintrag für den Termin
     */
    private function createPflichtstunde(listen_termine $listenTermin): void
    {
        try {
            // Prüfen, ob bereits eine Pflichtstunde für diesen Termin existiert
            $existingPflichtstunde = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
                ->where('listen_termin_id', $listenTermin->id)
                ->first();

            if ($existingPflichtstunde) {
                // Falls bereits vorhanden, aktualisieren falls abgelehnt
                if ($existingPflichtstunde->rejected) {
                    $existingPflichtstunde->update([
                        'rejected' => false,
                        'rejected_at' => null,
                        'rejected_by' => null,
                        'rejection_reason' => null,
                    ]);
                }

                return;
            }

            // Berechne Ende basierend auf Dauer
            $start = $listenTermin->termin;
            $end = $listenTermin->termin->copy()->addMinutes($listenTermin->duration);

            Pflichtstunde::create([
                'user_id' => $listenTermin->reserviert_fuer,
                'listen_termin_id' => $listenTermin->id,
                'start' => $start,
                'end' => $end,
                'description' => 'Automatisch erstellt: '.$listenTermin->liste->listenname.
                                ($listenTermin->comment ? ' - '.$listenTermin->comment : ''),
                'approved' => false,
            ]);

            Log::info('Pflichtstunde automatisch erstellt', [
                'listen_termin_id' => $listenTermin->id,
                'user_id' => $listenTermin->reserviert_fuer,
            ]);

        } catch (\Exception $e) {
            Log::error('Fehler beim Erstellen der Pflichtstunde', [
                'listen_termin_id' => $listenTermin->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lehnt die Pflichtstunde für den abgesagten Termin ab
     */
    private function rejectPflichtstunde(listen_termine $listenTermin, ?string $customReason = null): void
    {
        try {
            $pflichtstunde = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
                ->where('listen_termin_id', $listenTermin->id)
                ->first();

            if (! $pflichtstunde) {
                Log::warning('Keine Pflichtstunde zum Ablehnen gefunden', [
                    'listen_termin_id' => $listenTermin->id,
                ]);

                return;
            }

            // Nur ablehnen, wenn noch nicht genehmigt
            if (! $pflichtstunde->approved) {
                $reason = $customReason ?? 'Termin wurde abgesagt';

                $pflichtstunde->update([
                    'rejected' => true,
                    'rejected_at' => now(),
                    'rejected_by' => auth()->id(),
                    'rejection_reason' => $reason,
                ]);

                Log::info('Pflichtstunde automatisch abgelehnt', [
                    'pflichtstunde_id' => $pflichtstunde->id,
                    'reason' => $reason,
                ]);
            } else {
                Log::info('Pflichtstunde bereits genehmigt, wird nicht abgelehnt', [
                    'pflichtstunde_id' => $pflichtstunde->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Fehler beim Ablehnen der Pflichtstunde', [
                'listen_termin_id' => $listenTermin->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
