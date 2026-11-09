<?php

namespace App\Actions\ServiceCategories;

use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\SystemLog;

/** UC54 Manage Service Categories: add and update. */
final class SaveServiceCategory
{
    /** @param array{category_name: string, description?: ?string, is_event_type?: bool} $data */
    public function handle(Staff $actor, array $data, ?ServiceCategory $category = null): ServiceCategory
    {
        $attributes = [
            'category_name' => $data['category_name'],
            'description' => $data['description'] ?? null,
            'is_event_type' => ! empty($data['is_event_type']) ? 1 : 0,
        ];

        if ($category) {
            $category->update($attributes);
            $action = 'update_service_category';
        } else {
            $category = ServiceCategory::create($attributes);
            $action = 'create_service_category';
        }

        SystemLog::record($actor->role_slug, (int) $actor->getKey(), $action, "{$action}: {$category->category_name}");

        return $category;
    }
}
