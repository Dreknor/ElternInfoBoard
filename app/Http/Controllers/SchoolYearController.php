<?php

namespace App\Http\Controllers;

use App\Model\Group;
use App\Model\User;
use App\Services\SchoolYearService;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    public function index()
    {
        // Berechtigungsprüfung
        if (! auth()->user()->can('schoolyear.change')) {
            abort(403, 'Keine Berechtigung für den Schuljahreswechsel.');
        }
        $groups = Group::all();
        $roles = app('spatie.permission.models.role')::all();

        return view('schoolyear.index', compact('groups', 'roles'));
    }

    public function process(Request $request, SchoolYearService $service)
    {
        if (! auth()->user()->can('schoolyear.change')) {
            abort(403, 'Keine Berechtigung für den Schuljahreswechsel.');
        }
        $groupMapping = $request->input('group_mapping', []);
        $roleMapping = $request->input('role_mapping', []);
        $service->runSchoolYearChange($groupMapping, $roleMapping);
        $usersWithoutGroup = $service->getUsersWithoutGroup();

        return view('schoolyear.users_without_group', compact('usersWithoutGroup'));
    }

    public function massDelete(Request $request)
    {
        // Die geschützten Rollen sind fest im Code definiert und NICHT über die Settings änderbar
        $protectedRoles = ['Mitarbeiter', 'Vereinsmitglieder', 'Administrator'];
        $userIds = $request->input('user_ids', []);
        $fehler = '';
        $deleted = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }
            if ($user->roles()->whereIn('name', $protectedRoles)->exists()) {
                $fehler .= ($fehler ? ', ' : 'Nicht gelöscht: ').$user->name;

                continue;
            }
            try {
                $user->delete();
                $deleted++;
            } catch (\Exception $e) {
                $fehler .= ($fehler ? ', ' : 'Fehler bei: ').$user->name;
            }
        }

        return redirect(url('settings'))->with([
            'type' => ($fehler == '') ? 'success' : 'danger',
            'Meldung' => ($deleted ? "$deleted Benutzer gelöscht. " : '').$fehler,
        ]);
    }
}
