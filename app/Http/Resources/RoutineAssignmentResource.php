<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoutineAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'assignable_type' => class_basename($this->assignable_type),
            'assignable_id'  => $this->assignable_id,
            'assigned_by'    => $this->assigned_by,
            'start_date'     => $this->start_date?->toDateString(),
            'end_date'       => $this->end_date?->toDateString(),
            'notes'          => $this->notes,
            'created_at'     => $this->created_at->toDateTimeString(),
        ];
    }
}
