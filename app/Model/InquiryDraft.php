<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zwischengespeicherter Entwurf je Konto. Ein Entwurf gilt nicht als Rückmeldung.
 */
class InquiryDraft extends Model
{
    protected $fillable = ['assignment_id', 'user_id', 'answers'];

    protected function casts(): array
    {
        return [
            'answers' => 'json:unicode',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(InquiryAssignment::class, 'assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
