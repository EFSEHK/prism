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

function promptInstall({ version, releaseNotes, apkUrl }) {
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

/**
 * Compare installed build with API and optionally download + install APK.
 */
export async function checkApkUpdate() {
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
      apkUrl: data.apk_url,
    })

    if (!shouldUpdate) {
      return
    }

    const target = `${FileSystem.cacheDirectory}efsc-ya-update.apk`
    const download = await FileSystem.downloadAsync(data.apk_url, target)

    if (download.status !== 200) {
      await Linking.openURL(data.apk_url)
      return
    }

    await installApk(download.uri)
  } catch {
    // Non-blocking: app should still open if update check fails offline.
  }
}
