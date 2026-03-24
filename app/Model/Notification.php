<?php

namespace App\Model;

use App\Notifications\Push;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = ['type', 'user_id', 'title', 'message', 'icon', 'url', 'read', 'important'];

    protected $visible = ['id', 'type', 'user_id', 'title', 'message', 'icon', 'url', 'read', 'important'];

    protected function casts(): array
    {
        return [
            'read' => 'boolean',
            'important' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #[Scope]
    protected function unread($query)
    {
        return $query->where('read', false);
    }

    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            try {
                // Sende WebPush-Notification nur wenn Benutzer WebPush-Subscriptions hat
                if ($notification->user && $notification->user->pushSubscriptions()->exists()) {
                    $notification->user->notify(new Push($notification->title, $notification->message));
                }
            } catch (\Exception $e) {
                \Log::warning("Fehler beim Senden der WebPush-Notification für Benutzer {$notification->user_id}: " . $e->getMessage());
            }
        });
    }
}
