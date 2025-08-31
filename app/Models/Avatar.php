<?php

namespace App\Models;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avatar extends Model
{
    protected $fillable = [
        'admin_id',
        'avatar',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function getAvatarUrlAttribute(): string | null
    {
        return ImageHelpers::pathToUrl($this->avatar);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
