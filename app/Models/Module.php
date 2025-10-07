<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';
    protected $fillable = [
        'name',
        'applications',
        'support_id',
        'image',
        'level_id',
        'admin_id',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): string | null
    {
        return ImageHelpers::pathToUrl($this->image);
    }

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

    public function supports()
    {
        return $this->hasMany(Support::class);
    }
}
