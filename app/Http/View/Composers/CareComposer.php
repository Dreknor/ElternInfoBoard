<?php

namespace App\Http\View\Composers;

use App\Settings\CareSetting;
use Illuminate\View\View;

class CareComposer
{
    protected $careSettings;

    public function __construct(CareSetting $careSettings)
    {
        $this->careSettings = $careSettings;
    }

    public function compose(View $view): void
    {
        $view->with('careSettings', $this->careSettings);
    }
}
