import { checkOtaUpdate } from './otaUpdate'
import { checkApkUpdate } from './apkUpdate'

/** OTA first (silent), then APK prompt if native build is behind API. */
export async function runStartupUpdateChecks() {
  const reloaded = await checkOtaUpdate()
  if (reloaded) {
    return
  }

  await checkApkUpdate()
}
