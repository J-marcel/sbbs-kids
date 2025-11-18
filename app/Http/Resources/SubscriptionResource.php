<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_model_id,
            'parent_name' => $this->parent->name,
            'student' => $this->student ? [ // ✅ Un seul student
                'id' => $this->student->id,
                'name' => $this->student->name,
                'age_group' => $this->student->age_group,
                'gender' => $this->student->gender,
            ] : null,
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'transaction_id' => $this->transaction_id,
            'payment_group_id' => $this->payment_group_id,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'amount_paid' => (float) $this->amount_paid,
            'payment_method' => $this->payment_method,
            'is_active' => $this->isActive(),
            'days_remaining' => $this->end_date ? now()->diffInDays($this->end_date, false) : null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
