<?php

namespace App\Services;

use App\Models\User;

/**
 * Single source of truth for Prism staff/learner module nav enablement.
 * Mirrors the role gates previously duplicated in web useRoles / usePermissions
 * and mobile STAFF_FEATURE_DEFS visibility checks.
 */
class ModuleCatalogService
{
    private const STAFF_ROLES = [
        'superadmin',
        'admin',
        'principal',
        'vice_principal',
        'section_head',
        'class_incharge',
        'teacher',
        'computer_operator',
    ];

    private const APPROVE_ROLES = [
        'superadmin',
        'admin',
        'principal',
        'vice_principal',
        'section_head',
        'class_incharge',
    ];

    private const TIMETABLE_ROLES = [
        'superadmin',
        'admin',
        'computer_operator',
        'teacher',
    ];

    private const FEES_ROLES = [
        'superadmin',
        'admin',
        'accountant',
        'computer_operator',
    ];

    private const BROADCAST_ROLES = [
        'superadmin',
        'admin',
        'principal',
        'vice_principal',
        'section_head',
        'class_incharge',
        'teacher',
        'computer_operator',
    ];

    private const LEAVE_ROLES = [
        'superadmin',
        'admin',
        'section_head',
        'parent',
    ];

    private const MANAGE_USERS_ROLES = [
        'superadmin',
        'admin',
        'computer_operator',
    ];

    private const ACADEMIC_ROLES = [
        'superadmin',
        'admin',
        'developer',
        'computer_operator',
    ];

    private const ROSTER_ROLES = [
        'computer_operator',
        'section_head',
        'class_incharge',
    ];

    private const LEARNER_ROLES = ['parent', 'student'];

    private const LEARNER_MODULE_IDS = [
        'homework',
        'marks',
        'attendance',
        'timetable',
        'notifications',
        'fees',
        'online',
        'leave',
    ];

    /**
     * @return list<array{
     *   id: string,
     *   label: string,
     *   enabled: bool,
     *   platforms: list<string>,
     *   route_web: string|null,
     *   route_mobile: string|null
     * }>
     */
    public function forUser(User $user, ?string $platform = null): array
    {
        $roles = $user->getRoleNames()
            ->map(fn (string $name) => strtolower($name))
            ->values()
            ->all();

        $isLearner = $this->hasAnyRole($roles, self::LEARNER_ROLES)
            && ! $this->hasAnyRole($roles, self::STAFF_ROLES)
            && ! $this->hasAnyRole($roles, ['developer', 'accountant']);

        $modules = $isLearner
            ? $this->learnerModules()
            : $this->staffModules($user, $roles);

        if ($platform !== null && $platform !== '') {
            $platform = strtolower($platform);
            $modules = array_values(array_filter(
                $modules,
                fn (array $module) => in_array($platform, $module['platforms'], true)
            ));
        }

        return $modules;
    }

