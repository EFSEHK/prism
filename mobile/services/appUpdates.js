import { checkOtaUpdate } from './otaUpdate'
import { checkApkUpdate } from './apkUpdate'

/** OTA first (with progress UI), then APK prompt if native build is behind API. */
export async function runStartupUpdateChecks({ onStatus } = {}) {
  const reloaded = await checkOtaUpdate({ onStatus })
  if (reloaded) {
    return
  }

  await checkApkUpdate({ onStatus })
}
