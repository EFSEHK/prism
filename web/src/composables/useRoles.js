import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export function useRoles() {
  const auth = useAuthStore()

  const roles = computed(() => (auth.user?.roles || []).map((r) => r.name))

  const isParent = computed(() => roles.value.includes('parent'))

  const canApprove = computed(() =>
    roles.value.some((n) =>
      ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge'].includes(n)
    )
  )

  const canStaff = computed(() =>
    roles.value.some((n) => ['superadmin', 'admin', 'teacher', 'section_head'].includes(n))
  )

  const canTimetable = computed(() =>
    roles.value.some((n) => ['superadmin', 'admin', 'computer_operator', 'teacher'].includes(n))
  )

  const canFees = computed(() =>
    roles.value.some((n) => ['superadmin', 'admin', 'accountant', 'computer_operator'].includes(n))
  )

  const canFeed = computed(() =>
    roles.value.some((n) => ['superadmin', 'admin', 'principal', 'vice_principal'].includes(n))
  )

  const canLeave = computed(() =>
    roles.value.some((n) =>
      ['superadmin', 'admin', 'teacher', 'principal', 'vice_principal', 'section_head'].includes(n)
    )
  )

  return {
    roles,
    isParent,
    canApprove,
    canStaff,
    canTimetable,
    canFees,
    canFeed,
    canLeave,
  }
}
