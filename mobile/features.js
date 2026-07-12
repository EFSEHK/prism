/**
 * Feature tiles for the mobile home/dashboard icon grid.
 *
 * Enablement comes from GET /efsc/modules (backend ModuleCatalogService).
 * - `coming_soon: true` (matches web ComingSoonView stubs) → grey tile + Alert "Coming soon"
 * - PENDING_MOBILE_SCREENS → catalog-live on web, mobile UI not built → in-tab Coming soon
 */

/** Staff module IDs that are live on web but lack a dedicated mobile screen. */
export const PENDING_MOBILE_SCREENS = [
  'approvals',
  'marks',
  'homework',
  'online',
  'notifications',
  'leave',
  'users',
  'configuration',
  'permissions',
]

const LEARNER_FEATURE_DEFS = [
  { id: 'homework', label: 'Homework', tint: '#2563eb', soft: '#eff6ff' },
  { id: 'marks', label: 'Marks', tint: '#059669', soft: '#ecfdf5' },
  { id: 'attendance', label: 'Attendance', tint: '#d97706', soft: '#fffbeb' },
  { id: 'timetable', label: 'Timetable', tint: '#7c3aed', soft: '#f5f3ff' },
  { id: 'notifications', label: 'Notifications', tint: '#db2777', soft: '#fdf2f8' },
  { id: 'fees', label: 'Fees', tint: '#0d9488', soft: '#f0fdfa' },
  { id: 'online', label: 'Online Class', tint: '#0284c7', soft: '#f0f9ff' },
  { id: 'leave', label: 'Leave', tint: '#ea580c', soft: '#fff7ed' },
]

const STAFF_FEATURE_META = {
  attendance: { tint: '#d97706', soft: '#fffbeb', label: 'Attendance' },
  approvals: { tint: '#059669', soft: '#ecfdf5', label: 'Approvals' },
  marks: { tint: '#2563eb', soft: '#eff6ff', label: 'Marks' },
  homework: { tint: '#7c3aed', soft: '#f5f3ff', label: 'Homework' },
  online: { tint: '#0284c7', soft: '#f0f9ff', label: 'Online Class' },
  timetable: { tint: '#db2777', soft: '#fdf2f8', label: 'Timetable' },
  fees: { tint: '#0d9488', soft: '#f0fdfa', label: 'Fees' },
  notifications: { tint: '#ea580c', soft: '#fff7ed', label: 'Notifications' },
  leave: { tint: '#4f46e5', soft: '#eef2ff', label: 'Leave' },
  users: { tint: '#334155', soft: '#f1f5f9', label: 'Users' },
  configuration: { tint: '#64748b', soft: '#f8fafc', label: 'Configuration' },
  permissions: { tint: '#b45309', soft: '#fffbeb', label: 'Permissions' },
}

function moduleEnabledOnMobile(module) {
  if (!module || module.enabled === false) return false
  const platforms = module.platforms || ['web', 'mobile']
  return platforms.includes('mobile')
}

/**
 * Build staff dashboard tiles from the API module catalog.
 * @param {Array<{ id: string, label?: string, enabled?: boolean, coming_soon?: boolean, platforms?: string[] }>} modules
 */
export function staffFeaturesFor(modules = []) {
  return (modules || [])
    .filter((m) => m.id !== 'dashboard' && moduleEnabledOnMobile(m))
    .map((m) => {
      const meta = STAFF_FEATURE_META[m.id] || {}
      const comingSoon = m.coming_soon === true
      return {
        id: m.id,
        label: m.label || meta.label || m.id,
        tint: meta.tint || '#64748b',
        soft: meta.soft || '#f8fafc',
        ready: !comingSoon,
        comingSoon,
        pendingScreen: !comingSoon && PENDING_MOBILE_SCREENS.includes(m.id),
      }
    })
}

/**
 * Learner tiles: prefer catalog when provided; otherwise fall back to full learner set.
 * @param {Array<{ id: string, label?: string, enabled?: boolean, coming_soon?: boolean, platforms?: string[] }>} [modules]
 */
export function learnerFeatures(modules) {
  if (Array.isArray(modules) && modules.length > 0) {
    const byId = Object.fromEntries(
      modules.filter(moduleEnabledOnMobile).map((m) => [m.id, m]),
    )
    return LEARNER_FEATURE_DEFS
      .filter((f) => byId[f.id])
      .map((f) => {
        const comingSoon = byId[f.id]?.coming_soon === true
        return {
          ...f,
          ready: !comingSoon,
          comingSoon,
          pendingScreen: false,
        }
      })
  }

  return LEARNER_FEATURE_DEFS.map((f) => ({
    ...f,
    ready: true,
    comingSoon: false,
    pendingScreen: false,
  }))
}

export function isPendingMobileScreen(id) {
  return PENDING_MOBILE_SCREENS.includes(id)
}

export function isCatalogComingSoon(modules, id) {
  return (modules || []).some((m) => m.id === id && m.coming_soon === true)
}
