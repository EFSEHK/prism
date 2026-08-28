import * as Updates from 'expo-updates'

/**
 * Check for an OTA JS bundle update and apply it (production builds only).
 * Returns true if the app is reloading.
 * @param {{ onStatus?: (status: object | null) => void }} options
 */
export async function checkOtaUpdate({ onStatus } = {}) {
  if (__DEV__ || !Updates.isEnabled) {
    return false
  }

  try {
    onStatus?.({ phase: 'checking', progress: 0 })
    const result = await Updates.checkForUpdateAsync()
    if (!result.isAvailable) {
      onStatus?.(null)
      return false
    }

    onStatus?.({ phase: 'ota-download', progress: 0 })
    await Updates.fetchUpdateAsync()
    onStatus?.({ phase: 'restarting', progress: 1 })
    await Updates.reloadAsync()
    return true
  } catch {
    onStatus?.(null)
    return false
  }
}
