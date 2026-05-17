import axios from 'axios'
import Constants from 'expo-constants'
import { Platform } from 'react-native'

const extra = Constants.expoConfig?.extra || {}

/** Laragon API base (repo-root vhost + root .htaccess → api/public). */
export const DEFAULT_API_URL = 'http://prism.test/api'

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
    if (hostname === 'prism.test') {
      const targetIp =
        lanIp || (Platform.OS === 'android' ? '10.0.2.2' : '')
      if (targetIp) {
        baseURL = configured.replace(hostname, targetIp)
        headers.Host = 'prism.test'
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
