<?php

namespace App\Services\App;

use App\Mail\Krankmeldung as KrankmeldungMail;
use App\Model\ActiveDisease;
use App\Model\Child;
use App\Model\Disease;
use App\Model\Krankmeldungen;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Krankmeldung anlegen – gemeinsam für die alte und die neue App-API (B-40).
 * Das Kind muss zur Familie gehören; die Meldung wird mit Kind und Krankheit verknüpft.
 */
class KrankmeldungService
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function create(
        User $user,
        ?Child $child,
        ?string $name,
        Carbon $start,
        Carbon $ende,
        string $kommentar,
        ?int $diseaseId = null,
        array $files = [],
    ): Krankmeldungen {
        $disease = $diseaseId ? Disease::find($diseaseId) : null;

        $krankmeldung = DB::transaction(function () use ($user, $child, $name, $start, $ende, $kommentar, $disease) {
            $krankmeldung = Krankmeldungen::create([
                'name' => $child ? $child->first_name.' '.$child->last_name : (string) $name,
                'kommentar' => $kommentar,
                'start' => $start->copy()->startOfDay(),
                'ende' => $ende->copy()->startOfDay(),
                'users_id' => $user->id,
                'child_id' => $child?->id,
                'disease_id' => $disease?->id,
            ]);

            if ($disease) {
                ActiveDisease::insert([
                    'user_id' => $user->id,
                    'disease_id' => $disease->id,
                    'start' => $start->toDateString(),
                    'end' => $start->copy()->addDays((int) $disease->aushang_dauer)->toDateString(),
                    'comment' => $kommentar,
                    'active' => false,
                ]);
                Cache::forget('active_diseases');
            }

            return $krankmeldung;
        });

        foreach ($files as $file) {
            $krankmeldung->addMedia($file)->toMediaCollection('files');
        }

        Mail::to(config('mail.from.address'))
            ->cc($user->email)
            ->queue(new KrankmeldungMail(
                $user->email,
                $user->name,
                $this->mailName($krankmeldung, $child, $user),
                $start->format('d.m.Y'),
                $ende->format('d.m.Y'),
                $kommentar,
                $disease?->name,
                $krankmeldung->getMedia('files')->all(),
            ));

        return $krankmeldung;
    }

    /** Hinweise zur Wiederzulassung bei meldepflichtigen Erkrankungen (wie im Web). */
    public function readmissionHint(?int $diseaseId): ?string
    {
        $disease = $diseaseId ? Disease::find($diseaseId) : null;
        if (! $disease) {
            return null;
        }

        return trim('Wiederzulassung durch: '.$disease->wiederzulassung_durch."\nWiederzulassung wann: ".$disease->wiederzulassung_wann);
    }

    private function mailName(Krankmeldungen $krankmeldung, ?Child $child, User $user): string
    {
        if ($child) {
            $parts = array_unique(array_filter([$child->group?->name, $child->class?->name]));

            return $krankmeldung->name.($parts ? ' ('.implode(' - ', $parts).')' : '');
        }

        return $krankmeldung->name.' ('.$user->groups->pluck('name')->implode(', ').')';
    }

    /** Akzeptiert `Y-m-d` (neu) und `d.m.Y` (alte App-Versionen). */
    public static function parseDate(string $value): Carbon
    {
        return str_contains($value, '.')
            ? Carbon::createFromFormat('d.m.Y', $value)->startOfDay()
            : Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
    }
}
