<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'educational_objective' => $this->educational_objective,
            'required_equipment' => $this->required_equipment,
            'price' => (float) $this->price,
            'activity_schedule' => $this->activity_schedule,
            'course' => $this->whenLoaded('course'),
            'purchases_count' => $this->when(
                $this->relationLoaded('purchases'),
                fn() => $this->purchases->count()
            ),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
