<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RoutineAssignmentResource;

class RoutineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'scope'       => $this->scope,
            'is_template' => $this->is_template,
            'is_active'              => $this->is_active,
            'price'                  => $this->price,
            'is_published'           => $this->is_published,
            'marketplace_description'=> $this->marketplace_description,
            'difficulty'             => $this->difficulty,
            'duration_weeks'         => $this->duration_weeks,
            'days_per_week'          => $this->days_per_week,
            'cover_image'            => $this->cover_image,
            'discipline'             => $this->discipline,
            'target_goals'           => $this->target_goals ?? [],
            'target_level'           => $this->target_level,
            'contraindications'      => $this->contraindications ?? [],
            'purchases_count'        => $this->whenCounted('purchases'),
            'is_purchased'           => $this->when(isset($this->is_purchased), $this->is_purchased),
            'is_own_routine'         => $this->when(isset($this->is_own_routine), $this->is_own_routine),
            'source'                 => $this->when(isset($this->source), $this->source),
            'owner'                  => new UserResource($this->whenLoaded('owner')),
            'blocks'      => BlockResource::collection($this->whenLoaded('days')),
            'assignments' => RoutineAssignmentResource::collection($this->whenLoaded('assignments')),
            'created_at'  => $this->created_at->toDateTimeString(),
            'updated_at'  => $this->updated_at->toDateTimeString(),
        ];
    }
}
