<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Rueckmeldungen
 */
class Rueckmeldungen extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'rueckmeldungen';

    /**
     * @var array
     */
    protected $fillable = ['post_id', 'empfaenger', 'ende', 'text', 'pflicht', 'type', 'commentable', 'max_answers', 'multiple'];

    /**
     * @var array
     */
    protected $visible = ['post_id', 'empfaenger', 'ende', 'text', 'pflicht', 'type', 'max_answers', 'multiple'];

    /**
     * @var array
     */
    protected $casts = [
        'ende' => 'datetime',
        'pflicht' => 'boolean',
        'commentable' => 'boolean',
        'multiple' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userRueckmeldungen()
    {
        if ($this->type == 'abfrage') {
            return $this->hasMany(AbfrageAntworten::class, 'rueckmeldung_id');
        }

        return $this->hasMany(UserRueckmeldungen::class, 'post_id', 'post_id');
    }

    public function options()
    {
        if ($this->type == 'abfrage') {
            return $this->hasMany(AbfrageOptions::class, 'rueckmeldung_id');
        }

        return null;
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($rueckmeldung) {
            $post = $rueckmeldung->post;
            if ($rueckmeldung->ende->greaterThan($post->archiv_ab)) {
                $post->update([
                    'archiv_ab' => $rueckmeldung->ende,
                ]);
            }
        });
    }
}
