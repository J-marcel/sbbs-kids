<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Helpers\ImageHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'otp',
        'email_verified_at',
        'otp_expires_at',
        'is_otp_verified',
        'phone_number',
        'number_whatsapp',
        'password',
        'avatar',
        'compte_active',
        'role_id',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): string | null
    {
        return ImageHelpers::pathToUrl($this->avatar);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'otp_expires_at',
        'is_otp_verified',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'otp_expires_at'    => 'datetime',
            'compte_active'     => 'bool',
        ];
    }

    // Générer un OTP clair, l'enregistrer haché, et le retourner
    public function generateOTP(): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'otp'             => Hash::make($otp),
            'otp_expires_at'  => now()->addMinutes(15),
            'is_otp_verified' => false,
        ]);

        return $otp; // Code clair à envoyer à l'utilisateur
    }

    // Vérifier l'OTP entré par l'utilisateur
    public function verifyOTP(string $otp): bool
    {
        if (! $this->otp || $this->otp_expires_at < now() || $this->is_otp_verified) {
            return false;
        }

        if (Hash::check($otp, $this->otp)) {
            $this->update([
                'otp'               => null,
                'otp_expires_at'    => null,
                'is_otp_verified'   => true,
                'email_verified_at' => now(),
            ]);
            return true;
        }

        return false;
    }


    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function parents()
    {
        return $this->hasMany(ParentModel::class);
    }
    public function studentParent()
    {
        return $this->hasOne(ParentModel::class)->where('is_main', true);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function trainer()
    {
        return $this->hasOne(Trainer::class);
    }

}
