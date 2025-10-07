<?php

namespace App\Model;

use App\Observers\ChildMandateObserver;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

#[ObservedBy([ChildMandateObserver::class])]
class ChildMandate extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'child_mandates';

    protected $fillable = [
        'child_id',
        'mandate_name',
        'mandate_description',
        'created_by',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class, 'child_id');
    }
}
