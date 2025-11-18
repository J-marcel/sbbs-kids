<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    protected $fillable = [
        'libelle',
        'admin_id',
        'pdf',
        'video',
        'description',
        'course_id',
        'status'
    ];

    protected $appends = [
        'video_url',
        'pdf_url',
    ];

    public function getVideoUrlAttribute(): string | null
    {
        // Si c'est déjà une URL complète, la retourner telle quelle
        if (filter_var($this->video, FILTER_VALIDATE_URL)) {
            return $this->video;
        }

        // Sinon, utiliser le helper (au cas où c'est un chemin local)
        return ImageHelpers::pathToUrl($this->video);
    }

    public function getPdfUrlAttribute(): string | null
    {
        // // Si c'est déjà une URL complète, la retourner telle quelle
        // if (filter_var($this->pdf, FILTER_VALIDATE_URL)) {
        //     return $this->pdf;
        // }

        // Sinon, utiliser le helper (au cas où c'est un chemin local)
        return ImageHelpers::pathToUrl($this->pdf);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }


}
