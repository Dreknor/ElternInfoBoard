<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Rueckmeldungen
 * @package App\Model
 */
class Rueckmeldungen extends Model
{
    /**
     * @var string
     */
    protected $table = "rueckmeldungen";

    /**
     * @var array
     */
    protected $fillable = ['posts_id', 'empfaenger', 'ende', 'text', 'pflicht'];
    /**
     * @var array
     */
    protected $visible = ['posts_id', 'empfaenger', 'ende', 'text', 'pflicht'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'ende'];

    /**
     * @var array
     */
    protected $casts = [
        'pflicht' => "boolean"
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
