import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useViewAsStore } from '../stores/viewAs'

const ACADEMIC_ROLES = ['superadmin', 'admin', 'developer', 'computer_operator']
const ROSTER_ROLES = ['computer_operator', 'section_head', 'class_incharge']

export function usePermissions() {
  const auth = useAuthStore()
  const viewAs = useViewAsStore()

  const permissionNames = computed(() => {
    if (viewAs.active) return viewAs.permissions
    return (auth.user?.permissions || []).map((p) => p.name)
  })

  const roleNames = computed(() => {
    if (viewAs.active) return [viewAs.role]
    return (auth.user?.roles || []).map((r) => r.name)
  })

  function can(name) {
    return permissionNames.value.includes(name)
  }

  function hasRole(...names) {
    return roleNames.value.some((n) => names.includes(n))
  }

  const canManageAcademic = computed(
    () => can('manage_academic_structure') || hasRole(...ACADEMIC_ROLES),
  )
  const canManageRoster = computed(
    () => can('manage_student_roster') || hasRole(...ROSTER_ROLES),
  )
  const canConfigure = computed(() => canManageAcademic.value || canManageRoster.value)

  return {
    permissionNames,
    roleNames,
    can,
    hasRole,
    canManageAcademic,
    canManageRoster,
    canConfigure,
  }
}
