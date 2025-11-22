<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'has_active_subscription', // ✅ Ajouté
        'subscription_status', // ✅ Ajouté
    ];

    protected $hidden = [
        'pin_code',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_model_id');
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class, 'avatar_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function workshopPurchases(): HasMany
    {
        return $this->hasMany(WorkshopPurchase::class);
    }

    public function purchasedWorkshops()
    {
        return $this->belongsToMany(Workshop::class, 'workshop_purchases')
            ->wherePivot('status', 'completed')
            ->withPivot(['amount_paid', 'purchased_at', 'status'])
            ->withTimestamps();
    }

    // ==========================================
    // ACCESSORS (ATTRIBUTS CALCULÉS)
    // ==========================================

    /**
     * URL de l'avatar
     */
    public function getAvatarUrlAttribute(): string | null
    {
        return ImageHelpers::pathToUrl($this->avatar);
    }

    /**
     * Vérifier si a un abonnement actif
     * Accessible via: $student->has_active_subscription
     */
    public function getHasActiveSubscriptionAttribute(): bool
    {
        return $this->hasActiveSubscription();
    }

    /**
     * Statut complet de l'abonnement
     * Accessible via: $student->subscription_status
     */
    public function getSubscriptionStatusAttribute(): array
    {
        $activeSubscription = $this->activeSubscription();

        if (!$activeSubscription) {
            return [
                'has_subscription' => false,
                'status' => 'no_subscription',
                'message' => 'Aucun abonnement actif',
            ];
        }

        $daysRemaining = $activeSubscription->end_date
            ? now()->diffInDays($activeSubscription->end_date, false)
            : null;

        return [
            'has_subscription' => true,
            'status' => 'active',
            'subscription_id' => $activeSubscription->id,
            'plan_id' => $activeSubscription->plan->id,
            'plan_name' => $activeSubscription->plan->name,
            'plan_price' => (float) $activeSubscription->plan->price,
            'duration_months' => $activeSubscription->plan->duration_months,
            'start_date' => $activeSubscription->start_date?->toDateString(),
            'end_date' => $activeSubscription->end_date?->toDateString(),
            'days_remaining' => $daysRemaining,
            'is_expiring_soon' => $daysRemaining !== null && $daysRemaining <= 7 && $daysRemaining > 0,
            'is_expired' => $daysRemaining !== null && $daysRemaining < 0,
            'message' => $this->getSubscriptionMessage($activeSubscription, $daysRemaining),
        ];
    }

    // ==========================================
    // MÉTHODES D'ABONNEMENT
    // ==========================================

    /**
     * Vérifier si l'élève a un abonnement actif
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->exists();
    }

    /**
     * Obtenir l'abonnement actif
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->first();
    }

    /**
     * Obtenir tous les abonnements actifs (au cas où il y en aurait plusieurs)
     */
    public function activeSubscriptions()
    {
        return $this->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->get();
    }

    /**
     * Obtenir l'historique des abonnements
     */
    public function subscriptionHistory()
    {
        return $this->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Vérifier si l'abonnement expire bientôt (dans les 7 jours)
     */
    public function subscriptionExpiringSoon(): bool
    {
        $activeSubscription = $this->activeSubscription();

        if (!$activeSubscription || !$activeSubscription->end_date) {
            return false;
        }

        $daysRemaining = now()->diffInDays($activeSubscription->end_date, false);

        return $daysRemaining <= 7 && $daysRemaining > 0;
    }

    /**
     * Nombre de jours restants sur l'abonnement
     */
    public function subscriptionDaysRemaining(): ?int
    {
        $activeSubscription = $this->activeSubscription();

        if (!$activeSubscription || !$activeSubscription->end_date) {
            return null;
        }

        return now()->diffInDays($activeSubscription->end_date, false);
    }

    /**
     * Message personnalisé selon le statut de l'abonnement
     */
    private function getSubscriptionMessage($subscription, ?int $daysRemaining): string
    {
        if ($daysRemaining === null) {
            return 'Abonnement actif';
        }

        if ($daysRemaining < 0) {
            return 'Abonnement expiré';
        }

        if ($daysRemaining === 0) {
            return 'Abonnement expire aujourd\'hui';
        }

        if ($daysRemaining === 1) {
            return 'Abonnement expire demain';
        }

        if ($daysRemaining <= 7) {
            return "Abonnement expire dans {$daysRemaining} jours";
        }

        return "Abonnement actif ({$daysRemaining} jours restants)";
    }

    // ==========================================
    // MÉTHODES DE WORKSHOPS
    // ==========================================

    /**
     * Vérifier si le student a acheté un workshop
     */
    public function hasPurchasedWorkshop(Workshop $workshop): bool
    {
        return $this->workshopPurchases()
            ->where('workshop_id', $workshop->id)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Obtenir tous les workshops achetés et complétés
     */
    public function completedWorkshops()
    {
        return $this->workshopPurchases()
            ->with('workshop')
            ->where('status', 'completed')
            ->get();
    }

    /**
     * Nombre total de workshops achetés
     */
    public function workshopsPurchasedCount(): int
    {
        return $this->workshopPurchases()
            ->where('status', 'completed')
            ->count();
    }

    // ==========================================
    // AUTRES MÉTHODES
    // ==========================================

    /**
     * Vérifier le code PIN
     */
    public function checkPin($pin): bool
    {
        return Hash::check($pin, $this->pin_code);
    }

    /**
     * Définir le code PIN (avec hash)
     */
    public function setPin(string $pin): void
    {
        $this->pin_code = Hash::make($pin);
        $this->save();
    }

    /**
     * Obtenir le label du groupe d'âge
     */
    public function getAgeGroupLabelAttribute(): string
    {
        return match($this->age_group) {
            '4-7' => 'Enfant (4-7 ans)',
            '8-12' => 'Pré-adolescent (8-12 ans)',
            '13-17' => 'Adolescent (13-17 ans)',
            default => $this->age_group,
        };
    }

    /**
     * Scope : Students avec abonnement actif
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->whereHas('subscriptions', function($q) {
            $q->where('status', 'active')
              ->where('end_date', '>', now());
        });
    }

    /**
     * Scope : Students sans abonnement actif
     */
    public function scopeWithoutActiveSubscription($query)
    {
        return $query->whereDoesntHave('subscriptions', function($q) {
            $q->where('status', 'active')
              ->where('end_date', '>', now());
        });
    }

    /**
     * Scope : Students dont l'abonnement expire bientôt
     */
    public function scopeSubscriptionExpiringSoon($query, int $days = 7)
    {
        return $query->whereHas('subscriptions', function($q) use ($days) {
            $q->where('status', 'active')
              ->where('end_date', '>', now())
              ->where('end_date', '<=', now()->addDays($days));
        });
    }
}
