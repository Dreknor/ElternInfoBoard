<?php

namespace App\Model;

use App\Notifications\Push;
use App\Services\Push\NativePushService;
use App\Services\Push\PushTarget;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = ['type', 'user_id', 'title', 'message', 'icon', 'url', 'read', 'important'];

    // created_at wird für die Anzeige in der App benötigt (B-05).
    protected $visible = ['id', 'type', 'user_id', 'title', 'message', 'icon', 'url', 'read', 'important', 'created_at'];

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

    /** Ziel in der App, z. B. ['type' => 'post', 'id' => 12]. */
    public function target(): ?array
    {
        return PushTarget::fromUrl($this->url);
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

            NativePushService::dispatch(
                [$notification->user_id],
                $notification->title,
                $notification->message,
                $notification->url,
                $notification->type ?? 'info'
            );
        });
    }
}
