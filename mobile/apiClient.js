import axios from 'axios'
import Constants from 'expo-constants'
import { Platform } from 'react-native'

const extra = Constants.expoConfig?.extra || {}

/** Laragon API base (repo-root vhost + root .htaccess → api/public). */
export const DEFAULT_API_URL = 'http://EFSC-YA.test/api'

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
    // Browser on the dev PC can use EFSC-YA.test directly (same hosts file as Laragon).
    if (hostname === 'EFSC-YA.test' && Platform.OS !== 'web') {
      const targetIp =
        lanIp || (Platform.OS === 'android' ? '10.0.2.2' : '')
      if (targetIp) {
        baseURL = configured.replace(hostname, targetIp)
        headers.Host = 'EFSC-YA.test'
        bridgeHost = targetIp
      }
    }
  } catch {
    /* use configured as-is */
  }

  return {
    displayUrl: configured,
    bridgeHost,
    client: axios.create({ baseURL, timeout: 20000, headers }),
  }
}

const api = resolveApi()

export const API_DISPLAY = api.displayUrl
export const API_BRIDGE_HOST = api.bridgeHost
export const apiClient = api.client
export const USES_EMULATOR_API = api.displayUrl.includes('10.0.2.2')

/** Set Bearer token immediately (use before async calls right after login). */
export function setAuthToken(token) {
  if (token) {
    apiClient.defaults.headers.common.Authorization = `Bearer ${token}`
  } else {
    delete apiClient.defaults.headers.common.Authorization
  }
}
