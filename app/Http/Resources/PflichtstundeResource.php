<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PflichtstundeResource
 *
 * API Resource für Pflichtstunden-Daten.
 *
 * @package App\Http\Resources
 */
class PflichtstundeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'start' => $this->start?->toISOString(),
            'end' => $this->end?->toISOString(),
            'duration_minutes' => $this->duration,
            'duration_hours' => round($this->duration / 60, 2),
            'description' => $this->description,
            'bereich' => $this->bereich,
            'status' => $this->getStatus(),
            'approved' => $this->approved,
            'approved_at' => $this->approved_at?->toISOString(),
            'approved_by' => $this->approved_by,
            'approver_name' => $this->approver?->name,
            'rejected' => $this->rejected,
            'rejected_at' => $this->rejected_at?->toISOString(),
            'rejected_by' => $this->rejected_by,
            'rejector_name' => $this->rejector?->name,
            'rejection_reason' => $this->rejection_reason,
            'listen_termin_id' => $this->listen_termin_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Bestimme den Status der Pflichtstunde
     *
     * @return string
     */
    private function getStatus(): string
    {
        if ($this->approved) {
            return 'approved';
        }
        if ($this->rejected) {
            return 'rejected';
        }
        if ($this->end && $this->end->isPast()) {
            return 'pending';
        }
        return 'upcoming';
    }
}

