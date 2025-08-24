<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentModel extends Model
{
    protected $table = 'parent_models';

    protected $fillable = [
        'name',
        'user_id',
        'gender',
        'phone_number',
        'number_whatsapp',
        'is_main',
        'is_child',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }
}
