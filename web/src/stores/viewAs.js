import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'
import { roleLabel } from '../constants/roleLabels'

const ROLE_STORAGE_KEY = 'efsc_view_as'
const USER_STORAGE_KEY = 'efsc_view_as_user'

export const useViewAsStore = defineStore('viewAs', () => {
  const role = ref(localStorage.getItem(ROLE_STORAGE_KEY) || '')
  const permissions = ref([])
  const options = ref([])
  const loaded = ref(false)
  const impersonateUser = ref(JSON.parse(localStorage.getItem(USER_STORAGE_KEY) || 'null'))

  const active = computed(() => !!role.value && !impersonateUser.value)
  const isImpersonating = computed(() => !!impersonateUser.value)
  const label = computed(() => (active.value ? roleLabel(role.value) : ''))

  function syncHeaders() {
    if (impersonateUser.value?.id) {
      api.defaults.headers.common['X-View-As-User'] = String(impersonateUser.value.id)
      delete api.defaults.headers.common['X-View-As-Role']
      return
    }
    delete api.defaults.headers.common['X-View-As-User']
    if (role.value) {
      api.defaults.headers.common['X-View-As-Role'] = role.value
    } else {
      delete api.defaults.headers.common['X-View-As-Role']
    }
  }

  function clearRole() {
    role.value = ''
    permissions.value = []
    localStorage.removeItem(ROLE_STORAGE_KEY)
    syncHeaders()
  }

  function applyOption(option) {
    if (!option) {
      clearRole()
      return
    }
    impersonateUser.value = null
    localStorage.removeItem(USER_STORAGE_KEY)
    role.value = option.name
    permissions.value = option.permissions || []
    localStorage.setItem(ROLE_STORAGE_KEY, option.name)
    syncHeaders()
  }

  function setRole(name) {
    if (!name) {
      clearRole()
      return
    }
    const option = options.value.find((r) => r.name === name)
    if (option) {
      applyOption(option)
    }
  }

  function startImpersonation(user) {
    clearRole()
    const permissionNames = (user.permissions || []).map((p) => (typeof p === 'string' ? p : p.name))
    impersonateUser.value = {
      id: user.id,
      name: user.name,
      email: user.email,
      roles: user.roles || [],
      permissions: permissionNames,
    }
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(impersonateUser.value))
    syncHeaders()
  }

  function stopImpersonation() {
    impersonateUser.value = null
    localStorage.removeItem(USER_STORAGE_KEY)
    syncHeaders()
  }

  function clear() {
    clearRole()
    stopImpersonation()
  }

  async function loadOptions() {
    if (loaded.value) return options.value
    const { data } = await api.get('/view-as/roles')
    options.value = data
    loaded.value = true
    if (role.value && !impersonateUser.value) {
      const match = data.find((r) => r.name === role.value)
      if (match) {
        permissions.value = match.permissions || []
        syncHeaders()
      } else {
        clearRole()
      }
    }
    return data
  }

  syncHeaders()

  return {
    role,
    permissions,
    options,
    loaded,
    impersonateUser,
    active,
    isImpersonating,
    label,
    setRole,
    startImpersonation,
    stopImpersonation,
    clear,
    clearRole,
    loadOptions,
    syncHeaders,
  }
})
