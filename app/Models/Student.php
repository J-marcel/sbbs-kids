<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_model_id',
        'phone_number',
        'number_whatsapp',
        'gender',
        'age_group',
        'pin_code',

    ];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class);
    }
}
