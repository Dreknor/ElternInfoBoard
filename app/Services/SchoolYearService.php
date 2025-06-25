<?php

namespace App\Services;

use App\Model\Arbeitsgemeinschaft;
use App\Model\Krankmeldungen;
use App\Model\Schickzeiten;
use App\Model\User;
use App\Model\Child;
use App\Model\Group;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchoolYearService
{
    public function runSchoolYearChange(array $groupMapping, array $roleMapping)
    {
        Log::info('Schuljahreswechsel gestartet', [
            'user_id' => auth()->id(),
            'group_mapping' => $groupMapping,
            'role_mapping' => $roleMapping,
        ]);
        DB::transaction(function () use ($groupMapping, $roleMapping) {
            // 1. Gruppen von Usern und Kindern anpassen
            $this->updateGroups($groupMapping);
            // 2. Rollen anpassen
            $this->updateRoles($roleMapping);
            // 3. Arbeitsgemeinschaften löschen
            Log::info('Alle Arbeitsgemeinschaften werden gelöscht');

            Arbeitsgemeinschaft::query()->delete();
            // 4. Krankmeldungen älter als 3 Wochen löschen
            $deletedKrankmeldungen = Krankmeldungen::where('created_at', '<', now()->subWeeks(3))->delete();
            Log::info('Krankmeldungen gelöscht', ['count' => $deletedKrankmeldungen]);
            // 5. Schickzeiten mit deleted_at löschen
            $deletedSchickzeiten = Schickzeiten::whereNotNull('deleted_at')->delete();
            Log::info('Schickzeiten gelöscht', ['count' => $deletedSchickzeiten]);
        });
        Log::info('Schuljahreswechsel abgeschlossen', [
            'user_id' => auth()->id()
        ]);
    }

    private function updateGroups(array $groupMapping)
    {
        foreach ($groupMapping as $oldGroupId => $newGroupId) {
            if ($oldGroupId == $newGroupId) continue;
            Log::info('Gruppenwechsel', ['alt' => $oldGroupId, 'neu' => $newGroupId]);
            $userIds = DB::table('group_user')
                ->where('group_id', $oldGroupId)
                ->where('updated_at', '<', now()->subWeeks(3))
                ->pluck('user_id');
            foreach ($userIds as $userId) {
                $exists = DB::table('group_user')
                    ->where('user_id', $userId)
                    ->where('group_id', $newGroupId)
                    ->exists();
                if (!$exists && $newGroupId) {
                    DB::table('group_user')->insert([
                        'user_id' => $userId,
                        'group_id' => $newGroupId,
                    ]);
                }
                // Alten Eintrag entfernen
                DB::table('group_user')
                    ->where('user_id', $userId)
                    ->where('group_id', $oldGroupId)
                    ->delete();
            }
            Log::info('Gruppenwechsel aktualisiert', ['count' => $newGroupId]);
        }

        $this->moveChildrenToNewGroup($groupMapping);
        DB::table('children')
            ->whereNull('class_id')
            ->orWhereNull('group_id')
            ->delete();
    }

    /**
     * Verschiebt Kinder anhand des gesamten groupMappings, sodass class_id und group_id nur einmalig pro Kind angepasst werden.
     */
    private function moveChildrenToNewGroup(array $groupMapping)
    {
        $children = DB::table('children')
            ->where('updated_at', '<', now()->subWeeks(3))
            ->get();
        foreach ($children as $child) {
            $update = [];
            if (array_key_exists($child->group_id, $groupMapping)) {
                $newGroupId = $groupMapping[$child->group_id];
                if ($child->group_id != $newGroupId) {
                    $update['group_id'] = $newGroupId;
                }
            }
            if (array_key_exists($child->class_id, $groupMapping)) {
                $newClassId = $groupMapping[$child->class_id];
                if ($child->class_id != $newClassId) {
                    $update['class_id'] = $newClassId;
                }
            }
            if (!empty($update)) {
                DB::table('children')
                    ->where('id', $child->id)
                    ->update($update);
            }
        }
    }

    private function updateRoles(array $roleMapping)
    {
        $modelType = 'App\\Model\\User';
        foreach ($roleMapping as $oldRole => $newRole) {
            if ($oldRole == $newRole) continue;
            Log::info('Rollenwechsel', ['alt' => $oldRole, 'neu' => $newRole]);
            // Alle User mit alter Rolle holen
            $userIds = DB::table('model_has_roles')
                ->where('role_id', $oldRole)
                ->where('model_type', $modelType)
                ->pluck('model_id');
            foreach ($userIds as $userId) {
                // Prüfen, ob User die neue Rolle schon hat
                $exists = DB::table('model_has_roles')
                    ->where('role_id', $newRole)
                    ->where('model_id', $userId)
                    ->where('model_type', $modelType)
                    ->exists();
                if (!$exists && $newRole) {
                    // Update auf neue Rolle
                    DB::table('model_has_roles')
                        ->where('role_id', $oldRole)
                        ->where('model_id', $userId)
                        ->where('model_type', $modelType)
                        ->update(['role_id' => $newRole]);
                } else {
                    // Alte Rolle löschen, da neue schon vorhanden
                    DB::table('model_has_roles')
                        ->where('role_id', $oldRole)
                        ->where('model_id', $userId)
                        ->where('model_type', $modelType)
                        ->delete();
                }
            }
        }
    }

    public function getUsersWithoutGroup()
    {
        // Nur Nutzer ohne Gruppen, die NICHT die geschützten Rollen haben
        $protectedRoles = ['Mitarbeiter', 'Schulbegleiter', 'Administrator'];
        return User::doesntHave('groups')
            ->whereDoesntHave('roles', function($query) use ($protectedRoles) {
                $query->whereIn('name', $protectedRoles);
            })
            ->get();
    }
}
