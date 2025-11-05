<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentModel extends Model
{
    protected $table = 'parent_models';

    protected $fillable = [
        'name',
        'avatar',
        'email',
        'user_id',
        'gender',
        'phone_number',
        'number_whatsapp',
        'is_main',
        'is_child',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function getAvatarUrlAttribute(): string | null
    {
        return ImageHelpers::pathToUrl($this->avatar);
    }

    protected $casts = [
        'is_main' => 'boolean',
        'is_child' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // Obtenir l'abonnement actif
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->active()
            ->first();
    }

    // Vérifier si a un abonnement actif
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->active()
            ->exists();
    }

    // Obtenir les students par age_group
    public function studentsByAgeGroup(string $ageGroup)
    {
        return $this->students()
            ->where('age_group', $ageGroup)
            ->get();
    }
}
