<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
