<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'age_group',
        'duration_months',
        'price',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // Scope pour les plans actifs
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope par groupe d'âge
    public function scopeForAgeGroup($query, string $ageGroup)
    {
        return $query->where('age_group', $ageGroup);
    }
}
