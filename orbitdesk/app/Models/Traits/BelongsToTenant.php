<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant()
    {
        // Apply tenant scope to all queries
        static::addGlobalScope(new TenantScope);

        // Automatically set tenant_id when creating a record
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->tenant_id) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }
}
