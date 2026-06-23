<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function view(User $user, ProductCategory $category): bool
    {
        return $user->can('inventory.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function update(User $user, ProductCategory $category): bool
    {
        return $user->can('inventory.manage');
    }

    public function delete(User $user, ProductCategory $category): bool
    {
        return $user->can('inventory.manage');
    }
}
