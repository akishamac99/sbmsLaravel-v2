<?php

namespace App\Actions\ServiceCategories;

use App\Exceptions\BusinessRuleViolation;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\SystemLog;

/** UC54 Manage Service Categories: delete. A category that still has services cannot be deleted. */
final class DeleteServiceCategory
{
    public function handle(Staff $actor, ServiceCategory $category): void
    {
        if ($category->services()->exists()) {
            throw new BusinessRuleViolation('This category still has services. Move or delete those services first.');
        }

        $name = $category->category_name;
        $category->delete();

        SystemLog::record($actor->role_slug, (int) $actor->getKey(), 'delete_service_category', "Deleted service category {$name}");
    }
}
