<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'gender' => $this->gender,
            'age_group' => $this->age_group,
            'phone_number' => $this->phone_number,
            'number_whatsapp' => $this->number_whatsapp,
            'has_active_subscription' => $this->hasActiveSubscription(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
