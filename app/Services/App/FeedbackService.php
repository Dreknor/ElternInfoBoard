<?php

namespace App\Services\App;

use App\Mail\UserRueckmeldung as UserRueckmeldungMail;
use App\Model\AbfrageAntworten;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Rückmeldungen und Abfragen – gemeinsame Regeln für alte und neue App-API (B-20, B-21).
 * Fehler werden als HttpException mit deutscher Meldung geworfen.
 */
class FeedbackService
{
    /** Offen bis einschließlich des Frist-Tages (Web: `ende >= heute`). */
    public static function isOpen(Rueckmeldungen $rueckmeldung): bool
    {
        return ! $rueckmeldung->ende || $rueckmeldung->ende->copy()->endOfDay()->isFuture();
    }

    public function requireFeedback(User $user, Post $post, ?string $type = null): Rueckmeldungen
    {
        if ($user->cannot('view', $post)) {
            throw new HttpException(403, 'Keine Berechtigung für diesen Beitrag.');
        }
        $rueckmeldung = $post->rueckmeldung;
        if (! $rueckmeldung || ($type && $rueckmeldung->type !== $type)) {
            throw new HttpException(404, 'Für diesen Beitrag ist diese Rückmeldung nicht vorgesehen.');
        }
        if (! self::isOpen($rueckmeldung)) {
            throw new HttpException(410, 'Die Frist für Rückmeldungen ist abgelaufen.');
        }

        return $rueckmeldung;
    }

    /** Bisherige Antworten der Familie zu diesem Beitrag. */
    public function familyResponses(User $user, Post $post)
    {
        return UserRueckmeldungen::query()
            ->where('post_id', $post->id)
            ->whereIn('users_id', Family::userIds($user))
            ->with(['answers.option', 'user:id,name'])
            ->orderBy('created_at')
            ->get();
    }

    public function storeText(User $user, Post $post, string $text): UserRueckmeldungen
    {
        $rueckmeldung = $this->requireFeedback($user, $post);
        if (in_array($rueckmeldung->type, ['abfrage', 'bild', 'commentable', 'terminliste'], true)) {
            throw new HttpException(422, 'Für diesen Beitrag ist keine Text-Rückmeldung vorgesehen.');
        }

        $existing = $this->familyResponses($user, $post);
        if (! $rueckmeldung->multiple && $existing->isNotEmpty()) {
            throw new HttpException(409, 'Ihre Familie hat bereits geantwortet. Sie können die Antwort ändern.');
        }

        $response = UserRueckmeldungen::create([
            'post_id' => $post->id,
            'users_id' => $user->id,
            'text' => $text,
            'rueckmeldung_number' => $existing->count() + 1,
        ]);

        $this->mailRecipient($user, $rueckmeldung, $text, 'Rückmeldung zu '.$post->header);

        return $response;
    }

    public function updateText(User $user, UserRueckmeldungen $response, string $text): UserRueckmeldungen
    {
        if (! in_array((int) $response->users_id, Family::userIds($user), true)) {
            throw new HttpException(403, 'Sie sind nicht berechtigt, diese Rückmeldung zu ändern.');
        }
        $post = $response->nachricht;
        $rueckmeldung = $this->requireFeedback($user, $post);

        $response->update(['text' => $text]);
        $this->mailRecipient($user, $rueckmeldung, $text, 'Aktualisierte Rückmeldung zu '.$post->header);

        return $response->fresh();
    }

    /**
     * Abfrage beantworten oder (bei nicht mehrfachen Abfragen) die bestehende Antwort ersetzen.
     *
     * @param  array<int,string>  $answers  option_id => Wert ("1" für angehakte Optionen)
     */
    public function storeAbfrage(User $user, Post $post, array $answers, ?int $replaceId = null): UserRueckmeldungen
    {
        $rueckmeldung = $this->requireFeedback($user, $post, 'abfrage');
        $options = $rueckmeldung->options()->get()->keyBy('id');

        $clean = [];
        foreach ($answers as $optionId => $value) {
            $option = $options->get((int) $optionId);
            // Nur Optionen dieser Abfrage (vorher waren fremde Optionen beschreibbar).
            if (! $option || $option->type === 'trenner') {
                throw new HttpException(422, 'Ungültige Antwortoption.');
            }
            $value = is_bool($value) ? ($value ? '1' : '') : trim((string) $value);
            if ($value !== '' && $value !== '0') {
                $clean[$option->id] = $option->type === 'check' ? '1' : mb_substr($value, 0, 5000);
            }
        }

        if (empty($clean)) {
            throw new HttpException(422, 'Bitte beantworten Sie mindestens eine Frage.');
        }
        $missing = $options->filter(fn ($o) => $o->required && $o->type !== 'trenner' && ! isset($clean[$o->id]));
        if ($missing->isNotEmpty()) {
            throw new HttpException(422, 'Bitte füllen Sie alle Pflichtfelder aus: '.$missing->pluck('option')->implode(', '));
        }
        $checked = $options->filter(fn ($o) => $o->type === 'check' && isset($clean[$o->id]))->count();
        if ($rueckmeldung->max_answers && $checked > $rueckmeldung->max_answers) {
            throw new HttpException(422, "Bitte wählen Sie höchstens {$rueckmeldung->max_answers} Optionen.");
        }

        $existing = $this->familyResponses($user, $post);
        $target = $replaceId
            ? $existing->firstWhere('id', $replaceId)
            : (! $rueckmeldung->multiple ? $existing->first() : null);
        if ($replaceId && ! $target) {
            throw new HttpException(404, 'Antwort nicht gefunden.');
        }

        return DB::transaction(function () use ($user, $post, $clean, $target, $existing) {
            if ($target) {
                AbfrageAntworten::where('rueckmeldung_id', $target->id)->delete();
                $target->touch();
                $response = $target;
            } else {
                $response = UserRueckmeldungen::create([
                    'post_id' => $post->id,
                    'users_id' => $user->id,
                    'text' => '',
                    'rueckmeldung_number' => $existing->count() + 1,
                ]);
            }

            AbfrageAntworten::insert(collect($clean)->map(fn ($answer, $optionId) => [
                'rueckmeldung_id' => $response->id,
                'user_id' => $user->id,
                'option_id' => $optionId,
                'answer' => $answer,
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all());

            return $response->load('answers.option');
        });
    }

    private function mailRecipient(User $user, Rueckmeldungen $rueckmeldung, string $text, string $subject): void
    {
        if (! $rueckmeldung->empfaenger) {
            return;
        }
        $mail = Mail::to($rueckmeldung->empfaenger);
        if ($user->sendCopy) {
            $mail->cc($user);
        }
        $mail->queue(new UserRueckmeldungMail([
            'email' => $user->email,
            'name' => $user->name,
            'text' => $text,
            'subject' => $subject,
        ]));
    }
}
