import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'

/** Admin shell links hidden while SuperAdmin is impersonating another user. */
export const ADMIN_SHELL_MODULE_IDS = ['users', 'configuration', 'permissions']

export const useModulesStore = defineStore('modules', () => {
  const items = ref([])
  const loaded = ref(false)
  const loading = ref(false)
  const error = ref('')

  const enabledIds = computed(() => new Set(
    items.value.filter((m) => m.enabled !== false).map((m) => m.id),
  ))

  function isEnabled(id) {
    return enabledIds.value.has(id)
  }

  function moduleById(id) {
    return items.value.find((m) => m.id === id) || null
  }

  function clear() {
    items.value = []
    loaded.value = false
    loading.value = false
    error.value = ''
  }

  async function fetchModules(platform = 'web') {
    loading.value = true
    error.value = ''
    try {
      const { data } = await api.get('/efsc/modules', { params: { platform } })
      const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
      items.value = list.filter((m) => m && m.enabled !== false)
      loaded.value = true
      return items.value
    } catch (e) {
      error.value = e?.message || 'Failed to load modules'
      items.value = []
      loaded.value = false
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    loaded,
    loading,
    error,
    enabledIds,
    isEnabled,
    moduleById,
    clear,
    fetchModules,
  }
})
