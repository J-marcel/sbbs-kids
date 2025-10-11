<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';
    protected $fillable = [
        'name',
        'applications',
        'support_id',
        'level_id',
        'admin_id',
    ];


    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function support()
    {
        return $this->belongsTo(Support::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
    
}
