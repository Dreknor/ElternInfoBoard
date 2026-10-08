<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kind aus Sicht des angemeldeten Users: eigene Beziehung, Rechte, Herkunft.
 *
 * @mixin \App\Model\Child
 */
class ChildRelationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pivot = $this->pivot;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'class' => $this->class?->name,
            'group' => $this->group?->name,
            'relation' => $pivot?->relation,
            'relation_label' => $pivot?->relationType()->label(),
            'rights' => [
                'custody' => (bool) $pivot?->has_custody,
                'information' => (bool) $pivot?->receives_information,
                'manage' => (bool) $pivot?->can_manage,
            ],
            'valid_until' => $pivot?->valid_until?->toDateString(),
            'source' => $pivot?->source,
            'source_label' => $pivot?->sourceLabel(),
            'pending_review' => (bool) $pivot?->isPendingReview(),
        ];
    }
}
