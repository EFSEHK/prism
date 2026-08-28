import { Platform, Alert, Linking } from 'react-native'
import * as Application from 'expo-application'
import * as FileSystem from 'expo-file-system/legacy'
import * as IntentLauncher from 'expo-intent-launcher'
import { apiClient } from '../apiClient'

function parseVersionCode(value) {
  const parsed = parseInt(String(value || ''), 10)
  return Number.isFinite(parsed) ? parsed : 0
}

async function installApk(localUri) {
  const contentUri = await FileSystem.getContentUriAsync(localUri)
  await IntentLauncher.startActivityAsync('android.intent.action.VIEW', {
    data: contentUri,
    flags: 1,
    type: 'application/vnd.android.package-archive',
  })
}

function promptInstall({ version, releaseNotes }) {
  return new Promise((resolve) => {
    const message = [
      `Version ${version} is available.`,
      releaseNotes ? `\n${releaseNotes}` : '',
      '\nDownload and install now?',
    ].join('')

    Alert.alert('Update available', message, [
      { text: 'Later', style: 'cancel', onPress: () => resolve(false) },
      {
        text: 'Update',
        onPress: () => resolve(true),
      },
    ])
  })
}

async function downloadApk(url, target, onProgress) {
  const downloadResumable = FileSystem.createDownloadResumable(
    url,
    target,
    {},
    (progress) => {
      const total = progress.totalBytesExpectedToWrite
      const written = progress.totalBytesWritten
      const ratio = total > 0 ? written / total : 0
      onProgress?.({
        phase: 'apk-download',
        progress: ratio,
        bytesWritten: written,
        bytesTotal: total,
      })
    }
  )

  return downloadResumable.downloadAsync()
}

/**
 * Compare installed build with API and optionally download + install APK.
 * @param {{ onStatus?: (status: object | null) => void }} options
 */
export async function checkApkUpdate({ onStatus } = {}) {
  if (Platform.OS !== 'android') {
    return
  }

  try {
    const { data } = await apiClient.get('/mobile/version')
    const installedCode = parseVersionCode(Application.nativeBuildVersion)
    const remoteCode = parseVersionCode(data.version_code)

    if (!data.apk_url || remoteCode <= installedCode) {
      return
    }

    const shouldUpdate = await promptInstall({
      version: data.version,
      releaseNotes: data.release_notes,
    })

    if (!shouldUpdate) {
      return
    }

    onStatus?.({
      phase: 'apk-download',
      progress: 0,
      bytesWritten: 0,
      bytesTotal: 0,
    })

    const target = `${FileSystem.cacheDirectory}efsc-ya-update.apk`
    const download = await downloadApk(data.apk_url, target, onStatus)

    if (!download || download.status !== 200) {
      onStatus?.(null)
      await Linking.openURL(data.apk_url)
      return
    }

    onStatus?.({ phase: 'installing', progress: 1 })
    await installApk(download.uri)
    onStatus?.(null)
  } catch {
    onStatus?.(null)
    // Non-blocking: app should still open if update check fails offline.
  }
}
