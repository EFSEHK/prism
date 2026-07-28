import { computed } from 'vue'
import { useRoles } from './useRoles'
import { usePermissions } from './usePermissions'
import { useModulesStore } from '../stores/modules'

/** Admin portal card id → module catalog id */
const PORTAL_MODULE_IDS = {
  apps: 'apps',
  'aims-import': 'aims-import',
  users: 'users',
  academic: 'configuration',
  permissions: 'permissions',
}

/**
 * Central registry for admin portal links.
 * Visibility follows the Apps page module catalog when loaded.
 */
export function useAdminPortal() {
  const {
    canManageUsers,
    canManageApps,
    isSuperadmin,
  } = useRoles()
  const { canConfigure, canImportAims } = usePermissions()
  const modules = useModulesStore()

  const portalFallbacks = computed(() => ({
    apps: canManageApps.value,
    'aims-import': canImportAims.value,
    users: canManageUsers.value,
    academic: canConfigure.value,
    permissions: isSuperadmin.value,
  }))

  function linkVisible(linkId) {
    const moduleId = PORTAL_MODULE_IDS[linkId]
    return modules.canAccessModule(moduleId, portalFallbacks.value[linkId] ?? false)
  }

  const links = computed(() => [
    {
      id: 'apps',
      title: 'Apps',
      description: 'Control which modules each role can see and whether they are live or coming soon.',
      path: '/admin/apps',
      accent: '#7c3aed',
      icon: '◫',
      visible: linkVisible('apps'),
    },
    {
      id: 'aims-import',
      title: 'AIMS Import',
      description: 'Upload CSV exports from AIMS — students, attendance, fees, and results.',
      path: '/admin/aims-import',
      accent: '#2563eb',
      icon: '⇪',
      visible: linkVisible('aims-import'),
    },
    {
      id: 'users',
      title: 'Users',
      description: 'Manage staff accounts, roles, and impersonation for support.',
      path: '/admin/users',
      accent: '#0891b2',
      icon: '◎',
      visible: linkVisible('users'),
    },
    {
      id: 'academic',
      title: 'Academic config',
      description: 'Session years, areas, classes, sections, subjects, and enrollment.',
      path: '/admin/academic',
      accent: '#059669',
      icon: '▦',
      visible: linkVisible('academic'),
    },
    {
      id: 'permissions',
      title: 'Permissions',
      description: 'Role defaults and per-user permission grants.',
      path: '/admin/permissions',
      accent: '#d97706',
      icon: '⚿',
      visible: linkVisible('permissions'),
    },
  ])

  const visibleLinks = computed(() => links.value.filter((link) => link.visible))

  const canAccessAdminPortal = computed(() => visibleLinks.value.length > 0)

  return {
    links,
    visibleLinks,
    canAccessAdminPortal,
  }
}
