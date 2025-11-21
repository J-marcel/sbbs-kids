<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_model_id',
        'name',
        'avatar',
        'gender',
        'age_group',
        'age',
        'phone_number',
        'number_whatsapp',
        'pin_code',
        'avatar_id',
        'role_id',

    ];

    protected $appends = [
        'avatar_url',
    ];

    public function getAvatarUrlAttribute(): string | null
    {
        return ImageHelpers::pathToUrl($this->avatar);
    }
    protected $hidden = [
        'pin_code',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_model_id');
    }

    public function checkPin($pin)
    {
        return Hash::check($pin, $this->pin_code);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class, 'avatar_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }


    // ✅ Changer de BelongsToMany à HasMany
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }


    // Vérifier si l'élève a un abonnement actif
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->exists();
    }

    // Obtenir l'abonnement actif
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->first();
    }


    public function workshopPurchases(): HasMany
    {
        return $this->hasMany(WorkshopPurchase::class);
    }

    // Vérifier si le student a acheté un workshop
    public function hasPurchasedWorkshop(Workshop $workshop): bool
    {
        return $this->workshopPurchases()
            ->where('workshop_id', $workshop->id)
            ->where('status', 'completed')
            ->exists();
    }

    // Obtenir tous les workshops achetés
    public function purchasedWorkshops()
    {
        return $this->belongsToMany(Workshop::class, 'workshop_purchases')
            ->wherePivot('status', 'completed')
            ->withPivot(['amount_paid', 'purchased_at', 'status'])
            ->withTimestamps();
    }
}
