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
    protected $fillable = ['post_id', 'empfaenger', 'ende', 'text', 'pflicht', 'type', 'commentable'];
    /**
     * @var array
     */
    protected $visible = ['post_id', 'empfaenger', 'ende', 'text', 'pflicht', 'type'];

    /**
     * @var array
     */

    /**
     * @var array
     */
    protected $casts = [
        'ende' => 'datetime',
        'pflicht' => 'boolean',
        'commentable' => 'boolean',
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
        return $this->hasMany(UserRueckmeldungen::class, 'post_id', 'post_id');
    }
}
