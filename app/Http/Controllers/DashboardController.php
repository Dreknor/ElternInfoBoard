<?php

namespace App\Http\Controllers;

use App\Model\ActiveDisease;
use App\Model\Child;
use App\Model\Losung;
use App\Model\Post;
use App\Model\Termin;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Show the application dashboard.
     *
     * @return View
     */
    public function index()
    {
        // Cache-Schlüssel basierend auf User-ID
        $userId = auth()->id();

        // Hole nur die neuesten 5 Nachrichten mit optimiertem Eager Loading
        if (auth()->user()->can('view all')) {
            $nachrichten = Post::query()
                ->select(['id', 'header', 'news', 'created_at'])
                ->where(function ($query) {
                    $query->whereNull('archiv_ab')
                        ->orWhere('archiv_ab', '>', Carbon::now());
                })
                ->without(['rueckmeldung']) // Deaktiviere automatisches Laden
                ->withExists('rueckmeldung') // Prüfe nur Existenz
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $termine = Termin::query()
                ->select(['id', 'terminname', 'start', 'ende', 'fullDay'])
                ->where('start', '>=', Carbon::today())
                ->orderBy('start')
                ->take(5)
                ->get();

        } else {
            // Cache user groups einmalig
            $userGroupIds = Cache::remember("user_groups_{$userId}", 300, function () use ($userId) {
                // Lade nur IDs, nicht die kompletten Group-Models
                return \DB::table('group_user')
                    ->where('user_id', $userId)
                    ->pluck('group_id')
                    ->toArray();
            });

            // Wenn User keine Gruppen hat, leere Collections zurückgeben
            if (empty($userGroupIds)) {
                $nachrichten = collect();
                $termine = collect();
            } else {
                $nachrichten = Post::query()
                    ->select(['posts.id', 'posts.header', 'posts.news', 'posts.created_at'])
                    ->where('posts.released', 1)
                    ->where(function ($query) {
                        $query->whereNull('posts.archiv_ab')
                            ->orWhere('posts.archiv_ab', '>', Carbon::now());
                    })
                    ->join('group_post', 'posts.id', '=', 'group_post.post_id')
                    ->whereIn('group_post.group_id', $userGroupIds)
                    ->without(['rueckmeldung']) // Deaktiviere automatisches Laden
                    ->withExists('rueckmeldung') // Prüfe nur Existenz
                    ->orderBy('posts.created_at', 'desc')
                    ->distinct()
                    ->take(5)
                    ->get();

                $termine = Termin::query()
                    ->select(['termine.id', 'termine.terminname', 'termine.start', 'termine.ende', 'termine.fullDay'])
                    ->where('termine.start', '>=', Carbon::today())
                    ->join('group_termine', 'termine.id', '=', 'group_termine.termin_id')
                    ->whereIn('group_termine.group_id', $userGroupIds)
                    ->orderBy('termine.start')
                    ->distinct()
                    ->take(5)
                    ->get();
            }
        }

        // Hole die heutige Losung (nur notwendige Felder)
        $losung = Losung::select(['date', 'Losungstext', 'Losungsvers', 'Lehrtext', 'Lehrtextvers'])
            ->whereDate('date', Carbon::today())
            ->first();

        // Hole die Kinder des Benutzers mit optimiertem Eager Loading
        $careChildren = auth()->user()->children_rel()
            ->select(['children.id', 'children.first_name', 'children.last_name', 'children.group_id'])
            ->care()
            ->whereHas('parents', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->with([
                'group:id,name', // Nur ID und Name der Gruppe
                'checkIns' => function ($query) {
                    // Nur heutige CheckIns laden
                    $query->select(['id', 'child_id', 'date', 'checked_in', 'checked_out', 'should_be', 'updated_at'])
                        ->whereDate('date', Carbon::today());
                },
                'krankmeldungen' => function ($query) {
                    // Nur aktuelle Krankmeldungen laden
                    $query->select(['id', 'child_id', 'start', 'ende'])
                        ->whereDate('start', '<=', Carbon::today())
                        ->whereDate('ende', '>=', Carbon::today());
                },
                'schickzeiten' => function ($query) {
                    // Nur heutige Schickzeiten laden
                    $query->select(['id', 'child_id', 'type', 'specific_date', 'time', 'time_ab', 'time_spaet'])
                        ->where('specific_date', Carbon::today())
                        ->orderBy('specific_date', 'desc');
                }
            ])
            ->orderBy('first_name')
            ->get();

        // Aktive meldepflichtige Erkrankungen abrufen
        $activeDiseases = Cache::remember('active_diseases', 60 * 5, function () {
            return ActiveDisease::query()
                ->select(['id', 'disease_id', 'start', 'end', 'active'])
                ->where('active', true)
                ->whereDate('end', '>=', Carbon::now())
                ->with('disease:id,name')
                ->get();
        });

        // Prüfe auf offene Anwesenheitsabfragen für die Kinder des Benutzers
        $openAttendanceSurveys = false;
        if ($careChildren->count() > 0) {
            $childIds = $careChildren->pluck('id')->toArray();

            // Cache den Check für offene Surveys
            $openAttendanceSurveys = Cache::remember("open_surveys_{$userId}", 60, function () use ($childIds) {
                $openSurveys = \App\Model\ChildCheckIn::query()
                    ->whereIn('child_id', $childIds)
                    ->where('should_be', false)
                    ->where('checked_in', false)
                    ->where('checked_out', false)
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->whereNotNull('lock_at')
                                ->where('lock_at', '>=', Carbon::today());
                        })->orWhere(function ($q) {
                            $q->whereNull('lock_at')
                                ->where('date', '>', Carbon::today());
                        });
                    })
                    ->exists(); // exists() ist schneller als count() > 0

                return $openSurveys;
            });
        }

        return view('dashboard.index', [
            'nachrichten' => $nachrichten,
            'termine' => $termine,
            'losung' => $losung,
            'datum' => Carbon::now(),
            'careChildren' => $careChildren,
            'activeDiseases' => $activeDiseases,
            'openAttendanceSurveys' => $openAttendanceSurveys,
        ]);
    }
}
