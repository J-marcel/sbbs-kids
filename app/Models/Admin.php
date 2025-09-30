<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'avatar',
        'email',
        'gender',
        'phone_number',
        'number_whatsapp',
        'user_id',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function getAvatarUrlAttribute(): string | null
    {
        return ImageHelpers::pathToUrl($this->avatar);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function avatar()
    {
        return $this->hasOne(Avatar::class);
    }

    public function levels()
    {
        return $this->hasMany(Level::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
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
