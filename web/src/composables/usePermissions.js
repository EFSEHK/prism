import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useViewAsStore } from '../stores/viewAs'

const ACADEMIC_ROLES = ['superadmin', 'admin', 'developer', 'computer_operator']
const ROSTER_ROLES = ['computer_operator', 'section_head', 'class_incharge']
const AIMS_IMPORT_ROLES = ['superadmin', 'developer', 'admin', 'vice_principal', 'computer_operator', 'accountant']

export function usePermissions() {
  const auth = useAuthStore()
  const viewAs = useViewAsStore()

  const permissionNames = computed(() => {
    if (viewAs.isImpersonating) return viewAs.impersonateUser?.permissions || []
    if (viewAs.active) return viewAs.permissions
    return (auth.user?.permissions || []).map((p) => p.name)
  })

  const roleNames = computed(() => {
    if (viewAs.isImpersonating) {
      return (viewAs.impersonateUser?.roles || []).map((r) => r.name)
    }
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
  const canImportAims = computed(
    () => can('import_aims_data') || hasRole(...AIMS_IMPORT_ROLES),
  )

  return {
    permissionNames,
    roleNames,
    can,
    hasRole,
    canManageAcademic,
    canManageRoster,
    canConfigure,
    canImportAims,
  }
}
