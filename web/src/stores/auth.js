import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'
import { useViewAsStore } from './viewAs'
import { useModulesStore } from './modules'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('efsc_token') || '')
  const user = ref(JSON.parse(localStorage.getItem('efsc_user') || 'null'))

  const isAuthenticated = computed(() => !!token.value)

  function setSession(accessToken, userPayload) {
    token.value = accessToken
    user.value = userPayload
    localStorage.setItem('efsc_token', accessToken)
    localStorage.setItem('efsc_user', JSON.stringify(userPayload))
    api.defaults.headers.common.Authorization = `Bearer ${accessToken}`
  }

  function clearSession() {
    token.value = ''
    user.value = null
    localStorage.removeItem('efsc_token')
    localStorage.removeItem('efsc_user')
    delete api.defaults.headers.common.Authorization
    useViewAsStore().clear()
    useModulesStore().clear()
  }

  async function login(email, password) {
    const { data } = await api.post('/login', { email, password })
    setSession(data.access_token, data.user)
    try {
      await useModulesStore().fetchModules('web')
    } catch {
      /* nav will retry on mount */
    }
    return data
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch {
      /* ignore */
    }
    clearSession()
  }

  if (token.value) {
    api.defaults.headers.common.Authorization = `Bearer ${token.value}`
  }

  return { token, user, isAuthenticated, login, logout, setSession, clearSession }
})
