<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
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
     * Get all of the trouble for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trouble(): HasMany
    {
        return $this->hasMany(Trouble::class, 'created_by', 'uuid');
    }

    /**
     * Get all of the confirmed for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function confirmed(): HasMany
    {
        return $this->hasMany(Trouble::class, 'confirmed_by', 'uuid');
    }

    /**
     * Get all of the ip_address for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ip_address(): HasMany
    {
        return $this->hasMany(IpAddress::class, 'user_id', 'uuid');
    }

    /**
     * Get all of the ip_assign for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ip_assign(): HasMany
    {
        return $this->hasMany(IpAssignments::class, 'user_id', 'uuid');
    }
}
