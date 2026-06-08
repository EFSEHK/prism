import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

const ACADEMIC_ROLES = ['superadmin', 'admin', 'developer', 'computer_operator']

export function usePermissions() {
  const auth = useAuthStore()

  const permissionNames = computed(() => (auth.user?.permissions || []).map((p) => p.name))
  const roleNames = computed(() => (auth.user?.roles || []).map((r) => r.name))

  function can(name) {
    return permissionNames.value.includes(name)
  }

  function hasRole(...names) {
    return roleNames.value.some((n) => names.includes(n))
  }

  const canManageAcademic = computed(
    () => can('manage_academic_structure') || hasRole(...ACADEMIC_ROLES),
  )
  const canManageRoster = computed(() => can('manage_student_roster'))
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
