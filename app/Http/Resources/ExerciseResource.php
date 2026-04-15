<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'description'  => $this->description,
            'muscle_group' => $this->muscle_group,
            'equipment'    => $this->equipment,
            'video_url'    => $this->video_url,
            'photo_url'    => $this->photo_url,
            'is_global'    => $this->is_global,
            'created_by'   => $this->created_by,
        ];
    }
}
