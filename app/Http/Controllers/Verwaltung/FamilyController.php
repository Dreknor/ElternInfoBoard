<?php

namespace App\Http\Controllers\Verwaltung;

use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\ChildGuardian;
use App\Model\Family;
use App\Model\GuardianLinkReport;
use App\Model\User;
use App\Services\Family\FamilyBuilder;
use App\Services\Family\FamilyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Familienverwaltung (FAM-10): Liste, Mitglieder, Zusammenführen/Trennen,
 * Sperren, Klärungsfälle, übernommene Beziehungen und Meldungen.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §7, §10, §12.3
 */
class FamilyController extends Controller implements HasMiddleware
{
    public function __construct(private readonly FamilyService $families) {}

    public static function middleware(): array
    {
        return ['auth', 'permission:manage families'];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $families = Family::query()
            ->withCount('users')
            ->with('users:id,name,family_id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('users', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->boolean('locked'), fn ($q) => $q->where('is_locked', true))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('families.index', [
            'families' => $families,
            'search' => $search,
            'counters' => $this->counters(),
        ]);
    }

    public function show(Family $family): View
    {
        $family->load('users');

        return view('families.show', [
            'family' => $family,
            'children' => $family->childrenQuery()->with(['parents', 'class', 'group'])->get(),
            'otherFamilies' => Family::query()->whereKeyNot($family->id)->orderBy('name')->get(['id', 'name']),
            'candidates' => User::query()->whereNull('family_id')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $family = $this->families->create(User::query()->whereIn('id', $data['user_ids'])->get(), $data['name'] ?? null, Family::SOURCE_MANUAL, true);

        return redirect()->route('families.show', $family)->with(['type' => 'success', 'Meldung' => 'Familie angelegt.']);
    }

    public function update(Request $request, Family $family): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $family->update($data + ['is_locked' => $request->boolean('is_locked')]);

        return back()->with(['type' => 'success', 'Meldung' => 'Familie gespeichert.']);
    }

    public function addMember(Request $request, Family $family): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);
        $user = User::findOrFail($data['user_id']);

        $this->families->addMember($family, $user);
        $this->families->setLocked($family, true);

        return back()->with(['type' => 'success', 'Meldung' => $user->name.' wurde der Familie hinzugefügt.']);
    }

    public function removeMember(Family $family, User $user): RedirectResponse
    {
        if ($user->family_id !== $family->id) {
            return back()->with(['type' => 'danger', 'Meldung' => 'Die Person gehört nicht zu dieser Familie.']);
        }

        $this->families->removeMember($user);
        if (Family::find($family->id)) {
            $this->families->setLocked($family, true);
        }

        return Family::find($family->id)
            ? back()->with(['type' => 'success', 'Meldung' => $user->name.' wurde aus der Familie gelöst.'])
            : redirect()->route('families.index')->with(['type' => 'success', 'Meldung' => 'Letztes Mitglied entfernt – Familie aufgelöst.']);
    }

    public function merge(Request $request, Family $family): RedirectResponse
    {
        $data = $request->validate(['source_id' => ['required', 'exists:families,id', 'different:family']]);

        $this->families->merge($family, Family::findOrFail($data['source_id']));

        return back()->with(['type' => 'success', 'Meldung' => 'Familien zusammengeführt.']);
    }

    public function split(Request $request, Family $family): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if (count($data['user_ids']) >= $family->users()->count()) {
            return back()->with(['type' => 'warning', 'Meldung' => 'Mindestens eine Person muss in der bisherigen Familie bleiben.']);
        }

        $new = $this->families->split($family, array_map('intval', $data['user_ids']), $data['name'] ?? null);

        return redirect()->route('families.show', $new)->with(['type' => 'success', 'Meldung' => 'Familie getrennt.']);
    }

    /**
     * Klärungsfälle, übernommene Beziehungen (E3) und Meldungen von Eltern.
     */
    public function review(FamilyBuilder $builder): View
    {
        $cases = collect($builder->reviewCases())->map(function (array $case) {
            return [
                'users' => User::query()->whereIn('id', $case['userIds'])->with('family')->get(),
                'children' => Child::query()->whereIn('id', $case['childIds'])->get()->keyBy('id'),
                'childSets' => $case['childSets'],
            ];
        });

        $pending = DB::table('child_user')
            ->where('source', ChildGuardian::SOURCE_MIGRATION)
            ->whereNull('reviewed_at')
            ->get(['child_id', 'user_id']);

        $pendingChildren = Child::query()->whereIn('id', $pending->pluck('child_id'))->with('parents')->get()->keyBy('id');
        $pendingUsers = User::query()->whereIn('id', $pending->pluck('user_id'))->get()->keyBy('id');

        return view('families.review', [
            'cases' => $cases,
            'pending' => $pending->map(fn ($row) => [
                'child' => $pendingChildren->get($row->child_id),
                'user' => $pendingUsers->get($row->user_id),
            ])->filter(fn ($row) => $row['child'] && $row['user'])->values(),
            'reports' => GuardianLinkReport::query()->open()->with(['child', 'user', 'reporter'])->latest()->get(),
            'counters' => $this->counters(),
        ]);
    }

    public function rebuild(Request $request, FamilyBuilder $builder): RedirectResponse
    {
        $report = $builder->rebuild(onlyUnassigned: $request->boolean('only_unassigned'));

        $summary = collect($report->summary())->map(fn ($count, $label) => "{$label}: {$count}")->implode(', ');

        return back()->with(['type' => 'success', 'Meldung' => 'Familienbildung ausgeführt – '.$summary]);
    }

    public function resolveReport(GuardianLinkReport $report): RedirectResponse
    {
        $report->update(['resolved_at' => now(), 'resolved_by' => auth()->id()]);

        return back()->with(['type' => 'success', 'Meldung' => 'Meldung als erledigt markiert.']);
    }

    /**
     * @return array{families: int, review_cases: int, pending_links: int, open_reports: int, without_family: int}
     */
    private function counters(): array
    {
        return [
            'families' => Family::count(),
            'review_cases' => count(app(FamilyBuilder::class)->reviewCases()),
            'pending_links' => DB::table('child_user')->where('source', ChildGuardian::SOURCE_MIGRATION)->whereNull('reviewed_at')->count(),
            'open_reports' => GuardianLinkReport::query()->open()->count(),
            'without_family' => User::query()->whereNull('family_id')->whereExists(
                fn ($q) => $q->from('child_user')->whereColumn('child_user.user_id', 'users.id')
            )->count(),
        ];
    }
}
