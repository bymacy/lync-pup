<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'account_status',
        'is_first_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_first_login' => 'boolean',
        ];
    }

    // Relationships
    public function startup()
    {
        return $this->hasOne(Startup::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    // Role helper accessors
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    public function isStartup(): bool
    {
        return $this->role === 'Startup';
    }

    /**
     * Account-level approval gate for self-registered Founders — separate
     * from the existing Information Sheet content-approval flow. Accounts
     * created via seeders/admin default to "Active"; self-registered
     * accounts start "Pending" until an admin approves or rejects them.
     */
    public function isApprovedAccount(): bool
    {
        return $this->account_status === 'Active';
    }

    public function isPendingApproval(): bool
    {
        return $this->account_status === 'Pending';
    }

    public function isRejected(): bool
    {
        return $this->account_status === 'Rejected';
    }
}