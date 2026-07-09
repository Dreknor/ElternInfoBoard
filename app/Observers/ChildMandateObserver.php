<?php

namespace App\Observers;

use App\Mail\NewMandateMail;
use App\Settings\CareSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChildMandateObserver
{
    public function creating($model)
    {
        Cache::forget('child_mandates_'.$model->child_id);
    }

    public function created($model): void
    {
        $careSettings = new CareSetting;

        if ($careSettings->mandate_notification_enabled && ! empty($careSettings->mandate_notification_email)) {
            try {
                $model->load('child');
                Mail::to($careSettings->mandate_notification_email)
                    ->send(new NewMandateMail($model));
            } catch (\Throwable $e) {
                Log::error('Fehler beim Senden der Abholvollmacht-Benachrichtigung: '.$e->getMessage());
            }
        }
    }

    public function deleted($model): void
    {
        Cache::forget('child_mandates_'.$model->child_id);
    }
}
