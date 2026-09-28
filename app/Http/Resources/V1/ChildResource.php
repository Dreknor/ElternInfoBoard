<?php

namespace App\Http\Resources\V1;

use App\Services\App\Family;
use App\Settings\CareSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Model\Child */
class ChildResource extends JsonResource
{
    private static ?array $care = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name.' '.$this->last_name),
            'group' => $this->group ? ['id' => $this->group->id, 'name' => $this->group->name] : null,
            'class' => $this->class ? ['id' => $this->class->id, 'name' => $this->class->name] : null,
            'notification' => (bool) $this->notification,
            'auto_checkIn' => (bool) $this->auto_checkIn,
            'is_in_care_module' => self::inCare($this->resource),
            // Rechte je Kind (B-80) – mit dem Familienmodell differenziert.
            'my_rights' => Family::rights($request->user(), $this->resource),
        ];
    }

    public static function inCare($child): bool
    {
        self::$care ??= (function () {
            try {
                $settings = app(CareSetting::class);

                return [$settings->groups_list ?? [], $settings->class_list ?? []];
            } catch (\Throwable) {
                // Hort-Modul nicht eingerichtet → kein Kind ist im Hort.
                return [[], []];
            }
        })();

        return in_array($child->group_id, self::$care[0]) && in_array($child->class_id, self::$care[1]);
    }
}
