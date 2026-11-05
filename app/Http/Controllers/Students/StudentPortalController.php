<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Model\Arbeitsgemeinschaft;
use App\Model\Vertretung;
use App\Model\VertretungsplanAbsence;
use App\Model\VertretungsplanNews;
use App\Model\VertretungsplanWeek;
use App\Services\Students\StudentAccountLog;
use App\Services\Students\StudentContext;
use App\Services\Students\StudentPostQuery;
use App\Services\StundenplanDataProvider;
use App\Settings\SchuelerSetting;
use App\Support\UploadGuard;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Oberfläche der Schülerkonten (docs/konzept-schuelerkonten.md §4, Pakete S-05, S-06).
 *
 * Erreichbar nur über die Routen `student.*` (Middleware `student`). Alle Daten kommen aus
 * StudentPostQuery bzw. dem verknüpften Kind (StudentContext), nie aus Gruppenmitgliedschaften.
 */
class StudentPortalController extends Controller
{
    public function home(Request $request): View
    {
        $student = $request->user();
        $vertretungen = $this->vertretungen($student)->where('date', '<=', today()->addDays(1)->toDateString());

        return view('student.home', [
            'child' => StudentContext::child($student),
            'klasse' => StudentContext::className($student),
            'posts' => StudentPostQuery::currentPosts($student)->with('media')->limit(5)->get(),
            'termine' => StudentPostQuery::termine($student)->limit(5)->get(),
            'vertretungen' => $vertretungen,
        ]);
    }

    public function posts(Request $request): View
    {
        $student = $request->user();
        $archiv = $request->boolean('archiv');

        $query = $archiv
            ? StudentPostQuery::posts($student)->whereNotNull('posts.archiv_ab')->where('posts.archiv_ab', '<=', now())->orderByDesc('posts.archiv_ab')
            : StudentPostQuery::currentPosts($student);

        return view('student.posts', [
            'posts' => $query->with('media')->paginate(15)->withQueryString(),
            'archiv' => $archiv,
        ]);
    }

    public function showPost(Request $request, int $post): View
    {
        $model = StudentPostQuery::findPost($request->user(), $post);
        abort_if($model === null, 404);

        return view('student.post', ['post' => $model->load('media', 'autor')]);
    }

    /**
     * Bild oder Anhang eines Schülerbeitrags. Nur Dateien von Beiträgen, die der Schüler sieht.
     */
    public function media(Request $request, int $post, int $media): Response
    {
        $model = StudentPostQuery::findPost($request->user(), $post);
        abort_if($model === null, 404);

        /** @var Media|null $file */
        $file = $model->media()->whereKey($media)->whereIn('collection_name', ['images', 'files', 'header'])->first();
        abort_if($file === null, 404);

        $isImage = str_starts_with((string) $file->mime_type, 'image/') && ! str_contains((string) $file->mime_type, 'svg');
        if (! $isImage) {
            return UploadGuard::harden($file->toResponse($request), $file, false);
        }

        $conversion = $request->query('conversion');
        $path = in_array($conversion, ['thumb', 'preview'], true) && $file->hasGeneratedConversion($conversion)
            ? $file->getPath($conversion)
            : $file->getPath();

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $file->mime_type);

        return UploadGuard::harden($response, $file);
    }

    public function termine(Request $request): View
    {
        return view('student.termine', [
            'termine' => StudentPostQuery::termine($request->user())->get()
                ->groupBy(fn ($t) => $t->start->locale('de')->isoFormat('MMMM YYYY')),
        ]);
    }

    public function vertretungsplan(Request $request): View
    {
        $settings = app(SchuelerSetting::class);
        $student = $request->user();

        return view('student.vertretungsplan', [
            'klasse' => StudentContext::className($student),
            'vertretungen' => $this->vertretungen($student)->groupBy('date'),
            'news' => VertretungsplanNews::query()->where('end', '>=', today())->orderBy('start')->get(),
            'week' => VertretungsplanWeek::query()->whereDate('week', '<=', today())->orderByDesc('week')->first(),
            'absences' => $settings->vertretung_abwesenheiten
                ? VertretungsplanAbsence::query()->whereDate('end_date', '>=', today())->whereDate('start_date', '<=', today()->addDays(7))->orderBy('start_date')->get()
                : collect(),
        ]);
    }

    public function stundenplan(Request $request): View
    {
        $provider = app(StundenplanDataProvider::class);
        $data = $provider->getStundenplanData();
        $child = StudentContext::child($request->user());
        $klassen = ($data && $child) ? array_values($provider->klassenForChild($child, $data)) : [];

        $klasse = $request->query('klasse');
        $klasse = in_array($klasse, $klassen, true) ? $klasse : ($klassen[0] ?? null);

        $vertretungen = collect();
        if ($klasse !== null) {
            $vertretungen = Vertretung::withoutGlobalScope('date')
                ->where('klasse_kurzform', $klasse)
                ->whereBetween('date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
                ->get()
                ->groupBy(fn ($v) => Carbon::parse($v->getRawOriginal('date'))->dayOfWeekIso);
        }

        return view('student.stundenplan', [
            'klassen' => $klassen,
            'klasse' => $klasse,
            'timetable' => $klasse ? $provider->timetableForClass($data, $klasse) : [],
            'zeitslots' => $klasse ? $provider->zeitslotsForClass($data, $klasse) : [],
            'vertretungen' => $vertretungen,
            'today' => now()->isWeekend() ? 1 : now()->dayOfWeekIso,
        ]);
    }

    public function ags(Request $request): View
    {
        $groupIds = StudentContext::groupIds($request->user());
        $child = StudentContext::child($request->user());

        $ags = $groupIds === [] ? collect() : Arbeitsgemeinschaft::query()
            ->with('manager:id,name')
            ->where('end_date', '>', now())
            ->whereHas('groups', fn ($q) => $q->withoutGlobalScopes()->whereIn('groups.id', $groupIds))
            ->orderBy('weekday')->orderBy('start_time')
            ->get();

        $enrolled = $child ? $child->arbeitsgemeinschaften()->pluck('arbeitsgemeinschaften.id')->all() : [];

        return view('student.ags', ['ags' => $ags, 'enrolled' => $enrolled]);
    }

    public function profile(Request $request): View
    {
        $student = $request->user();

        return view('student.profile', [
            'student' => $student,
            'klasse' => StudentContext::className($student),
            'canChangePassword' => $this->usesPassword($student),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $student = $request->user();
        abort_unless($this->usesPassword($student), 404);

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->input('current_password'), $student->password)) {
            return back()->withErrors(['current_password' => 'Das bisherige Passwort stimmt nicht.']);
        }

        $student->forceFill(['password' => Hash::make($request->input('password')), 'changePassword' => false])->save();
        StudentAccountLog::info('Passwort geändert', $student);

        return redirect()->route('student.profile')->with(['type' => 'success', 'Meldung' => 'Dein Passwort wurde geändert.']);
    }

    private function vertretungen($student)
    {
        return StudentPostQuery::vertretungen($student);
    }

    /** Passwort gibt es nur beim Login mit Benutzername (nicht bei SSO-Konten). */
    private function usesPassword($student): bool
    {
        return $student->ucs_oidc_sub === null && $student->ucs_source !== 'kelvin' && app(SchuelerSetting::class)->login_benutzername;
    }
}
