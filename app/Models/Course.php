<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'courses';
    protected $fillable = [
        'title',
        'libelle',
        'objectif',
        'guide_for_parents',
        'introduction',
        'module_id',
        'admin_id',
    ];

    // protected $appends = [
    //     'video_url',
    // ];

    // public function getVideoUrlAttribute(): string | null
    // {
    //     // Si c'est déjà une URL complète, la retourner telle quelle
    //     if (filter_var($this->video, FILTER_VALIDATE_URL)) {
    //         return $this->video;
    //     }

    //     // Sinon, utiliser le helper (au cas où c'est un chemin local)
    //     return ImageHelpers::pathToUrl($this->video);
    // }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
    public function supports()
    {
        return $this->hasMany(Support::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
