<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SearchLog
 *
 * Eigenständige Protokollierung der Suchanfragen (unabhängig von den
 * System-Logs), damit Suchstatistiken ausgewertet und separat bereinigt
 * werden können, ohne die allgemeinen Logs zu belasten.
 */
class SearchLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'search_logs';

    protected $fillable = [
        'user_id',
        'search_term',
        'nachrichten_count',
        'seiten_count',
        'results_count',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'nachrichten_count' => 'integer',
            'seiten_count' => 'integer',
            'results_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithoutResults($query)
    {
        return $query->where('results_count', 0);
    }

    public function scopeWithResults($query)
    {
        return $query->where('results_count', '>', 0);
    }
}
