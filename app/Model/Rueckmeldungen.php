<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 * Class Rueckmeldungen
 */
class Rueckmeldungen extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'rueckmeldungen';

    /**
     * @var array
     */
    protected $fillable = ['post_id', 'empfaenger', 'ende', 'text', 'pflicht', 'type', 'commentable', 'max_answers', 'multiple', 'liste_id', 'terminliste_start_date', 'terminliste_end_date'];

    /**
     * @var array
     */
    protected $visible = ['post_id', 'empfaenger', 'ende', 'text', 'pflicht', 'type', 'max_answers', 'multiple', 'liste_id', 'terminliste_start_date', 'terminliste_end_date', 'commentable'];

    /**
     * @var array
     */
    protected $appends = ['active'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ende' => 'datetime',
            'pflicht' => 'boolean',
            'commentable' => 'boolean',
            'multiple' => 'boolean',
            'terminliste_start_date' => 'date',
            'terminliste_end_date' => 'date',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function userRueckmeldungen(): HasMany
    {
        /*
        if ($this->type == 'abfrage') {
            return $this->hasManyTh(AbfrageAntworten::class, 'rueckmeldung_id');
        }
*/
        return $this->hasMany(UserRueckmeldungen::class, 'post_id', 'post_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(AbfrageOptions::class, 'rueckmeldung_id');
    }


    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($rueckmeldung) {
            $post = $rueckmeldung->post;
            if ($post && $rueckmeldung->ende && ($post->archiv_ab === null || $rueckmeldung->ende->greaterThan($post->archiv_ab))) {
                $post->update([
                    'archiv_ab' => $rueckmeldung->ende,
                ]);
            }
        });

        static::updated(function ($rueckmeldung) {
            $post = $rueckmeldung->post;
            if ($post && $rueckmeldung->ende && ($post->archiv_ab === null || $rueckmeldung->ende->greaterThan($post->archiv_ab))) {
                $post->update([
                    'archiv_ab' => $rueckmeldung->ende,
                ]);
            }
        });
    }

    public function liste(): BelongsTo
    {
        return $this->belongsTo(Liste::class, 'liste_id');
    }

    public function isTerminliste(): bool
    {
        return $this->type === 'terminliste';
    }

    /**
     * Determine if the feedback is still active (deadline has not passed).
     *
     * @return bool
     */
    public function getActiveAttribute(): bool
    {
        return $this->ende && $this->ende->isFuture();
    }
}
