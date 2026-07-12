/**
 * Feature tiles for the mobile home/dashboard icon grid.
 *
 * Enablement comes ONLY from GET /efsc/modules (ModuleCatalogService).
 * Render from `status`: "live" | "coming_soon" | "disabled" — no local readiness maps.
 */

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

/** Normalize catalog module to tri-state status. */
export function moduleStatus(module) {
  if (!module) return 'disabled'
  if (module.status === 'live' || module.status === 'coming_soon' || module.status === 'disabled') {
    return module.status
  }
  if (module.enabled === false) return 'disabled'
  if (module.coming_soon === true) return 'coming_soon'
  return 'live'
}

function moduleEnabledOnMobile(module) {
  if (moduleStatus(module) === 'disabled') return false
  const platforms = module.platforms || ['web', 'mobile']
  return platforms.includes('mobile')
}

function toFeatureTile(m, meta = {}) {
  const status = moduleStatus(m)
  return {
    id: m.id,
    label: m.label || meta.label || m.id,
    tint: meta.tint || '#64748b',
    soft: meta.soft || '#f8fafc',
    status,
    ready: status === 'live',
    comingSoon: status === 'coming_soon',
  }
}

/**
 * Build staff dashboard tiles from the API module catalog.
 * @param {Array<{ id: string, label?: string, status?: string, enabled?: boolean, coming_soon?: boolean, platforms?: string[] }>} modules
 */
export function staffFeaturesFor(modules = []) {
  return (modules || [])
    .filter((m) => m.id !== 'dashboard' && moduleEnabledOnMobile(m))
    .map((m) => toFeatureTile(m, STAFF_FEATURE_META[m.id] || {}))
}

/**
 * Learner tiles: prefer catalog when provided; otherwise fall back to full learner set.
 * @param {Array<{ id: string, label?: string, status?: string, enabled?: boolean, coming_soon?: boolean, platforms?: string[] }>} [modules]
 */
export function learnerFeatures(modules) {
  if (Array.isArray(modules) && modules.length > 0) {
    const byId = Object.fromEntries(
      modules.filter(moduleEnabledOnMobile).map((m) => [m.id, m]),
    )
    return LEARNER_FEATURE_DEFS
      .filter((f) => byId[f.id])
      .map((f) => toFeatureTile(byId[f.id], f))
  }

  return LEARNER_FEATURE_DEFS.map((f) => ({
    ...f,
    status: 'live',
    ready: true,
    comingSoon: false,
  }))
}

export function isCatalogComingSoon(modules, id) {
  return (modules || []).some((m) => m.id === id && moduleStatus(m) === 'coming_soon')
}

export function isCatalogLive(modules, id) {
  return (modules || []).some((m) => m.id === id && moduleStatus(m) === 'live')
}
