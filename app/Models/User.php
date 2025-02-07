<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
class User extends Authenticatable implements MustVerifyEmail, Wallet
{
    use HasApiTokens, HasFactory, Notifiable, HasWallet;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_image_path'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function scopeWithAll($query){
        return $query->with('doctor',function($q){
            $q->withAll();
        })->with('clinic',function($q){
            $q->withAll();
        })->with('patient',function($q){
            $q->withAll();
        });
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }
    public function clinic()
    {
        return $this->hasOne(Clinic::class);
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_code', 'id', 'role_code');
    }

    public function social_accounts()
    {
        return $this->hasMany(socialAccount::class);
    }
    public function withdrawals()
    {
        return $this->hasMany(WithdrawRequest::class);
    }

    public function hasRole($role)
    {
        $role = $this->roles()->where('roles.role_code', $role)->first();
        if ($role) {
            return true;
        }
        return false;
    }
    public function notifications()
    {
        return $this->morphMany(CustomDatabaseNotification::class, 'notifiable')
                    ->orderBy('created_at', 'desc');
    }
}
