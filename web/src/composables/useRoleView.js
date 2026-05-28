import { computed, defineComponent, h } from 'vue'
import { useRoles } from './useRoles'

export function roleView(ParentComponent, StaffComponent) {
  return defineComponent({
    name: 'RoleView',
    setup() {
      const { isParent } = useRoles()
      const resolved = computed(() => (isParent.value ? ParentComponent : StaffComponent))
      return () => h(resolved.value)
    },
  })
}
