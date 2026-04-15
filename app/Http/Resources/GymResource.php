<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GymResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'description'  => $this->description,
            'logo'         => $this->logo,
            'invite_code'  => $this->when(
                                  $request->user()?->id === $this->trainer_id,
                                  $this->invite_code
                              ), // Solo el trainer ve el código
            'trainer'      => new UserResource($this->whenLoaded('trainer')),
            'profiles'     => ProfileResource::collection($this->whenLoaded('profiles')),
            'students_count' => $this->whenCounted('students'),
            'created_at'   => $this->created_at->toDateTimeString(),
        ];
    }
}
