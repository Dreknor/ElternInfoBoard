<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Model\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'uuid' => $this->uuid,
            'phone' => $this->phone,
            'public_mail' => $this->publicMail,
            'public_phone' => $this->publicPhone,
            'benachrichtigung' => $this->benachrichtigung,
            'send_copy' => (bool) $this->sendCopy,
            'release_calendar' => (bool) $this->releaseCalendar,
            'calendar_prefix' => $this->calendar_prefix,
            'messenger_discoverable' => (bool) $this->messenger_discoverable,
            'must_change_password' => (bool) $this->changePassword,
        ];
    }
}
