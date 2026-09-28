<?php

namespace App\Http\Controllers\Verwaltung;

use App\Enums\GuardianRelation;
use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\ChildGuardian;
use App\Model\GuardianLinkReport;
use App\Model\User;
use App\Services\Family\GuardianshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;

/**
 * Pflege der Bezugspersonen eines Kindes – ausschließlich Verwaltung
 * (Permission „manage families“, E6).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §10
 */
class GuardianController extends Controller implements HasMiddleware
{
    public function __construct(private readonly GuardianshipService $guardianship) {}

    public static function middleware(): array
    {
        return ['auth', 'permission:manage families'];
    }

    public function store(Request $request, Child $child): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'relation' => ['required', Rule::in(array_keys(GuardianRelation::options()))],
        ]);

        $user = User::findOrFail($data['user_id']);

        if ($this->guardianship->pivot($child, $user) !== null) {
            return back()->with(['type' => 'warning', 'Meldung' => $user->name.' ist bereits Bezugsperson.']);
        }

        $this->guardianship->link($child, $user, GuardianRelation::from($data['relation']));

        return back()->with(['type' => 'success', 'Meldung' => $user->name.' wurde als Bezugsperson hinzugefügt.']);
    }

    public function update(Request $request, Child $child, User $user): RedirectResponse
    {
        $data = $request->validate([
            'relation' => ['required', Rule::in(array_keys(GuardianRelation::options()))],
            'valid_until' => ['nullable', 'date'],
        ]);

        $this->guardianship->update($child, $user, [
            'relation' => $data['relation'],
            'has_custody' => $request->boolean('has_custody'),
            'receives_information' => $request->boolean('receives_information'),
            'can_manage' => $request->boolean('can_manage'),
            'valid_until' => $data['valid_until'] ?? null,
        ]);

        // Eine bewusste Änderung durch die Verwaltung gilt als Prüfung (E3)
        $this->guardianship->markReviewed($child, $user);

        return back()->with(['type' => 'success', 'Meldung' => 'Beziehung gespeichert.']);
    }

    public function applyDefaults(Child $child, User $user): RedirectResponse
    {
        $pivot = $this->guardianship->applyDefaultRights($child, $user);

        return back()->with(['type' => 'success', 'Meldung' => 'Standardrechte für „'.$pivot->relationType()->label().'“ übernommen.']);
    }

    public function review(Child $child, User $user): RedirectResponse
    {
        $this->guardianship->markReviewed($child, $user);
        $this->resolveReports($child, $user);

        return back()->with(['type' => 'success', 'Meldung' => 'Beziehung als geprüft markiert.']);
    }

    public function destroy(Request $request, Child $child, User $user): RedirectResponse
    {
        $pivot = $this->guardianship->pivot($child, $user);

        if ($pivot?->source === ChildGuardian::SOURCE_UCS && $pivot->is_auto_provisioned && ! $request->boolean('force')) {
            return back()->with([
                'type' => 'warning',
                'Meldung' => 'Diese Beziehung stammt aus UCS@school und würde beim nächsten Sync wieder angelegt. Bitte in UCS korrigieren.',
            ]);
        }

        $this->guardianship->unlink($child, $user);
        $this->resolveReports($child, $user);

        return back()->with(['type' => 'success', 'Meldung' => 'Beziehung entfernt.']);
    }

    private function resolveReports(Child $child, User $user): void
    {
        GuardianLinkReport::query()->open()
            ->where('child_id', $child->id)
            ->where('user_id', $user->id)
            ->update(['resolved_at' => now(), 'resolved_by' => auth()->id()]);
    }
}
