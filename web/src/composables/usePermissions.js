import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export function usePermissions() {
  const auth = useAuthStore()

  const permissionNames = computed(() => (auth.user?.permissions || []).map((p) => p.name))

  function can(name) {
    return permissionNames.value.includes(name)
  }

  const canManageAcademic = computed(() => can('manage_academic_structure'))
  const canManageRoster = computed(() => can('manage_student_roster'))
  const canConfigure = computed(() => canManageAcademic.value || canManageRoster.value)

  return {
    permissionNames,
    can,
    canManageAcademic,
    canManageRoster,
    canConfigure,
  }
}
