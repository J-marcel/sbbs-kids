<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_model_id',  // ✅ Changé de user_id
        'subscription_plan_id',
        'transaction_id',
        'status',
        'start_date',
        'end_date',
        'amount_paid',
        'payment_method',
        'cinetpay_data'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount_paid' => 'decimal:2',
        'cinetpay_data' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_model_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_subscription')
            ->withTimestamps();
    }

    // Vérifier si l'abonnement est actif
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->end_date
            && $this->end_date->isFuture();
    }

    // Activer l'abonnement et attacher les students
    public function activate(array $studentIds = []): void
    {
        $this->update([
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths($this->plan->duration_months),
        ]);

        // Attacher les students sélectionnés
        if (!empty($studentIds)) {
            $this->students()->sync($studentIds);
        }
    }

    // Scope pour les abonnements actifs
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '>', now());
    }
}
