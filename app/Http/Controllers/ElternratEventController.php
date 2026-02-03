<?php

namespace App\Http\Controllers;

use App\Mail\EventReminderMail;
use App\Model\ElternratEvent;
use App\Model\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ElternratEventController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view elternrat']);
    }

    /**
     * Display a listing of events
     */
    public function index()
    {
        $events = ElternratEvent::with(['creator', 'attendees.user'])
            ->orderBy('start_time', 'asc')
            ->paginate(20);

        $upcomingEvents = ElternratEvent::where('start_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();

        $pastEvents = ElternratEvent::where('start_time', '<', now())
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();

        return view('elternrat.events.index', compact('events', 'upcomingEvents', 'pastEvents'));
    }

    /**
     * Show form to create new event
     */
    public function create()
    {
        return view('elternrat.events.create');
    }

    /**
     * Store new event
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'send_reminder' => 'boolean',
            'reminder_hours' => 'integer|min:1|max:168',
        ]);

        $event = ElternratEvent::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('elternrat.events.index')->with([
            'type' => 'success',
            'meldung' => 'Termin erstellt',
        ]);
    }

    /**
     * Update attendance status
     */
    public function updateAttendance(Request $request, ElternratEvent $event)
    {
        $validated = $request->validate([
            'status' => 'required|in:accepted,declined,maybe',
            'comment' => 'nullable|string|max:500',
        ]);

        EventAttendee::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $request->user()->id,
            ],
            $validated
        );

        return back()->with([
            'type' => 'success',
            'meldung' => 'Teilnahmestatus aktualisiert',
        ]);
    }

    /**
     * Delete event
     */
    public function destroy(ElternratEvent $event)
    {
        if (auth()->user()->can('delete elternrat file') || $event->created_by === auth()->id()) {
            $event->delete();

            return redirect()->route('elternrat.events.index')->with([
                'type' => 'success',
                'meldung' => 'Termin gelöscht',
            ]);
        }

        return back()->with([
            'type' => 'danger',
            'meldung' => 'Keine Berechtigung',
        ]);
    }

    /**
     * Send reminders for upcoming events
     * Called by scheduled task
     */
    public function sendReminders()
    {
        $now = now();

        // Finde alle Events, die in naher Zukunft stattfinden und Erinnerungen aktiviert haben
        $upcomingEvents = ElternratEvent::where('send_reminder', true)
            ->where('start_time', '>', $now)
            ->get();

        $sentCount = 0;

        foreach ($upcomingEvents as $event) {
            // Berechne Stunden bis zum Event
            $hoursUntilEvent = $now->diffInHours($event->start_time, false);

            // Prüfe ob es Zeit ist, die Erinnerung zu senden
            // Toleranz von +/- 1 Stunde, um sicherzustellen dass wir den Zeitpunkt treffen
            if (abs($hoursUntilEvent - $event->reminder_hours) <= 1) {
                // Hole alle Elternratsmitglieder
                $elternratRole = Role::findByName('Elternrat');
                $permission = Permission::findByName('view elternrat');

                $users = $elternratRole->users->merge($permission->users)->unique('id');

                // Sende Erinnerung an alle Mitglieder
                foreach ($users as $user) {
                    if ($user->email) {
                        try {
                            Mail::to($user->email)->send(
                                new EventReminderMail($event, (int) $hoursUntilEvent)
                            );
                            $sentCount++;
                        } catch (\Exception $e) {
                            Log::error('Event Reminder Mail failed: '.$e->getMessage());
                        }
                    }
                }

                Log::info("Event reminder sent for: {$event->title} ({$sentCount} emails)");
            }
        }

        return $sentCount;
    }
}
