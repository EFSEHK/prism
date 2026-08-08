import axios from 'axios'
import Constants from 'expo-constants'
import { Platform } from 'react-native'

const extra = Constants.expoConfig?.extra || {}

/** Fallback when no .env — production API. */
export const DEFAULT_API_URL = 'https://sap-api.innovisiq.com/api'

/** Laragon vhosts bridged to LAN IP on physical devices (dev only). */
const DEV_VHOSTS = ['EFSC-YA.test', 'prism.test']

function resolveApi() {
  const configured =
    process.env.EXPO_PUBLIC_API_URL || extra.apiUrl || DEFAULT_API_URL

  const lanIp =
    process.env.EXPO_PUBLIC_API_LAN_IP || extra.lanIp || ''

  let baseURL = configured
  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  }
  let bridgeHost = null

  try {
    const { hostname } = new URL(configured)
    if (DEV_VHOSTS.includes(hostname) && Platform.OS !== 'web') {
      // 10.0.2.2 is the Android emulator → host loopback only. Physical phones
      // must use EXPO_PUBLIC_API_LAN_IP (same Wi‑Fi as the PC).
      const emulatorFallback =
        Platform.OS === 'android' && Constants.isDevice === false ? '10.0.2.2' : ''
      const targetIp = lanIp || emulatorFallback
      if (targetIp) {
        baseURL = configured.replace(hostname, targetIp)
        headers.Host = hostname
        bridgeHost = targetIp
      }
    }
  } catch {
    /* use configured as-is */
  }

  return {
    displayUrl: configured,
    bridgeHost,
    baseURL,
    client: axios.create({ baseURL, timeout: 20000, headers }),
  }
}

const api = resolveApi()

export const API_DISPLAY = api.displayUrl
export const API_BRIDGE_HOST = api.bridgeHost
export const API_BASE = api.baseURL
export const apiClient = api.client
export const USES_EMULATOR_API = api.baseURL.includes('10.0.2.2')

/** Set Bearer token immediately (use before async calls right after login). */
export function setAuthToken(token) {
  if (token) {
    apiClient.defaults.headers.common.Authorization = `Bearer ${token}`
  } else {
    delete apiClient.defaults.headers.common.Authorization
  }
}

/** Clear both view-as headers. */
export function clearViewAs() {
  delete apiClient.defaults.headers.common['X-View-As-Role']
  delete apiClient.defaults.headers.common['X-View-As-User']
}

/** Preview another role (superadmin only). Clears user impersonation. */
export function setViewAsRole(roleName) {
  delete apiClient.defaults.headers.common['X-View-As-User']
  if (roleName) {
    apiClient.defaults.headers.common['X-View-As-Role'] = roleName
  } else {
    delete apiClient.defaults.headers.common['X-View-As-Role']
  }
}

/** Impersonate another user (superadmin only). Clears role preview. */
export function setViewAsUser(userId) {
  delete apiClient.defaults.headers.common['X-View-As-Role']
  if (userId) {
    apiClient.defaults.headers.common['X-View-As-User'] = String(userId)
  } else {
    delete apiClient.defaults.headers.common['X-View-As-User']
  }
}
