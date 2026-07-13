import { computed, defineComponent, h } from 'vue'
import { useModulesStore } from '../stores/modules'
import ComingSoonView from '../views/ComingSoonView.vue'

/**
 * Renders LiveComponent when the module catalog status is `live`;
 * otherwise shows the shared "Coming soon" page.
 */
export function catalogView(moduleId, LiveComponent, options = {}) {
  const title = options.title || moduleId

  return defineComponent({
    name: `CatalogView_${moduleId}`,
    setup(_, { attrs }) {
      const modules = useModulesStore()
      const status = computed(() => modules.moduleStatus(moduleId))

      return () => {
        if (status.value !== 'live') {
          return h(ComingSoonView, { title })
        }
        return h(LiveComponent, { ...attrs })
      }
    },
  })
}
