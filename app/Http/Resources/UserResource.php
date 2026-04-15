<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->getRoleNames()->first(),
            'avatar'     => $this->avatar,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender'     => $this->gender,
            'weight_kg'  => $this->weight_kg,
            'height_cm'  => $this->height_cm,
            'plan'       => $this->plan ?? 'free',
            'profiles'   => ProfileResource::collection($this->whenLoaded('profiles')),
            'gyms'       => GymResource::collection($this->whenLoaded('gyms')),
            'owned_gym'  => new GymResource($this->whenLoaded('ownedGym')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
