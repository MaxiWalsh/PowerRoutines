<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RoutineAssignment extends Model
{
    protected $fillable = [
        'routine_id', 'assigned_by', 'assignable_type', 'assignable_id',
        'start_date', 'end_date', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** Polimórfico: puede ser User, Gym o Profile */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }
}
