<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    protected $fillable = [
        'title',
        'educational_objective',
        'required_equipment',
        'price',
        'activity_schedule',
        'course_id',
        'admin_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(WorkshopPurchase::class);
    }

    // Vérifier si un student a déjà acheté ce workshop
    public function isPurchasedBy(Student $student): bool
    {
        return $this->purchases()
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->exists();
    }

    // Obtenir le nombre d'achats pour ce workshop
    public function getPurchasesCount(): int
    {
        return $this->purchases()->completed()->count();
    }
}
