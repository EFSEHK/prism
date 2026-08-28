import { useEffect, useState } from 'react'
import { useUpdates } from 'expo-updates'
import UpdateOverlay from './UpdateOverlay'
import { runStartupUpdateChecks } from '../services/appUpdates'

/**
 * Runs OTA + APK update checks on launch and shows download/install progress.
 */
export default function AppUpdateManager() {
  const [status, setStatus] = useState(null)
  const { isDownloading, downloadProgress } = useUpdates()

  useEffect(() => {
    if (status?.phase !== 'ota-download' || !isDownloading) {
      return
    }
    setStatus((prev) =>
      prev?.phase === 'ota-download'
        ? { ...prev, progress: downloadProgress ?? prev.progress ?? 0 }
        : prev
    )
  }, [status?.phase, isDownloading, downloadProgress])

  useEffect(() => {
    let cancelled = false

    runStartupUpdateChecks({
      onStatus: (next) => {
        if (!cancelled) setStatus(next)
      },
    })

    return () => {
      cancelled = true
    }
  }, [])

  if (!status?.phase) return null

  return (
    <UpdateOverlay
      phase={status.phase}
      progress={status.progress ?? 0}
      bytesWritten={status.bytesWritten ?? 0}
      bytesTotal={status.bytesTotal ?? 0}
    />
  )
}
