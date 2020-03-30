<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Rueckmeldungen
 * @package App\Model
 */
class Rueckmeldungen extends Model
{
    use SoftDeletes;
    /**
     * @var string
     */
    protected $table = "rueckmeldungen";

    /**
     * @var array
     */
    protected $fillable = ['posts_id', 'empfaenger', 'ende', 'text', 'pflicht','type', 'commentable'];
    /**
     * @var array
     */
    protected $visible = ['posts_id', 'empfaenger', 'ende', 'text', 'pflicht', 'type'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'ende'];

    /**
     * @var array
     */
    protected $casts = [
        'pflicht' => "boolean",
        'commentable' => "boolean",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post(){
        return $this->belongsTo(Posts::class, 'posts_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userRueckmeldungen () {
        return $this->hasMany(UserRueckmeldungen::class, 'posts_id', 'posts_id');
    }



}
