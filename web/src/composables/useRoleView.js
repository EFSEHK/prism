import { computed, defineComponent, h } from 'vue'
import { useRoles } from './useRoles'

export function roleView(LearnerComponent, StaffComponent) {
  return defineComponent({
    name: 'RoleView',
    setup() {
      const { isLearner } = useRoles()
      const resolved = computed(() => (isLearner.value ? LearnerComponent : StaffComponent))
      return () => h(resolved.value)
    },
  })
}
