<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use App\Services\Students\StudentAccountService;
use App\Services\Students\StudentCodeSheet;
use App\Settings\SchuelerSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verwaltung der Schülerkonten (docs/konzept-schuelerkonten.md §5.4, §9, Paket S-07).
 *
 * Eigener Reiter neben der Benutzerverwaltung; Schülerkonten erscheinen nirgends sonst.
 * Rechte: „edit user“ (Konten), „edit settings“ (Einstellungen). Nur bei aktivem Modul.
 */
class StudentAccountAdminController extends Controller
{
    public function __construct(private readonly StudentAccountService $accounts) {}

    public function index(Request $request): View
    {
        $groupId = $request->integer('gruppe') ?: null;
        $search = trim((string) $request->query('suche', ''));
        $status = $request->query('status');

        $children = Child::query()
            ->with(['studentAccount', 'class:id,name', 'group:id,name'])
            ->where('status', '!=', Child::STATUS_LEFT)
            ->when($groupId, fn ($q) => $q->where(fn ($q) => $q->where('class_id', $groupId)->orWhere('group_id', $groupId)))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhereHas('studentAccount', fn ($q) => $q->where('username', 'like', "%{$search}%"))))
            ->when($status === 'ohne', fn ($q) => $q->whereNull('user_id'))
            ->when($status === 'aktiv', fn ($q) => $q->whereHas('studentAccount', fn ($q) => $q->where('is_active', true)))
            ->when($status === 'gesperrt', fn ($q) => $q->whereHas('studentAccount', fn ($q) => $q->where('is_active', false)))
            ->when(! in_array($status, ['ohne', 'aktiv', 'gesperrt'], true), fn ($q) => $q->whereNotNull('user_id'))
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(50)
            ->withQueryString();

        // Konten ohne (aktives) Kind, z. B. nach Austritt – stehen zur Löschung an
        $orphans = User::students()
            ->whereDoesntHave('studentChild', fn ($q) => $q->where('status', '!=', Child::STATUS_LEFT))
            ->orderBy('name')
            ->get();

        return view('student.admin.index', [
            'children' => $children,
            'orphans' => $orphans,
            'groups' => Group::active()->orderBy('name')->get(['id', 'name']),
            'groupId' => $groupId,
            'search' => $search,
            'status' => in_array($status, ['ohne', 'aktiv', 'gesperrt'], true) ? $status : 'alle',
            'counts' => [
                'konten' => User::students()->count(),
                'aktiv' => User::students()->where('is_active', true)->count(),
                'ohne' => Child::query()->where('status', '!=', Child::STATUS_LEFT)->whereNull('user_id')->count(),
            ],
            'studentSettings' => app(SchuelerSetting::class),
        ]);
    }

    /** Konto für ein Kind anlegen. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'child_id' => ['required', 'integer', Rule::exists('children', 'id')],
            'username' => ['nullable', 'string', 'max:120'],
        ]);

        $child = Child::findOrFail($data['child_id']);

        try {
            [$student, $created] = $this->accounts->createForChild($child, ['username' => $data['username'] ?? null]);
        } catch (\DomainException $e) {
            return back()->withInput()->with(['type' => 'danger', 'Meldung' => $e->getMessage()]);
        }

        return back()->with([
            'type' => 'success',
            'Meldung' => $created
                ? "Konto „{$student->username}“ angelegt. Den Zugangszettel erzeugt „Code drucken“."
                : 'Für dieses Kind gibt es schon ein Konto.',
        ]);
    }

    /**
     * „Konten für diese Klasse anlegen“ (§9.1 Nr. 7): legt fehlende Konten an und liefert
     * die Zettel der neu angelegten Konten als PDF.
     */
    public function storeForClass(Request $request, StudentCodeSheet $sheet): Response|RedirectResponse
    {
        $data = $request->validate(['group_id' => ['required', 'integer', Rule::exists('groups', 'id')]]);

        $children = Child::query()
            ->where('status', '!=', Child::STATUS_LEFT)
            ->whereNull('user_id')
            ->where(fn ($q) => $q->where('class_id', $data['group_id'])->orWhere('group_id', $data['group_id']))
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $created = collect();
        foreach ($children as $child) {
            [$student, $new] = $this->accounts->createForChild($child, ['via' => 'klasse']);
            if ($new) {
                $created->push($student);
            }
        }

        if ($created->isEmpty()) {
            return back()->with(['type' => 'info', 'Meldung' => 'In dieser Klasse haben bereits alle Kinder ein Konto.']);
        }

        return $sheet->download($created, 'zugangscodes_'.\Illuminate\Support\Str::slug(Group::find($data['group_id'])?->name ?? 'klasse'));
    }

    /** Neue Einmal-Codes für ausgewählte Konten (PDF). */
    public function codes(Request $request, StudentCodeSheet $sheet): Response|RedirectResponse
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:500'],
            'student_ids.*' => ['integer'],
        ]);

        $students = User::students()->whereIn('id', $data['student_ids'])->get();
        if ($students->isEmpty()) {
            return back()->with(['type' => 'warning', 'Meldung' => 'Keine Schülerkonten ausgewählt.']);
        }

        return $sheet->download($students);
    }

    public function update(Request $request, int $student): RedirectResponse
    {
        $account = $this->find($student);
        $data = $request->validate(['username' => ['required', 'string', 'max:120']]);

        try {
            $this->accounts->rename($account, $data['username']);
        } catch (\DomainException $e) {
            return back()->with(['type' => 'danger', 'Meldung' => $e->getMessage()]);
        }

        return back()->with(['type' => 'success', 'Meldung' => 'Benutzername geändert.']);
    }

    public function deactivate(int $student): RedirectResponse
    {
        $this->accounts->deactivate($this->find($student));

        return back()->with(['type' => 'success', 'Meldung' => 'Konto gesperrt. Laufende Anmeldungen sind beendet.']);
    }

    public function activate(int $student): RedirectResponse
    {
        try {
            $this->accounts->activate($this->find($student));
        } catch (\DomainException $e) {
            return back()->with(['type' => 'danger', 'Meldung' => $e->getMessage()]);
        }

        return back()->with(['type' => 'success', 'Meldung' => 'Konto entsperrt.']);
    }

    public function destroy(int $student): RedirectResponse
    {
        $this->accounts->delete($this->find($student));

        return back()->with(['type' => 'success', 'Meldung' => 'Konto gelöscht. Das Kind bleibt erhalten.']);
    }

    public function settings(): View
    {
        return view('student.admin.settings', [
            'studentSettings' => app(SchuelerSetting::class),
            'groups' => Group::active()->orderBy('name')->get(['id', 'name']),
            'ucsAvailable' => $this->ucsAvailable(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login_sso' => ['boolean'],
            'login_benutzername' => ['boolean'],
            'jit_anlage' => ['boolean'],
            'ucs_provisionierung' => ['boolean'],
            'freigegebene_gruppen' => ['array'],
            'freigegebene_gruppen.*' => ['integer', Rule::exists('groups', 'id')],
            'eltern_zustimmung' => ['boolean'],
            'eltern_codes' => ['boolean'],
            'loeschen_nach_tagen' => ['required', 'integer', 'min:1', 'max:365'],
            'vertretung_abwesenheiten' => ['boolean'],
        ]);

        $settings = app(SchuelerSetting::class);
        foreach (['login_sso', 'login_benutzername', 'jit_anlage', 'ucs_provisionierung', 'eltern_zustimmung', 'eltern_codes', 'vertretung_abwesenheiten'] as $flag) {
            $settings->{$flag} = (bool) ($data[$flag] ?? false);
        }
        $settings->freigegebene_gruppen = array_values(array_map('intval', $data['freigegebene_gruppen'] ?? []));
        $settings->loeschen_nach_tagen = (int) $data['loeschen_nach_tagen'];
        // Messenger mit Lehrkräften (S-14) ist noch nicht umgesetzt und bleibt aus.
        $settings->messenger_lehrkraefte = false;
        $settings->save();

        \App\Services\Students\StudentAccountLog::info('Schülerkonten: Einstellungen geändert', null);

        return back()->with(['type' => 'success', 'Meldung' => 'Einstellungen gespeichert.']);
    }

    private function find(int $id): User
    {
        return User::students()->findOrFail($id);
    }

    private function ucsAvailable(): bool
    {
        try {
            return (bool) app(\App\Settings\UcsSetting::class)->enabled;
        } catch (\Throwable) {
            return false;
        }
    }
}
