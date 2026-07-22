import { computed } from 'vue'
import { useRoles } from './useRoles'
import { usePermissions } from './usePermissions'

/**
 * Central registry for admin portal links.
 * Add new entries here when introducing admin-only pages.
 */
export function useAdminPortal() {
  const {
    canManageUsers,
    canManageApps,
    isSuperadmin,
  } = useRoles()
  const { canConfigure, canImportAims } = usePermissions()

  const links = computed(() => [
    {
      id: 'apps',
      title: 'Apps',
      description: 'Control which modules each role can see and whether they are live or coming soon.',
      path: '/admin/apps',
      accent: '#7c3aed',
      icon: '◫',
      visible: canManageApps.value,
    },
    {
      id: 'aims-import',
      title: 'AIMS Import',
      description: 'Upload CSV exports from AIMS — students, attendance, fees, and results.',
      path: '/admin/aims-import',
      accent: '#2563eb',
      icon: '⇪',
      visible: canImportAims.value,
    },
    {
      id: 'users',
      title: 'Users',
      description: 'Manage staff accounts, roles, and impersonation for support.',
      path: '/admin/users',
      accent: '#0891b2',
      icon: '◎',
      visible: canManageUsers.value,
    },
    {
      id: 'academic',
      title: 'Academic config',
      description: 'Session years, areas, classes, sections, subjects, and enrollment.',
      path: '/admin/academic',
      accent: '#059669',
      icon: '▦',
      visible: canConfigure.value,
    },
    {
      id: 'permissions',
      title: 'Permissions',
      description: 'Role defaults and per-user permission grants.',
      path: '/admin/permissions',
      accent: '#d97706',
      icon: '⚿',
      visible: isSuperadmin.value,
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
