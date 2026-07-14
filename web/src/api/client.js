import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
})

let handlingUnauthorized = false

function isAuthExemptRequest(config) {
  const url = String(config?.url || '')
  return url.includes('/login')
}

/**
 * Clear stale sessions and send the user to login on any API 401
 * (expired token, revoked token, middleware rejection, etc.).
 * Call once after Pinia + router are installed.
 */
export function installAuthInterceptor({ getAuthStore, router }) {
  api.interceptors.response.use(
    (response) => response,
    (error) => {
      const status = error.response?.status
      if (status === 401 && !isAuthExemptRequest(error.config)) {
        if (!handlingUnauthorized) {
          handlingUnauthorized = true
          try {
            const auth = getAuthStore()
            if (auth.token) {
              auth.clearSession()
            }
            const current = router.currentRoute.value
            if (current.path !== '/login') {
              const redirect = current.fullPath && current.fullPath !== '/' ? current.fullPath : undefined
              router.push(redirect ? { path: '/login', query: { redirect } } : '/login')
            }
          } finally {
            queueMicrotask(() => {
              handlingUnauthorized = false
            })
          }
        }
      }
      return Promise.reject(error)
    },
  )
}

export default api
