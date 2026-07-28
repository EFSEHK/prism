import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'

/** Admin shell links hidden while SuperAdmin is impersonating another user. */
export const ADMIN_SHELL_MODULE_IDS = ['users', 'configuration', 'permissions', 'apps', 'aims-import']

export const useModulesStore = defineStore('modules', () => {
  const items = ref([])
  const loaded = ref(false)
  const loading = ref(false)
  const error = ref('')

  const enabledIds = computed(() => new Set(
    items.value
      .filter((m) => {
        if (m.status === 'disabled') return false
        if (m.enabled === false) return false
        return true
      })
      .map((m) => m.id),
  ))

  function isEnabled(id) {
    return enabledIds.value.has(id)
  }

  function moduleById(id) {
    return items.value.find((m) => m.id === id) || null
  }

  function hasModule(id) {
    return items.value.some((m) => m.id === id)
  }

  /**
   * Module catalog is the source of truth for nav/portal visibility once loaded.
   * Pass a fallback for the brief window before /efsc/modules returns.
   */
  function canAccessModule(id, fallback = false) {
    if (!loaded.value) return fallback
    return hasModule(id)
  }

  function moduleStatus(idOrModule) {
    const m = typeof idOrModule === 'string' ? moduleById(idOrModule) : idOrModule
    if (!m) return 'disabled'
    if (m.status === 'live' || m.status === 'coming_soon' || m.status === 'disabled') return m.status
    if (m.enabled === false) return 'disabled'
    if (m.coming_soon === true) return 'coming_soon'
    return 'live'
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
      items.value = list.filter((m) => m && m.status !== 'disabled' && m.enabled !== false)
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
    hasModule,
    canAccessModule,
    moduleStatus,
    clear,
    fetchModules,
  }
})
