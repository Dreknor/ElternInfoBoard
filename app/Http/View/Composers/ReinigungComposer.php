<?php

namespace App\Http\View\Composers;

use App\Model\Reinigung;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ReinigungComposer
{
    public function compose($view): void
    {
        if (!auth()->check()) {
            $view->with('reinigung', null);
            return;
        }

        $expire = now()->diffInSeconds(now()->endOfDay());

        $reinigung = Cache::remember('reinigung'.auth()->id(), $expire, function () {
            // Reinigungsdienst der ganzen Familie (FamilyResolver)
            return Reinigung::query()
                ->whereIn('users_id', auth()->user()->familyUserIds())
                ->whereBetween('datum', [Carbon::now()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])
                ->orderBy('datum')
                ->first();
        });

        $view->with('reinigung', $reinigung);
    }
}
