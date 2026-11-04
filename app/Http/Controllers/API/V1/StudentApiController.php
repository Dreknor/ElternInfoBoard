<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Model\Arbeitsgemeinschaft;
use App\Model\Post;
use App\Model\Termin;
use App\Model\User;
use App\Model\UserDevice;
use App\Model\Vertretung;
use App\Services\Students\StudentAccountLog;
use App\Services\Students\StudentContext;
use App\Services\Students\StudentPostQuery;
use App\Services\StundenplanDataProvider;
use App\Support\StudentAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * App-API für Schülerkonten (docs/konzept-schuelerkonten.md §8, Paket S-12).
 *
 * Routen `api.v1.student.*` unter /api/v1/student. Tokens tragen nur die Fähigkeit `student`
 * und laufen nach StudentAccounts::TOKEN_DAYS Tagen ab. Alle übrigen v1-Endpunkte lehnen
 * Schülerkonten ab (RestrictStudentAccounts). Gleicher Datenbestand wie der Web-Schülerbereich.
 */
class StudentApiController extends Controller
{
    private const FAILED = 'Benutzername oder Passwort stimmt nicht.';

    /** Anmeldung mit Benutzername und Passwort (nur wenn in den Einstellungen erlaubt). */
    public function login(Request $request): JsonResponse
    {
        abort_unless(StudentAccounts::usernameLoginEnabled(), 404);

        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:200'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $student = User::students()->where('username', mb_strtolower(trim($data['username'])))->first();
        $valid = Hash::check($data['password'], $student?->password ?? Hash::make(Str::random(32)));

        if (! $student || ! $valid || $student->is_active === false) {
            throw ValidationException::withMessages(['username' => [self::FAILED]]);
        }

        $token = $student->createToken(
            mb_substr($data['device_name'], 0, 100),
            [StudentAccounts::TOKEN_ABILITY],
            now()->addDays(StudentAccounts::TOKEN_DAYS)
        );

        StudentAccountLog::info('Schülerkonto: App-Login', $student);

        return response()->json(['data' => [
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'account_type' => User::ACCOUNT_STUDENT,
        ]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Abgemeldet.']);
    }

    /** Start der App: wer bin ich, welche Bereiche gibt es. */
    public function bootstrap(Request $request): JsonResponse
    {
        $student = $request->user();

        return response()->json(['data' => [
            'account_type' => User::ACCOUNT_STUDENT,
            'user' => ['id' => $student->id, 'name' => $student->name, 'username' => $student->username],
            'klasse' => StudentContext::className($student),
            'gruppen' => StudentContext::groupNames($student)->values(),
            'bereiche' => ['nachrichten', 'termine', 'vertretungsplan', 'stundenplan', 'ags'],
        ]]);
    }

    public function posts(Request $request): JsonResponse
    {
        $page = StudentPostQuery::currentPosts($request->user())->with('media')->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn (Post $p) => $this->postData($p, false))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function showPost(Request $request, int $post): JsonResponse
    {
        $model = StudentPostQuery::findPost($request->user(), $post);
        abort_if($model === null, 404);

        return response()->json(['data' => $this->postData($model->load('media', 'autor'), true)]);
    }

    private function postData(Post $p, bool $full): array
    {
        $data = [
            'id' => $p->id,
            'titel' => $p->header,
            'aktualisiert_am' => $p->updated_at?->toIso8601String(),
            'wichtig' => (bool) $p->sticky,
        ];

        if ($full) {
            $data['text'] = $p->news;
            $data['autor'] = $p->autor?->name;
            $data['dateien'] = $p->media
                ->whereIn('collection_name', ['images', 'files', 'header'])
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'mime_type' => $m->mime_type,
                    'url' => route('api.v1.student.media', [$p->id, $m->id]),
                ])->values();
        }

        return $data;
    }

    public function termine(Request $request): JsonResponse
    {
        return response()->json(['data' => StudentPostQuery::termine($request->user())->get()
            ->map(fn (Termin $t) => [
                'id' => $t->id,
                'titel' => $t->terminname,
                'start' => $t->start?->toIso8601String(),
                'ende' => $t->ende?->toIso8601String(),
                'ganztags' => (bool) $t->fullDay,
            ])->values()]);
    }

    public function vertretungsplan(Request $request): JsonResponse
    {
        return response()->json(['data' => StudentPostQuery::vertretungen($request->user())
            ->map(fn (Vertretung $v) => [
                'datum' => $v->date,
                'stunde' => $v->stunde,
                'klasse' => $v->klasse_kurzform ?: $v->group?->name,
                'fach_alt' => $v->altFach,
                'fach_neu' => $v->neuFach,
                'lehrkraft' => $v->lehrer,
                'hinweis' => $v->comment,
            ])->values()]);
    }

    public function stundenplan(Request $request): JsonResponse
    {
        $provider = app(StundenplanDataProvider::class);
        $data = $provider->getStundenplanData();
        $child = StudentContext::child($request->user());
        $klassen = ($data && $child) ? array_values($provider->klassenForChild($child, $data)) : [];

        $klasse = $request->query('klasse');
        $klasse = in_array($klasse, $klassen, true) ? $klasse : ($klassen[0] ?? null);

        return response()->json(['data' => [
            'klassen' => $klassen,
            'klasse' => $klasse,
            'zeitslots' => $klasse ? $provider->zeitslotsForClass($data, $klasse) : [],
            'stunden' => $klasse ? $provider->timetableForClass($data, $klasse) : [],
        ]]);
    }

    public function ags(Request $request): JsonResponse
    {
        $groupIds = StudentContext::groupIds($request->user());
        $child = StudentContext::child($request->user());
        $enrolled = $child ? $child->arbeitsgemeinschaften()->pluck('arbeitsgemeinschaften.id')->all() : [];

        $ags = $groupIds === [] ? collect() : Arbeitsgemeinschaft::query()
            ->with('manager:id,name')
            ->where('end_date', '>', now())
            ->whereHas('groups', fn ($q) => $q->withoutGlobalScopes()->whereIn('groups.id', $groupIds))
            ->orderBy('weekday')->orderBy('start_time')
            ->get();

        return response()->json(['data' => $ags->map(fn ($ag) => [
            'id' => $ag->id,
            'name' => $ag->name,
            'beschreibung' => $ag->description,
            'wochentag' => $ag->weekday,
            'beginn' => $ag->start_time,
            'ende' => $ag->end_time,
            'leitung' => $ag->manager?->name,
            'angemeldet' => in_array($ag->id, $enrolled, true),
        ])->values()]);
    }

    /** Push-Gerät registrieren (Mitteilungen enthalten nur den Titel). */
    public function storeDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
            'provider' => 'required|in:fcm,apns',
            'platform' => 'nullable|string|max:20',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:20',
        ]);

        UserDevice::updateOrCreate(['token' => $data['token']], $data + ['user_id' => $request->user()->id, 'last_seen_at' => now()]);

        return response()->json(['message' => 'Gerät registriert.'], 201);
    }

    public function destroyDevice(Request $request, string $token): JsonResponse
    {
        UserDevice::where('user_id', $request->user()->id)->where('token', $token)->delete();

        return response()->json(['message' => 'Gerät entfernt.']);
    }
}
