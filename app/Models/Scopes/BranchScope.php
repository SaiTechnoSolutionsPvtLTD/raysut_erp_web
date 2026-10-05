<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Super Admin or HQ users without specific branch switch see all records
        if ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin')) {
            if (Session::has('active_branch_id') && Session::get('active_branch_id') !== null) {
                $builder->where($model->getTable() . '.branch_id', Session::get('active_branch_id'));
            }
            return;
        }

        // Branch-restricted users see only their assigned default branch or accessible branches
        if ($user->default_branch_id) {
            $builder->where($model->getTable() . '.branch_id', $user->default_branch_id);
        }
    }
}
