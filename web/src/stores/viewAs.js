import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'
import { roleLabel } from '../constants/roleLabels'

const STORAGE_KEY = 'efsc_view_as'

export const useViewAsStore = defineStore('viewAs', () => {
  const role = ref(localStorage.getItem(STORAGE_KEY) || '')
  const permissions = ref([])
  const options = ref([])
  const loaded = ref(false)

  const active = computed(() => !!role.value)
  const label = computed(() => (active.value ? roleLabel(role.value) : ''))

  function syncHeader() {
    if (role.value) {
      api.defaults.headers.common['X-View-As-Role'] = role.value
    } else {
      delete api.defaults.headers.common['X-View-As-Role']
    }
  }

  function applyOption(option) {
    if (!option) {
      clear()
      return
    }
    role.value = option.name
    permissions.value = option.permissions || []
    localStorage.setItem(STORAGE_KEY, option.name)
    syncHeader()
  }

  function setRole(name) {
    if (!name) {
      clear()
      return
    }
    const option = options.value.find((r) => r.name === name)
    if (option) {
      applyOption(option)
    }
  }

  function clear() {
    role.value = ''
    permissions.value = []
    localStorage.removeItem(STORAGE_KEY)
    syncHeader()
  }

  async function loadOptions() {
    if (loaded.value) return options.value
    const { data } = await api.get('/view-as/roles')
    options.value = data
    loaded.value = true
    if (role.value) {
      const match = data.find((r) => r.name === role.value)
      if (match) {
        permissions.value = match.permissions || []
        syncHeader()
      } else {
        clear()
      }
    }
    return data
  }

  syncHeader()

  return {
    role,
    permissions,
    options,
    loaded,
    active,
    label,
    setRole,
    clear,
    loadOptions,
    syncHeader,
  }
})
