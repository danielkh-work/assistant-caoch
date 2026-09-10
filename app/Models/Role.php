<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public const CATEGORY_FEATURE_TIER = 'feature_tier';
    public const CATEGORY_USER_TYPE = 'user_type';

    public function scopeFeatureTier($query)
    {
        return $query->where('category', self::CATEGORY_FEATURE_TIER);
    }

    public function scopeUserType($query)
    {
        return $query->where('category', self::CATEGORY_USER_TYPE);
    }
}
