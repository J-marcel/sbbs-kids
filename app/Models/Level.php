<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = [
        'name',
        'image',
        'description',
        'age_group',
        'admin_id',
    ];

    protected $appends =[
        'image_url'
    ];

    public function getImageUrlAttribute(): string | null
    {
        return ImageHelpers::pathToUrl($this->image);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }


}
