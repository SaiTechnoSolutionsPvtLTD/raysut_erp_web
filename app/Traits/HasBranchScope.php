<?php

namespace App\Traits;

use App\Models\Scopes\BranchScope;

trait HasBranchScope
{
    /**
     * Boot the branch scope for the model.
     */
    protected static function bootHasBranchScope(): void
    {
        static::addGlobalScope(new BranchScope());
    }
}
