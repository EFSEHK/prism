import { useState, useEffect } from 'react'
import {
  View,
  Text,
  TextInput,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Pressable,
  Keyboard,
  Platform,
} from 'react-native'
import { StatusBar } from 'expo-status-bar'
import {
  apiClient,
  API_DISPLAY,
  API_BRIDGE_HOST,
  USES_EMULATOR_API,
} from './apiClient'

function formatError(e) {
  const data = e.response?.data
  if (data?.errors) {
    return Object.values(data.errors).flat().join('\n')
  }
  if (data?.message) return data.message
  if (e.code === 'ECONNABORTED') {
    return 'Request timed out. Is Laragon running and is your phone on the same Wi‑Fi?'
  }
  if (e.message === 'Network Error' || e.code === 'ERR_NETWORK') {
    return [
      'Cannot reach the API.',
      `Configured: ${API_DISPLAY}`,
      API_BRIDGE_HOST
        ? `Tried via ${API_BRIDGE_HOST} (Host: prism.test).`
        : 'Set EXPO_PUBLIC_API_LAN_IP to your PC IP (ipconfig).',
      'Laragon must be running; phone and PC on the same Wi‑Fi.',
    ].join('\n')
  }
  return e.message || 'Login failed'
}

export default function App() {
  const [email, setEmail] = useState('parent@school.test')
  const [password, setPassword] = useState('Parent.123')
  const [token, setToken] = useState('')
  const [dashboard, setDashboard] = useState(null)
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')

  useEffect(() => {
    if (token) apiClient.defaults.headers.common.Authorization = `Bearer ${token}`
    else delete apiClient.defaults.headers.common.Authorization
  }, [token])

  async function login() {
    Keyboard.dismiss()
    setErr('')
    setLoading(true)
    try {
      const { data } = await apiClient.post('/login', { email, password })
      if (!data?.access_token) {
        throw new Error('No access token in response')
      }
      setToken(data.access_token)
      const dash = await apiClient.get('/prism/parent/dashboard', {
        params: { include: 'homework,timetable' },
      })
      setDashboard(dash.data)
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }

  async function logout() {
    try {
      await apiClient.post('/logout')
    } catch {
      /* ignore */
    }
    setToken('')
    setDashboard(null)
    setErr('')
  }

  return (
    <ScrollView
      contentContainerStyle={styles.container}
      keyboardShouldPersistTaps="handled"
    >
      <StatusBar style="dark" />
      <Text style={styles.title}>PRISM</Text>
      <Text style={styles.hint}>API: {API_DISPLAY}</Text>
      {API_BRIDGE_HOST ? (
        <Text style={styles.hint}>Via LAN: {API_BRIDGE_HOST} (Host: prism.test)</Text>
      ) : null}
      {USES_EMULATOR_API ? (
        <Text style={styles.warn}>
          Default URL is for Android emulator only. Copy mobile/.env.example to .env
          for Laragon (prism.test).
        </Text>
      ) : null}
      {!token ? (
        <View>
          <Text>Email</Text>
          <TextInput
            style={styles.input}
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            keyboardType="email-address"
            editable={!loading}
          />
          <Text>Password</Text>
          <TextInput
            style={styles.input}
            value={password}
            onChangeText={setPassword}
            secureTextEntry
            editable={!loading}
            onSubmitEditing={login}
          />
          {err ? <Text style={styles.err}>{err}</Text> : null}
          {loading ? (
            <View style={styles.loadingRow}>
              <ActivityIndicator size="small" />
              <Text style={styles.loadingText}>Logging in…</Text>
            </View>
          ) : null}
          <Pressable
            style={({ pressed }) => [
              styles.button,
              pressed && styles.buttonPressed,
              loading && styles.buttonDisabled,
            ]}
            onPress={login}
            disabled={loading}
          >
            <Text style={styles.buttonText}>{loading ? 'Please wait…' : 'Login'}</Text>
          </Pressable>
        </View>
      ) : (
        <View>
          <Text style={styles.ok}>Logged in</Text>
          {loading ? <ActivityIndicator /> : null}
          {dashboard ? (
            <Text style={styles.mono}>
              Children: {dashboard.children?.length || 0}
              {'\n'}
              Unread: {dashboard.unread_notifications}
            </Text>
          ) : null}
          <Pressable style={styles.button} onPress={logout}>
            <Text style={styles.buttonText}>Logout</Text>
          </Pressable>
        </View>
      )}
    </ScrollView>
  )
}

const styles = StyleSheet.create({
  container: { padding: 20, paddingTop: 48, paddingBottom: 40 },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 8 },
  hint: { fontSize: 11, color: '#666', marginBottom: 4 },
  warn: { fontSize: 11, color: '#b45309', marginBottom: 12, lineHeight: 16 },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 6,
    padding: 10,
    marginBottom: 12,
  },
  err: { color: '#b91c1c', marginBottom: 12, lineHeight: 20 },
  ok: { color: '#15803d', marginBottom: 8 },
  mono: { fontFamily: Platform.select({ ios: 'Menlo', android: 'monospace' }), marginVertical: 12 },
  loadingRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 12 },
  loadingText: { color: '#444' },
  button: {
    backgroundColor: '#2563eb',
    paddingVertical: 14,
    borderRadius: 8,
    alignItems: 'center',
  },
  buttonPressed: { opacity: 0.85 },
  buttonDisabled: { opacity: 0.6 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
})
