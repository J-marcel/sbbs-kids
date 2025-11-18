<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_model_id',
        'student_id', // ✅ AJOUT
        'subscription_plan_id',
        'transaction_id',
        'payment_group_id', // ✅ AJOUT
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    // Vérifier si l'abonnement est actif
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->end_date
            && $this->end_date->isFuture();
    }

    // Activer l'abonnement
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths($this->plan->duration_months),
        ]);
    }

    // Scope pour les abonnements actifs
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '>', now());
    }

    // Scope pour regrouper par paiement
    public function scopeByPaymentGroup($query, string $paymentGroupId)
    {
        return $query->where('payment_group_id', $paymentGroupId);
    }
}
