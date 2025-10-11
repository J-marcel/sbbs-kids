<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $table = 'activities';

    protected $fillable = [
        'title',
        'libelle',
        'description',
        'course_id',
        'admin_id',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
