<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'order' => $this->order,
            'notes' => $this->notes,

            // Secciones dentro del día (cuando es bloque padre)
            'sections' => BlockResource::collection($this->whenLoaded('sections')),

            // Ejercicios del bloque (cuando es sección)
            'exercises' => $this->whenLoaded('exercises', fn() =>
                $this->exercises->map(fn($ex) => [
                    'id'           => $ex->id,
                    'name'         => $ex->name,
                    'muscle_group' => $ex->muscle_group,
                    'sets'         => $ex->pivot->sets,
                    'reps'         => $ex->pivot->reps,
                    'reps_max'     => $ex->pivot->reps_max,
                    'duration_sec' => $ex->pivot->duration_sec,
                    'rest_sec'     => $ex->pivot->rest_sec,
                    'order'        => $ex->pivot->order,
                    'notes'        => $ex->pivot->notes,
                ])
            ),
        ];
    }
}
