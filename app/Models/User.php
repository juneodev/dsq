<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Manipulations;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Append computed attributes to model array/json.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar',
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
            'password' => 'hashed',
        ];
    }

    /**
     * Register media collections for the user model.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg','image/png','image/webp'])
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->fit(Manipulations::FIT_CROP, 200, 200)
                    ->nonQueued();
            });
    }

    /**
     * Accessor: avatar url (thumb conversion if available).
     */
    public function getAvatarAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('avatar', 'thumb');
        return $url !== '' ? $url : null;
    }
}
