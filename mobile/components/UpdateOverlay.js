import { View, Text, StyleSheet, ActivityIndicator, Modal } from 'react-native'

function formatBytes(bytes) {
  if (!Number.isFinite(bytes) || bytes <= 0) return ''
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

const PHASE_COPY = {
  checking: { title: 'Checking for updates', indeterminate: true },
  'ota-download': { title: 'Downloading update', indeterminate: false },
  'apk-download': { title: 'Downloading update', indeterminate: false },
  installing: { title: 'Preparing install', indeterminate: true },
  restarting: { title: 'Restarting app', indeterminate: true },
}

export default function UpdateOverlay({ phase, progress = 0, bytesWritten = 0, bytesTotal = 0 }) {
  if (!phase) return null

  const copy = PHASE_COPY[phase] || { title: 'Updating', indeterminate: true }
  const pct = Math.min(100, Math.max(0, Math.round(progress * 100)))
  const showBar = !copy.indeterminate && pct >= 0
  const byteLabel =
    bytesTotal > 0
      ? `${formatBytes(bytesWritten)} / ${formatBytes(bytesTotal)}`
      : showBar && pct > 0
        ? `${pct}%`
        : ''

  return (
    <Modal visible transparent animationType="fade" statusBarTranslucent>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <Text style={styles.title}>{copy.title}</Text>
          {showBar ? (
            <>
              <View style={styles.track}>
                <View style={[styles.fill, { width: `${pct}%` }]} />
              </View>
              <Text style={styles.meta}>{byteLabel || `${pct}%`}</Text>
            </>
          ) : (
            <ActivityIndicator size="large" color="#0f766e" style={styles.spinner} />
          )}
          <Text style={styles.hint}>Please keep the app open.</Text>
        </View>
      </View>
    </Modal>
  )
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.55)',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  card: {
    width: '100%',
    maxWidth: 320,
    backgroundColor: '#fff',
    borderRadius: 14,
    padding: 22,
    alignItems: 'stretch',
    shadowColor: '#000',
    shadowOpacity: 0.15,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
    elevation: 6,
  },
  title: {
    fontSize: 17,
    fontWeight: '700',
    color: '#0f172a',
    textAlign: 'center',
    marginBottom: 16,
  },
  track: {
    height: 8,
    borderRadius: 999,
    backgroundColor: '#e2e8f0',
    overflow: 'hidden',
  },
  fill: {
    height: '100%',
    backgroundColor: '#0f766e',
    borderRadius: 999,
  },
  meta: {
    marginTop: 10,
    fontSize: 13,
    color: '#475569',
    textAlign: 'center',
    fontWeight: '600',
  },
  spinner: {
    marginVertical: 8,
  },
  hint: {
    marginTop: 14,
    fontSize: 12,
    color: '#94a3b8',
    textAlign: 'center',
  },
})
