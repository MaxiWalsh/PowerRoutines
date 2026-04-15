<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by', 'name', 'description',
        'muscle_group', 'equipment', 'video_url', 'photo_url', 'is_global',
    ];

    protected $casts = ['is_global' => 'boolean'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class, 'block_exercises')
                    ->withPivot(['sets', 'reps', 'duration_sec', 'rest_sec', 'order', 'notes'])
                    ->withTimestamps();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }
}
