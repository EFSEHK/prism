<?php

namespace App\Services;

use App\Models\ModuleSetting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Single source of truth for Prism staff/learner module nav enablement.
 * Defaults live in code; superadmin/developer overrides persist in module_settings.
 */
class ModuleCatalogService
{
    public const STATUSES = ['live', 'coming_soon', 'disabled'];

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

    private const ALL_ROLES = [
        'superadmin',
        'developer',
        'admin',
        'principal',
        'vice_principal',
        'section_head',
        'class_incharge',
        'teacher',
        'computer_operator',
        'accountant',
        'parent',
        'student',
    ];

    /** @var Collection<string, ModuleSetting>|null */
    private ?Collection $settingsById = null;

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
            ? $this->learnerModules($roles)
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
     * Full catalog for Apps admin UI (defaults merged with persisted overrides).
     *
     * @return list<array<string, mixed>>
     */
    public function adminCatalog(): array
    {
        $out = [];
        foreach ($this->definitions() as $definition) {
            $resolved = $this->resolveDefinition($definition);
            $out[] = [
                'id' => $resolved['id'],
                'label' => $resolved['label'],
                'status' => $resolved['status'],
                'coming_soon' => $resolved['status'] === 'coming_soon',
                'platforms' => $resolved['platforms'],
                'route_web' => $resolved['route_web'],
                'route_mobile' => $resolved['route_mobile'],
                'visible_roles' => $resolved['visible_roles'],
                'default_status' => $this->defaultStatus($definition),
                'default_visible_roles' => $definition['visible_roles'],
                'locked_roles' => $definition['locked_roles'] ?? [],
                'editable' => (bool) ($definition['editable'] ?? true),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{id: string, status?: string, visible_roles?: list<string>|null}>  $items
     * @return list<array<string, mixed>>
     */
    public function syncSettings(array $items): array
    {
        $byId = collect($this->definitions())->keyBy('id');

        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            $definition = $byId->get($id);
            if (! $definition || ($definition['editable'] ?? true) === false) {
                continue;
            }

            $status = $item['status'] ?? null;
            if (! is_string($status) || ! in_array($status, self::STATUSES, true)) {
                $status = $this->defaultStatus($definition);
            }

            $roles = $item['visible_roles'] ?? null;
            if (! is_array($roles)) {
                $roles = $definition['visible_roles'];
            }
            $roles = $this->normalizeRoles($roles, $definition['locked_roles'] ?? []);

            ModuleSetting::query()->updateOrCreate(
                ['module_id' => $id],
                [
                    'status' => $status,
                    'visible_roles' => $roles,
                ]
            );
        }

        $this->settingsById = null;

        return $this->adminCatalog();
    }

    /**
     * @return list<string>
     */
    public function assignableRoles(): array
    {
        return self::ALL_ROLES;
    }

    /**
     * @param  list<string>  $roles
     * @return list<array<string, mixed>>
     */
    private function staffModules(User $user, array $roles): array
    {
        $enabled = [];

        foreach ($this->definitions() as $definition) {
            $resolved = $this->resolveDefinition($definition);
            if ($resolved['status'] === 'disabled') {
                continue;
            }
            if (! $this->isAllowed($user, $roles, $resolved)) {
                continue;
            }

            $enabled[] = $this->toModulePayload($resolved);
        }

        return $enabled;
    }

    /**
     * @param  list<string>  $roles
     * @return list<array<string, mixed>>
     */
    private function learnerModules(array $roles): array
    {
        $byId = collect($this->definitions())->keyBy('id');

        $dashboard = $this->resolveDefinition([
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'platforms' => ['web', 'mobile'],
            'route_web' => '/dashboard',
            'route_mobile' => 'dashboard',
            'coming_soon' => false,
            'visible_roles' => self::LEARNER_ROLES,
            'editable' => false,
        ]);

        $modules = [$this->toModulePayload($dashboard)];

        foreach (self::LEARNER_MODULE_IDS as $id) {
            $definition = $byId->get($id);
            if (! $definition) {
                continue;
            }
            $resolved = $this->resolveDefinition($definition);
            if ($resolved['status'] === 'disabled') {
                continue;
            }
            if (! $this->hasAnyRole($roles, $resolved['visible_roles'])) {
                continue;
            }
            $modules[] = $this->toModulePayload($resolved);
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
        $status = $definition['status'] ?? $this->defaultStatus($definition);
        if (! in_array($status, self::STATUSES, true)) {
            $status = $this->defaultStatus($definition);
        }

        return [
            'id' => $definition['id'],
            'label' => $definition['label'],
            'enabled' => $status !== 'disabled',
            'status' => $status,
            'coming_soon' => $status === 'coming_soon',
            'platforms' => $definition['platforms'],
            'route_web' => $definition['route_web'],
            'route_mobile' => $definition['route_mobile'],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function resolveDefinition(array $definition): array
    {
        $setting = $this->settings()->get($definition['id']);
        $status = $setting?->status;
        if (! is_string($status) || ! in_array($status, self::STATUSES, true)) {
            $status = $this->defaultStatus($definition);
        }

        $roles = $setting?->visible_roles;
        if (! is_array($roles)) {
            $roles = $definition['visible_roles'];
        }
        $roles = $this->normalizeRoles($roles, $definition['locked_roles'] ?? []);

        $definition['status'] = $status;
        $definition['coming_soon'] = $status === 'coming_soon';
        $definition['visible_roles'] = $roles;

        return $definition;
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @param  list<string>  $roles
     */
    private function isAllowed(User $user, array $roles, array $resolved): bool
    {
        if (isset($resolved['gate']) && is_callable($resolved['gate'])) {
            return (bool) ($resolved['gate'])($user, $roles, $resolved['visible_roles']);
        }

        return $this->hasAnyRole($roles, $resolved['visible_roles']);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function defaultStatus(array $definition): string
    {
        if (isset($definition['status']) && in_array($definition['status'], self::STATUSES, true)) {
            return $definition['status'];
        }

        return ! empty($definition['coming_soon']) ? 'coming_soon' : 'live';
    }

    /**
     * @param  list<mixed>  $roles
     * @param  list<string>  $locked
     * @return list<string>
     */
    private function normalizeRoles(array $roles, array $locked): array
    {
        $normalized = [];
        foreach ($roles as $role) {
            if (! is_string($role)) {
                continue;
            }
            $role = strtolower(trim($role));
            if ($role === '' || ! in_array($role, self::ALL_ROLES, true)) {
                continue;
            }
            $normalized[] = $role;
        }

        foreach ($locked as $role) {
            $normalized[] = strtolower($role);
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return Collection<string, ModuleSetting>
     */
    private function settings(): Collection
    {
        if ($this->settingsById === null) {
            try {
                $this->settingsById = ModuleSetting::query()->get()->keyBy('module_id');
            } catch (\Throwable) {
                $this->settingsById = collect();
            }
        }

        return $this->settingsById;
    }

    /**
     * @return list<array{
     *   id: string,
     *   label: string,
     *   platforms: list<string>,
     *   route_web: string|null,
     *   route_mobile: string|null,
     *   coming_soon: bool,
     *   visible_roles: list<string>,
     *   locked_roles?: list<string>,
     *   editable?: bool,
     *   gate?: callable
     * }>
     */
    private function definitions(): array
    {
        $configRoles = array_values(array_unique([...self::ACADEMIC_ROLES, ...self::ROSTER_ROLES]));

        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/home',
                'route_mobile' => 'dashboard',
                'coming_soon' => false,
                'visible_roles' => array_values(array_unique([...self::STAFF_ROLES, 'developer', 'accountant', ...self::LEARNER_ROLES])),
                'locked_roles' => [],
                'editable' => false,
                'gate' => fn (User $user, array $roles, array $allowed): bool => true,
            ],
            [
                'id' => 'users',
                'label' => 'Users',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/admin/users',
                'route_mobile' => 'users',
                'coming_soon' => false,
                'visible_roles' => self::MANAGE_USERS_ROLES,
                'locked_roles' => ['superadmin'],
            ],
            [
                'id' => 'configuration',
                'label' => 'Configuration',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/admin/academic',
                'route_mobile' => 'configuration',
                'coming_soon' => false,
                'visible_roles' => $configRoles,
                'locked_roles' => ['superadmin'],
                'gate' => function (User $user, array $roles, array $allowed): bool {
                    return $user->can('manage_academic_structure')
                        || $user->can('manage_student_roster')
                        || $this->hasAnyRole($roles, $allowed);
                },
            ],
            [
                'id' => 'permissions',
                'label' => 'Permissions',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/admin/permissions',
                'route_mobile' => 'permissions',
                'coming_soon' => false,
                'visible_roles' => ['superadmin'],
                'locked_roles' => ['superadmin'],
                'editable' => false,
            ],
            [
                'id' => 'apps',
                'label' => 'Apps',
                'platforms' => ['web'],
                'route_web' => '/admin/apps',
                'route_mobile' => null,
                'coming_soon' => false,
                'visible_roles' => ['superadmin', 'developer'],
                'locked_roles' => ['superadmin', 'developer'],
                'editable' => false,
            ],
            [
                'id' => 'approvals',
                'label' => 'Approvals',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/approvals',
                'route_mobile' => 'approvals',
                'coming_soon' => false,
                'visible_roles' => self::APPROVE_ROLES,
                'locked_roles' => ['superadmin'],
            ],
            [
                'id' => 'attendance',
                'label' => 'Attendance',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/attendance',
                'route_mobile' => 'attendance',
                'coming_soon' => true,
                'visible_roles' => array_values(array_unique([...self::STAFF_ROLES, ...self::LEARNER_ROLES])),
                'locked_roles' => [],
            ],
            [
                'id' => 'marks',
                'label' => 'Marks',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/marks',
                'route_mobile' => 'marks',
                'coming_soon' => true,
                'visible_roles' => array_values(array_unique([...self::STAFF_ROLES, ...self::LEARNER_ROLES])),
                'locked_roles' => [],
            ],
            [
                'id' => 'homework',
                'label' => 'Homework',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/homework',
                'route_mobile' => 'homework',
                'coming_soon' => true,
                'visible_roles' => array_values(array_unique([...self::STAFF_ROLES, ...self::LEARNER_ROLES])),
                'locked_roles' => [],
            ],
            [
                'id' => 'timetable',
                'label' => 'Timetable',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/timetable',
                'route_mobile' => 'timetable',
                'coming_soon' => true,
                'visible_roles' => array_values(array_unique([...self::TIMETABLE_ROLES, ...self::LEARNER_ROLES])),
                'locked_roles' => [],
            ],
            [
                'id' => 'online',
                'label' => 'Online',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/online-classes',
                'route_mobile' => 'online',
                'coming_soon' => true,
                'visible_roles' => array_values(array_unique([...self::STAFF_ROLES, ...self::LEARNER_ROLES])),
                'locked_roles' => [],
            ],
            [
                'id' => 'fees',
                'label' => 'Fees',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/fees',
                'route_mobile' => 'fees',
                'coming_soon' => true,
                'visible_roles' => array_values(array_unique([...self::FEES_ROLES, ...self::LEARNER_ROLES])),
                'locked_roles' => [],
            ],
            [
                'id' => 'notifications',
                'label' => 'Notifications',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/notifications',
                'route_mobile' => 'notifications',
                'coming_soon' => true,
                'visible_roles' => array_values(array_unique([...self::BROADCAST_ROLES, ...self::LEARNER_ROLES])),
                'locked_roles' => [],
            ],
            [
                'id' => 'leave',
                'label' => 'Leave',
                'platforms' => ['web', 'mobile'],
                'route_web' => '/leave',
                'route_mobile' => 'leave',
                'coming_soon' => true,
                'visible_roles' => array_values(array_unique([...self::LEAVE_ROLES, 'student'])),
                'locked_roles' => [],
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
