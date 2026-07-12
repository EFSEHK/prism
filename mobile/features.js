/**
 * Feature tiles for the mobile home/dashboard icon grid.
 * `ready: false` → greyed tile + Alert "Coming soon".
 *
 * Readiness is global: if a feature is incomplete for any role, it is
 * disabled for everyone. Flip a flag here when the mobile flow is done.
 */
export const FEATURE_READY = {
  attendance: true,
  approvals: false,
  marks: false,
  homework: false,
  online: false,
  timetable: false,
  fees: false,
  notifications: false,
  leave: false,
  users: false,
  configuration: false,
  permissions: false,
}

function isReady(id) {
  return FEATURE_READY[id] === true
}

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

const CONFIG_PERMS = [
  'manage_academic_years',
  'manage_areas',
  'manage_classes',
  'manage_sections',
  'manage_subjects',
  'manage_students',
]

const STAFF_FEATURE_DEFS = [
  {
    id: 'attendance',
    label: 'Attendance',
    tint: '#d97706',
    soft: '#fffbeb',
    visible: ({ roles, permissions }) =>
      permissions.includes('mark_attendance') || roles.includes('computer_operator'),
  },
  {
    id: 'approvals',
    label: 'Approvals',
    tint: '#059669',
    soft: '#ecfdf5',
    visible: ({ roles }) =>
      roles.some((n) =>
        ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge'].includes(n),
      ),
  },
  {
    id: 'marks',
    label: 'Marks',
    tint: '#2563eb',
    soft: '#eff6ff',
    visible: ({ roles }) =>
      roles.some((n) =>
        ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge', 'teacher', 'computer_operator'].includes(n),
      ),
  },
  {
    id: 'homework',
    label: 'Homework',
    tint: '#7c3aed',
    soft: '#f5f3ff',
    visible: ({ roles }) =>
      roles.some((n) =>
        ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge', 'teacher', 'computer_operator'].includes(n),
      ),
  },
  {
    id: 'online',
    label: 'Online Class',
    tint: '#0284c7',
    soft: '#f0f9ff',
    visible: ({ roles }) =>
      roles.some((n) =>
        ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge', 'teacher', 'computer_operator'].includes(n),
      ),
  },
  {
    id: 'timetable',
    label: 'Timetable',
    tint: '#db2777',
    soft: '#fdf2f8',
    visible: ({ roles }) =>
      roles.some((n) => ['superadmin', 'admin', 'computer_operator', 'teacher'].includes(n)),
  },
  {
    id: 'fees',
    label: 'Fees',
    tint: '#0d9488',
    soft: '#f0fdfa',
    visible: ({ roles }) =>
      roles.some((n) => ['superadmin', 'admin', 'accountant', 'computer_operator'].includes(n)),
  },
  {
    id: 'notifications',
    label: 'Notifications',
    tint: '#ea580c',
    soft: '#fff7ed',
    visible: ({ roles }) =>
      roles.some((n) =>
        ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge', 'teacher', 'computer_operator'].includes(n),
      ),
  },
  {
    id: 'leave',
    label: 'Leave',
    tint: '#4f46e5',
    soft: '#eef2ff',
    visible: ({ roles }) =>
      roles.some((n) => ['superadmin', 'admin', 'section_head'].includes(n)),
  },
  {
    id: 'users',
    label: 'Users',
    tint: '#334155',
    soft: '#f1f5f9',
    visible: ({ roles }) =>
      roles.some((n) => ['superadmin', 'admin', 'computer_operator'].includes(n)),
  },
  {
    id: 'configuration',
    label: 'Configuration',
    tint: '#64748b',
    soft: '#f8fafc',
    visible: ({ roles, permissions }) =>
      roles.some((n) => ['superadmin', 'admin'].includes(n))
      || permissions.some((p) => CONFIG_PERMS.includes(p)),
  },
  {
    id: 'permissions',
    label: 'Permissions',
    tint: '#b45309',
    soft: '#fffbeb',
    visible: ({ roles }) => roles.includes('superadmin'),
  },
]

export function staffFeaturesFor({ roles = [], permissions = [] }) {
  const ctx = { roles, permissions }
  return STAFF_FEATURE_DEFS.filter((f) => f.visible(ctx)).map(
    ({ id, label, tint, soft }) => ({ id, label, ready: isReady(id), tint, soft }),
  )
}

export function learnerFeatures() {
  return LEARNER_FEATURE_DEFS.map((f) => ({ ...f, ready: isReady(f.id) }))
}
