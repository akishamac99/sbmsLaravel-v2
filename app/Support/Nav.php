<?php

namespace App\Support;

use App\Models\Permission;

class Nav
{
    /**
     * Sidebar links for each role, grouped into sections.
     *
     * Each item is: [label, route name ending, icon, manager permission module].
     * A group whose label is null is shown without a heading. The "Settings" group is
     * collapsible in the sidebar and also rendered as tabs on the settings pages.
     *
     * @return array<string, array<int, array{0: string, 1: string, 2: string, 3: ?string}>>
     */
    private static function definitions(string $role): array
    {
        return [
            'owner' => [
                '' => [
                    ['Dashboard', 'dashboard', 'fa-chart-line', null],
                ],
                'Bookings' => [
                    ['Manage Bookings', 'bookings', 'fa-calendar', null],
                    ['Walk-In', 'walkin', 'fa-user-plus', null],
                    ['Payments', 'payments', 'fa-credit-card', null],
                    ['Attendance', 'attendance', 'fa-check-circle', null],
                ],
                'Business' => [
                    ['Customers', 'customers', 'fa-users', null],
                    ['Services', 'services', 'fa-scissors', null],
                    ['Promos', 'promos', 'fa-tags', null],
                    ['Reports', 'reports', 'fa-chart-bar', null],
                ],
                'Team' => [
                    ['Staff', 'staff.manage', 'fa-id-card', null],
                    ['Staff Availability', 'staff-availability', 'fa-clock', null],
                ],
                'Settings' => [
                    ['My Profile', 'profile', 'fa-user', null],
                    ['Policies', 'policies', 'fa-gavel', null],
                    ['Permissions', 'permissions', 'fa-user-shield', null],
                    ['System Logs', 'logs', 'fa-list', null],
                ],
            ],
            'manager' => [
                '' => [
                    ['Dashboard', 'dashboard', 'fa-chart-line', null],
                ],
                'Bookings' => [
                    ['Manage Bookings', 'bookings', 'fa-calendar', 'bookings'],
                    ['Walk-In', 'walkin', 'fa-user-plus', 'walkin'],
                    ['Payments', 'payments', 'fa-credit-card', 'payments'],
                    ['Attendance', 'attendance', 'fa-check-circle', 'attendance'],
                ],
                'Business' => [
                    ['Customers', 'customers', 'fa-users', 'customers'],
                    ['Services', 'services', 'fa-scissors', 'services'],
                    ['Reports', 'reports', 'fa-chart-bar', 'reports'],
                    ['Staff Directory', 'staff', 'fa-id-card', 'staff'],
                ],
                'Settings' => [
                    ['My Profile', 'profile', 'fa-user', null],
                ],
            ],
            'staff' => [
                '' => [
                    ['Dashboard', 'dashboard', 'fa-chart-line', null],
                ],
                'My Work' => [
                    ['My Bookings', 'bookings', 'fa-calendar', null],
                    ['Walk-In', 'walkin', 'fa-user-plus', null],
                    ['Attendance', 'attendance', 'fa-check-circle', null],
                ],
                'Settings' => [
                    ['My Profile', 'profile', 'fa-user', null],
                    ['My Availability', 'availability', 'fa-clock', null],
                ],
            ],
            'customer' => [
                '' => [
                    ['Dashboard', 'dashboard', 'fa-home', null],
                    ['Book a Service', 'services', 'fa-scissors', null],
                    ['My Bookings', 'bookings', 'fa-calendar', null],
                    ['Notifications', 'notifications', 'fa-bell', null],
                    ['Feedback', 'feedback', 'fa-star', null],
                ],
                'Settings' => [
                    ['My Profile', 'profile', 'fa-user', null],
                ],
            ],
        ][$role] ?? [];
    }

    /**
     * Sidebar groups for a role, with managers limited to the sections the owner allows.
     *
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, icon: string}>}>
     */
    public static function groups(string $role): array
    {
        $groups = [];

        foreach (self::definitions($role) as $label => $items) {
            $visible = collect($items)
                ->filter(fn (array $item): bool => $role !== 'manager'
                    || $item[3] === null
                    || Permission::allows('Manager', $item[3], 'view'))
                ->map(fn (array $item): array => [
                    'label' => $item[0],
                    'route' => $role.'.'.$item[1],
                    'icon' => $item[2],
                ])
                ->values()
                ->all();

            if ($visible !== []) {
                $groups[] = ['label' => (string) $label, 'items' => $visible];
            }
        }

        return $groups;
    }

    /**
     * Every sidebar link for a role, without grouping.
     *
     * @return array<int, array{label: string, route: string, icon: string}>
     */
    public static function for(string $role): array
    {
        return collect(self::groups($role))->flatMap(fn (array $group): array => $group['items'])->values()->all();
    }

    /**
     * The links in the role's Settings group, used for the tabs on settings pages.
     *
     * @return array<int, array{label: string, route: string, icon: string}>
     */
    public static function settings(string $role): array
    {
        return collect(self::groups($role))->firstWhere('label', 'Settings')['items'] ?? [];
    }
}
