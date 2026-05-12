import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/client'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('prism_token') || '')
  const user = ref(JSON.parse(localStorage.getItem('prism_user') || 'null'))

  const isAuthenticated = computed(() => !!token.value)

  function setSession(accessToken, userPayload) {
    token.value = accessToken
    user.value = userPayload
    localStorage.setItem('prism_token', accessToken)
    localStorage.setItem('prism_user', JSON.stringify(userPayload))
    api.defaults.headers.common.Authorization = `Bearer ${accessToken}`
  }

  function clearSession() {
    token.value = ''
    user.value = null
    localStorage.removeItem('prism_token')
    localStorage.removeItem('prism_user')
    delete api.defaults.headers.common.Authorization
  }

  if (token.value) {
    api.defaults.headers.common.Authorization = `Bearer ${token.value}`
  }

  async function login(email, password) {
    const { data } = await api.post('/login', { email, password })
    setSession(data.access_token, data.user)
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch {
      /* ignore */
    }
    clearSession()
  }

  return { token, user, isAuthenticated, login, logout, setSession, clearSession }
})