    /**
     * @param  list<string>  $roles
     * @return list<array<string, mixed>>
     */
    private function staffModules(User $user, array $roles): array
    {
        $definitions = $this->definitions();
        $enabled = [];

        foreach ($definitions as $definition) {
            $allowed = ($definition['gate'])($user, $roles);
            if (! $allowed) {
                continue;
            }

            $enabled[] = $this->toModulePayload($definition);
        }

        return $enabled;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function learnerModules(): array
    {
        $byId = collect($this->definitions())->keyBy('id');

        $modules = [
            $this->toModulePayload([
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/dashboard',
                'route_mobile' => 'dashboard',
                'coming_soon' => false,
            ]),
        ];

        foreach (self::LEARNER_MODULE_IDS as $id) {
            $definition = $byId->get($id);
            if (! $definition) {
                continue;
            }
            $modules[] = $this->toModulePayload($definition);
        }

        return $modules;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array{
     *   id: string,
     *   label: string,
     *   enabled: bool,
     *   status: 'live'|'coming_soon'|'disabled',
     *   coming_soon: bool,
     *   platforms: list<string>,
     *   route_web: string|null,
     *   route_mobile: string|null
     * }
     */
    private function toModulePayload(array $definition): array
    {
        $comingSoon = (bool) ($definition['coming_soon'] ?? false);
        $status = $definition['status'] ?? ($comingSoon ? 'coming_soon' : 'live');
        if (! in_array($status, ['live', 'coming_soon', 'disabled'], true)) {
            $status = $comingSoon ? 'coming_soon' : 'live';
        }

        return [
            'id' => $definition['id'],
            'label' => $definition['label'],
            // enabled remains for older clients; prefer `status` for tri-state UI.
            'enabled' => $status !== 'disabled',
            'status' => $status,
            'coming_soon' => $status === 'coming_soon',
            'platforms' => $definition['platforms'],
            'route_web' => $definition['route_web'],
            'route_mobile' => $definition['route_mobile'],
        ];
    }

    /**
     * @return list<array{
     *   id: string,
     *   label: string,
     *   platforms: list<string>,
     *   route_web: string|null,
     *   route_mobile: string|null,
     *   coming_soon: bool,
     *   gate: callable(User, list<string>): bool
     * }>
     */
    private function definitions(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/home',
                'route_mobile' => 'dashboard',
                'coming_soon' => false,
                'gate' => fn (User $user, array $roles): bool => true,
            ],
            [
                'id' => 'users',
                'label' => 'Users',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/admin/users',
                'route_mobile' => 'users',
                'coming_soon' => false,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::MANAGE_USERS_ROLES),
            ],
            [
                'id' => 'configuration',
                'label' => 'Configuration',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/admin/academic',
                'route_mobile' => 'configuration',
                'coming_soon' => false,
                'gate' => function (User $user, array $roles): bool {
                    return $user->can('manage_academic_structure')
                        || $user->can('manage_student_roster')
                        || $this->hasAnyRole($roles, self::ACADEMIC_ROLES)
                        || $this->hasAnyRole($roles, self::ROSTER_ROLES);
                },
            ],
            [
                'id' => 'permissions',
                'label' => 'Permissions',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/admin/permissions',
                'route_mobile' => 'permissions',
                'coming_soon' => false,
                'gate' => fn (User $user, array $roles): bool => in_array('superadmin', $roles, true),
            ],
            [
                'id' => 'approvals',
                'label' => 'Approvals',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/approvals',
                'route_mobile' => 'approvals',
                'coming_soon' => false,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::APPROVE_ROLES),
            ],
            [
                'id' => 'attendance',
                'label' => 'Attendance',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/attendance',
                'route_mobile' => 'attendance',
                'coming_soon' => true,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::STAFF_ROLES),
            ],
            [
                'id' => 'marks',
                'label' => 'Marks',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/marks',
                'route_mobile' => 'marks',
                'coming_soon' => true,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::STAFF_ROLES),
            ],
            [
                'id' => 'homework',
                'label' => 'Homework',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/homework',
                'route_mobile' => 'homework',
                'coming_soon' => true,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::STAFF_ROLES),
            ],
            [
                'id' => 'timetable',
                'label' => 'Timetable',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/timetable',
                'route_mobile' => 'timetable',
                'coming_soon' => true,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::TIMETABLE_ROLES),
            ],
            [
                'id' => 'online',
                'label' => 'Online',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/online-classes',
                'route_mobile' => 'online',
                'coming_soon' => true,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::STAFF_ROLES),
            ],
            [
                'id' => 'fees',
                'label' => 'Fees',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/fees',
                'route_mobile' => 'fees',
                'coming_soon' => true,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::FEES_ROLES),
            ],
            [
                'id' => 'notifications',
                'label' => 'Notifications',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/notifications',
                'route_mobile' => 'notifications',
                'coming_soon' => true,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::BROADCAST_ROLES),
            ],
            [
                'id' => 'leave',
                'label' => 'Leave',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/leave',
                'route_mobile' => 'leave',
                'coming_soon' => true,
                'gate' => fn (User $user, array $roles): bool => $this->hasAnyRole($roles, self::LEAVE_ROLES),
            ],
        ];
    }

    /**
     * @param  list<string>  $roles
     * @param  list<string>  $allowed
     */
    private function hasAnyRole(array $roles, array $allowed): bool
    {
        return count(array_intersect($roles, $allowed)) > 0;
    }
}
