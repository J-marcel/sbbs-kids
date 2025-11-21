<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_model_id,
            'parent_name' => $this->parent->name,
            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'age_group' => $this->student->age_group,
            ],
            'workshop' => new WorkshopResource($this->whenLoaded('workshop')),
            'transaction_id' => $this->transaction_id,
            'payment_group_id' => $this->payment_group_id,
            'status' => $this->status,
            'amount_paid' => (float) $this->amount_paid,
            'payment_method' => $this->payment_method,
            'purchased_at' => $this->purchased_at?->toISOString(),
            'is_completed' => $this->isCompleted(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
