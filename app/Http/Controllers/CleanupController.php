<?php

namespace App\Http\Controllers;

use App\Model\ActiveDisease;
use App\Model\krankmeldungen;
use App\Model\Losung;
use App\Model\Mail;
use App\Model\Notification;
use App\Model\Schickzeiten;
use App\Model\Vertretung;
use App\Model\VertretungsplanAbsence;
use App\Model\VertretungsplanNews;
use App\Model\VertretungsplanWeek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class CleanupController extends Controller
{
    public function clean_up()
    {
        $admins = Role::query()->where('name', 'Administrator')->first()->users()->get();


        try {
            // Delete notifications older than 10 days
            Notification::query()->where('created_at', '<', now()->subDays(10))->delete();#
            // Delete read notifications older than 3 days
            Notification::query()->where('created_at', '<', now()->subDays(3))->where('read', 1)->delete();
            Notification::query()->whereNull('created_at')->update(['created_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Error while cleaning up notifications: ' . $e->getMessage());
            foreach ($admins as $admin) {
                $notification = new Notification([
                    'user_id' => $admin->id,
                    'title' => 'Clean up Error',
                    'message' => 'Error while cleaning up notifications ',
                    'type' => 'error',
                ]);
                $notification->save();
            }
        }

        try {
            //Delete Mail Logs older than 14 days
            Mail::query()->where('created_at', '<', now()->subDays(14))->withoutGlobalScopes()->delete();
        } catch (\Exception $e) {
            Log::error('Error while cleaning up Mail: ' . $e->getMessage());
            foreach ($admins as $admin) {
                $notification = new Notification([
                    'user_id' => $admin->id,
                    'title' => 'Clean up Error',
                    'message' => 'Error while cleaning up Mails ',
                    'type' => 'error',
                ]);
                $notification->save();
            }
        }

        try {
            //Delete old Schickzeiten
            $schickzeiten = Schickzeiten::withTrashed()->whereNotNull('deleted_at')->where('deleted_at', '<', now()->subDays(14))->get();
            foreach ($schickzeiten as $schickzeit) {
                $schickzeit->forceDelete();
            }
        } catch (\Exception $e) {
            Log::error('Error while cleaning up Schickzeiten: ' . $e->getMessage());
            foreach ($admins as $admin) {
                $notification = new Notification([
                    'user_id' => $admin->id,
                    'title' => 'Clean up Error',
                    'message' => 'Error while cleaning up Schickzeiten ',
                    'type' => 'error',
                ]);
                $notification->save();
            }
        }

        try {
            //delete Losungen older than 1 day
            Losung::whereDate('date', '<', now()->subDays(1))->delete();

        } catch (\Exception $e) {
            Log::error('Error while cleaning up Losungen: ' . $e->getMessage());
            foreach ($admins as $admin) {
                $notification = new Notification([
                    'user_id' => $admin->id,
                    'title' => 'Clean up Error',
                    'message' => 'Error while cleaning up Losungen ',
                    'type' => 'error',
                ]);
                $notification->save();
            }
        }

        try {
            //delete old activDisease entries older than 30 day
            ActiveDisease::whereDate('end', '<', now()->subDays(30))->delete();

        } catch (\Exception $e) {
            Log::error('Error while cleaning up ActiveDisease: ' . $e->getMessage());
            foreach ($admins as $admin) {
                $notification = new Notification([
                    'user_id' => $admin->id,
                    'title' => 'Clean up Error',
                    'message' => 'Error while cleaning up ActiveDisease ',
                    'type' => 'error',
                ]);
                $notification->save();
            }
        }

        try {
            //delete Vertretung entries older than 7 days
            Vertretung::whereDate('date', '<', now()->subDays(7))->delete();
            VertretungsplanAbsence::whereDate('end_date', '<', now()->subDays(7))->delete();
            VertretungsplanNews::whereDate('ende', '<', now()->subDays(7))->delete();
            VertretungsplanWeek::whereDate('week', '<', now()->subDays(7))->delete();
        } catch (\Exception $e) {
            Log::error('Error while cleaning up Vertretung: ' . $e->getMessage());
            foreach ($admins as $admin) {
                $notification = new Notification([
                    'user_id' => $admin->id,
                    'title' => 'Clean up Error',
                    'message' => 'Error while cleaning up Vertretung ',
                    'type' => 'error',
                ]);
                $notification->save();
            }
        }

        try {
            //delete old Schickzeiten
            $schickzeiten = Schickzeiten::withTrashed()->where('deleted_at', '<', now()->subMonth(2))->forceDelete();

        } catch (\Exception $e) {
            Log::error('Error while cleaning up Schickzeiten: ' . $e->getMessage());
            foreach ($admins as $admin) {
                $notification = new Notification([
                    'user_id' => $admin->id,
                    'title' => 'Clean up Error',
                    'message' => 'Error while cleaning up Schickzeiten ',
                    'type' => 'error',
                ]);
                $notification->save();
            }
        }

        try {
            //delete krankmeldungen older than last august

            if (now()->month < 8) {
                $date = now()->subYear()->month(8)->day(1)->hour(0)->minute(0)->second(0);
            } else {
                $date = now()->month(8)->day(1)->hour(0)->minute(0)->second(0);
            }

            krankmeldungen::withTrashed()->where('created_at', '<', $date)->forceDelete();

        } catch (\Exception $e) {
            Log::error('Error while cleaning up Krankmeldungen: ' . $e->getMessage());
            foreach ($admins as $admin) {
                $notification = new Notification([
                    'user_id' => $admin->id,
                    'title' => 'Clean up Error',
                    'message' => 'Error while cleaning up Krankmeldungen ',
                    'type' => 'error',
                ]);
                $notification->save();
            }
        }



        foreach ($admins as $admin) {
            $notification = new Notification([
                'user_id' => $admin->id,
                'title' => 'Clean up Success',
                'message' => 'Clean up was successful.',
                'type' => 'success',
            ]);
            $notification->save();
        }
    }
}
