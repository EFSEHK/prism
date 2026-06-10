export const ROLE_LABELS = {
  admin: 'Admin',
  principal: 'Principal',
  vice_principal: 'Vice Principal',
  section_head: 'Section Head',
  class_incharge: 'Class Incharge',
  teacher: 'Teacher',
  parent: 'Parent',
  student: 'Student',
  computer_operator: 'Computer Operator',
  accountant: 'Accountant',
}

export function roleLabel(name) {
  if (!name) return ''
  return ROLE_LABELS[name] || name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}
