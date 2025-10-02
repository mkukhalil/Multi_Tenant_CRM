<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // Spatie guard name
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-assign roles on creation
        static::created(function ($user) {
            // 1️⃣ User with ID=1 is always Super Admin
            if ($user->id === 1) {
                $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
                $user->assignRole($superAdminRole);
                return;
            }

            // 2️⃣ First user of a tenant → Tenant Admin
            if ($user->tenant_id) {
                $tenantUserCount = self::where('tenant_id', $user->tenant_id)->count();
                if ($tenantUserCount === 1) {
                    $tenantAdminRole = Role::firstOrCreate(['name' => 'Tenant Admin']);
                    $user->assignRole($tenantAdminRole);
                }
            }
        });

        // Reset auto-increment if all users deleted
        static::deleted(function () {
            if (self::count() === 0) {
                DB::afterCommit(fn() => DB::statement('ALTER TABLE users AUTO_INCREMENT = 1'));
            }
        });
    }

    /**
     * Relation: User belongs to a Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Shortcut: Check if user is Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->id === 1 || $this->hasRole('Super Admin');
    }
    public function assignedLeads()
{
    return $this->hasMany(\App\Models\Lead::class, 'assigned_to');
}

public function createdLeads()
{
    return $this->hasMany(\App\Models\Lead::class, 'created_by');
}

}
