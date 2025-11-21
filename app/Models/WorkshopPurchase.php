<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkshopPurchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_model_id',
        'student_id',
        'workshop_id',
        'transaction_id',
        'payment_group_id',
        'status',
        'amount_paid',
        'payment_method',
        'cinetpay_data',
        'purchased_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'cinetpay_data' => 'array',
        'purchased_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_model_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    // Marquer l'achat comme complété
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'purchased_at' => now(),
        ]);
    }

    // Vérifier si l'achat est complété
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // Scope pour les achats complétés
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Scope pour les achats en attente
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope par groupe de paiement
    public function scopeByPaymentGroup($query, string $paymentGroupId)
    {
        return $query->where('payment_group_id', $paymentGroupId);
    }
}
