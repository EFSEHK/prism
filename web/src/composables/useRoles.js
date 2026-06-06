import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

export function useRoles() {
  const auth = useAuthStore()

  const roles = computed(() => (auth.user?.roles || []).map((r) => r.name))

  const isSuperadmin = computed(() => roles.value.includes('superadmin'))
  const isParent = computed(() => roles.value.includes('parent'))
  const isStudent = computed(() => roles.value.includes('student'))
  const isLearner = computed(() => isParent.value || isStudent.value)

  const canApprove = computed(() =>
    roles.value.some((n) =>
      ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge'].includes(n)
    )
  )

  const canStaff = computed(() =>
    roles.value.some((n) =>
      ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge', 'teacher', 'computer_operator'].includes(n)
    )
  )

  const canTimetable = computed(() =>
    roles.value.some((n) => ['superadmin', 'admin', 'computer_operator', 'teacher'].includes(n))
  )

  const canFees = computed(() =>
    roles.value.some((n) => ['superadmin', 'admin', 'accountant', 'computer_operator'].includes(n))
  )

  const canBroadcasts = computed(() =>
    roles.value.some((n) =>
      ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge', 'teacher', 'computer_operator'].includes(n)
    )
  )

  const canLeave = computed(() =>
    roles.value.some((n) =>
      ['superadmin', 'admin', 'section_head', 'parent'].includes(n)
    )
  )

  return {
    roles,
    isSuperadmin,
    isParent,
    isStudent,
    isLearner,
    canApprove,
    canStaff,
    canTimetable,
    canFees,
    canBroadcasts,
    canLeave,
  }
}
