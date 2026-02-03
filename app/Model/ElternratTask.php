<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElternratTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'assigned_to',
        'created_by',
        'status',
        'priority',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user assigned to the task
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the creator of the task
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }

    /**
     * Check if task is due soon (within 3 days)
     */
    public function isDueSoon(): bool
    {
        return $this->due_date && $this->due_date->isFuture() && $this->due_date->diffInDays() <= 3 && $this->status !== 'completed';
    }

    /**
     * Mark task as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Scope for open tasks
     */
    #[Scope]
    protected function open($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for in progress tasks
     */
    #[Scope]
    protected function inProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for completed tasks
     */
    #[Scope]
    protected function completed($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for overdue tasks
     */
    #[Scope]
    protected function overdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'completed');
    }
}
