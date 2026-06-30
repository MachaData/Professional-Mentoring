<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // No authenticated user, or a superadmin (no organization) → no scoping.
        if (! $user || $user->isSuperadmin()) {
            return;
        }

        if ($user->organization_id) {
            $builder->where($model->getTable().'.organization_id', $user->organization_id);
        }
    }
}
