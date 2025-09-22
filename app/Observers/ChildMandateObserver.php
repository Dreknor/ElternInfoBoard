<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class ChildMandateObserver
{
    public function creating($model)
    {
        Cache::forget('child_mandates_' . $model->child_id);
    }

    public function deleted($model)
    {
        Cache::forget('child_mandates_' . $model->child_id);
    }
}
