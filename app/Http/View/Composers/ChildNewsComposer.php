<?php

namespace App\Http\View\Composers;

use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ChildNewsComposer
{
    public function compose($view): void
    {
        $children = auth()->user()->children();

        if ($children->count() > 0) {
            $allowedClasses = Cache::remember('careSettings_classes', now()->addDay(), function ()  use ($children){
                $careSettings = new CareSetting();
                return $careSettings->class_list;
            });

            $allowedGroups = Cache::remember('careSettings_groups', now()->addDay(), function () use ($children) {
                $careSettings = new CareSetting();
                return $careSettings->groups_list;
            });

            $children = $children->filter(function ($child) use ($allowedClasses, $allowedGroups) {
                return in_array($child->class_id, $allowedClasses) && in_array($child->group_id, $allowedGroups);
            });
            $children = $children->load(['checkIns' => fn ($query) => $query->where('date', '>=', today())->where('date', '<=', Carbon::now()->addDays(40))])
                ->sortBy(function ($child) {
                    return $child->checkIns->count() > 0 ? $child->checkIns->first()->date : now();
                });

        }


        $view->with('children', $children);
    }
}
