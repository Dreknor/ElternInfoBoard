<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bezugsperson eines Kindes (User mit Pivot ChildGuardian).
 *
 * @mixin \App\Model\User
 */
class GuardianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pivot = $this->pivot;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'relation' => $pivot?->relation,
            'relation_label' => $pivot?->relationType()->label(),
            'has_custody' => (bool) $pivot?->has_custody,
            'receives_information' => (bool) $pivot?->receives_information,
            'can_manage' => (bool) $pivot?->can_manage,
        ];
    }
}
