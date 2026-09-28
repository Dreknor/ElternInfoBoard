<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Familie mit Mitgliedern und (abgeleiteten) Kindern.
 *
 * @mixin \App\Model\Family
 */
class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'members' => $this->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values(),
            'children' => $this->childrenQuery()->get()->map(fn ($child) => [
                'id' => $child->id,
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
            ])->values(),
        ];
    }
}
