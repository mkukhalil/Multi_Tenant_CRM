<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // only scope queries when a tenant_id has been bound by middleware
        if (app()->has('tenant_id')) {
            $builder->where($model->getTable() . '.tenant_id', app('tenant_id'));
        }
    }
}
