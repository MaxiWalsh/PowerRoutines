<?php

namespace App\Services;

use App\Models\ExerciseLog;
use App\Models\User;

class ExerciseLogService
{
    public function log(User $user, array $data): ExerciseLog
    {
        return ExerciseLog::create([
            'user_id'     => $user->id,
            'exercise_id' => $data['exercise_id'],
            'routine_id'  => $data['routine_id'] ?? null,
            'block_id'    => $data['block_id'] ?? null,
            'session_id'  => $data['session_id'] ?? null,
            'weight_kg'   => $data['weight_kg'],
            'reps'        => $data['reps'],
            'sets'        => $data['sets'],
            'notes'       => $data['notes'] ?? null,
            'logged_at'   => $data['logged_at'] ?? now(),
        ]);
    }

    public function historyForExercise(User $user, int $exerciseId)
    {
        return ExerciseLog::where('user_id', $user->id)
                          ->where('exercise_id', $exerciseId)
                          ->orderByDesc('logged_at')
                          ->paginate(20);
    }

    public function statsForExercise(User $user, int $exerciseId): array
    {
        $logs = ExerciseLog::where('user_id', $user->id)
                           ->where('exercise_id', $exerciseId)
                           ->orderByDesc('logged_at');

        $lastLog = (clone $logs)->first();
        $maxLog  = (clone $logs)->orderByDesc('weight_kg')->first();

        return [
            'last_weight'   => $lastLog?->weight_kg,
            'last_logged_at'=> $lastLog?->logged_at,
            'higher_weight' => $maxLog?->weight_kg,
            'total_sessions'=> (clone $logs)->count(),
        ];
    }
}
