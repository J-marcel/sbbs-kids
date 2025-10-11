<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    protected $table = 'workshops';
    protected $fillable = [
        'title',
        'educational_objective',
        'required_equipment',
        'activity_schedule',
        'course_id',
        'admin_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
