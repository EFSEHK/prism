/**
 * Feature tiles for the mobile home/dashboard icon grid.
 * `ready: false` → greyed tile + Alert "Coming soon".
 */

export const LEARNER_FEATURES = [
  { id: 'homework', label: 'Homework', ready: true, tint: '#2563eb', soft: '#eff6ff' },
  { id: 'marks', label: 'Marks', ready: true, tint: '#059669', soft: '#ecfdf5' },
  { id: 'attendance', label: 'Attendance', ready: true, tint: '#d97706', soft: '#fffbeb' },
  { id: 'timetable', label: 'Timetable', ready: true, tint: '#7c3aed', soft: '#f5f3ff' },
  { id: 'notifications', label: 'Notifications', ready: true, tint: '#db2777', soft: '#fdf2f8' },
  { id: 'fees', label: 'Fees', ready: true, tint: '#0d9488', soft: '#f0fdfa' },
  { id: 'online', label: 'Online Class', ready: true, tint: '#0284c7', soft: '#f0f9ff' },
  { id: 'leave', label: 'Leave', ready: true, tint: '#ea580c', soft: '#fff7ed' },
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
    ready: true,
    visible: ({ roles, permissions }) =>
      permissions.includes('mark_attendance') || roles.includes('computer_operator'),
  },
  {
    id: 'approvals',
    label: 'Approvals',
    tint: '#059669',
    soft: '#ecfdf5',
    ready: false,
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
    ready: false,
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
    ready: false,
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
    ready: false,
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
    ready: false,
    visible: ({ roles }) =>
      roles.some((n) => ['superadmin', 'admin', 'computer_operator', 'teacher'].includes(n)),
  },
  {
    id: 'fees',
    label: 'Fees',
    tint: '#0d9488',
    soft: '#f0fdfa',
    ready: false,
    visible: ({ roles }) =>
      roles.some((n) => ['superadmin', 'admin', 'accountant', 'computer_operator'].includes(n)),
  },
  {
    id: 'notifications',
    label: 'Notifications',
    tint: '#ea580c',
    soft: '#fff7ed',
    ready: false,
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
    ready: false,
    visible: ({ roles }) =>
      roles.some((n) => ['superadmin', 'admin', 'section_head'].includes(n)),
  },
  {
    id: 'users',
    label: 'Users',
    tint: '#334155',
    soft: '#f1f5f9',
    ready: false,
    visible: ({ roles }) =>
      roles.some((n) => ['superadmin', 'admin', 'computer_operator'].includes(n)),
  },
  {
    id: 'configuration',
    label: 'Configuration',
    tint: '#64748b',
    soft: '#f8fafc',
    ready: false,
    visible: ({ roles, permissions }) =>
      roles.some((n) => ['superadmin', 'admin'].includes(n))
      || permissions.some((p) => CONFIG_PERMS.includes(p)),
  },
  {
    id: 'permissions',
    label: 'Permissions',
    tint: '#b45309',
    soft: '#fffbeb',
    ready: false,
    visible: ({ roles }) => roles.includes('superadmin'),
  },
]

export function staffFeaturesFor({ roles = [], permissions = [] }) {
  const ctx = { roles, permissions }
  return STAFF_FEATURE_DEFS.filter((f) => f.visible(ctx)).map(
    ({ id, label, ready, tint, soft }) => ({ id, label, ready, tint, soft }),
  )
}

export function learnerFeatures() {
  return LEARNER_FEATURES.map((f) => ({ ...f }))
}
