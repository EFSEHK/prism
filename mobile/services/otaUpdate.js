import * as Updates from 'expo-updates'

/**
 * Check for an OTA JS bundle update and apply it (production builds only).
 * Returns true if the app is reloading.
 */
export async function checkOtaUpdate() {
  if (__DEV__ || !Updates.isEnabled) {
    return false
  }

  try {
    const result = await Updates.checkForUpdateAsync()
    if (!result.isAvailable) {
      return false
    }

    await Updates.fetchUpdateAsync()
    await Updates.reloadAsync()
    return true
  } catch {
    return false
  }
}
