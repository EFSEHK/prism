import { useState, useEffect } from 'react'
import {
  View,
  Text,
  TextInput,
  Button,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
} from 'react-native'
import axios from 'axios'
import Constants from 'expo-constants'
import { StatusBar } from 'expo-status-bar'

const extra = Constants.expoConfig?.extra || {}
const API_BASE =
  process.env.EXPO_PUBLIC_API_URL ||
  extra.apiUrl ||
  'http://10.0.2.2:8000/api'

const client = axios.create({
  baseURL: API_BASE,
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
})

export default function App() {
  const [email, setEmail] = useState('parent@school.test')
  const [password, setPassword] = useState('Parent.123')
  const [token, setToken] = useState('')
  const [dashboard, setDashboard] = useState(null)
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')

  useEffect(() => {
    if (token) client.defaults.headers.common.Authorization = `Bearer ${token}`
    else delete client.defaults.headers.common.Authorization
  }, [token])

  async function login() {
    setErr('')
    setLoading(true)
    try {
      const { data } = await client.post('/login', { email, password })
      setToken(data.access_token)
      const dash = await client.get('/prism/parent/dashboard', {
        params: { include: 'homework,timetable' },
      })
      setDashboard(dash.data)
    } catch (e) {
      setErr(e.response?.data?.message || e.message || 'Error')
    } finally {
      setLoading(false)
    }
  }

  async function logout() {
    try {
      await client.post('/logout')
    } catch {
      /* ignore */
    }
    setToken('')
    setDashboard(null)
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <StatusBar style="dark" />
      <Text style={styles.title}>PRISM</Text>
      <Text style={styles.hint}>API: {API_BASE}</Text>
      {!token ? (
        <View>
          <Text>Email</Text>
          <TextInput style={styles.input} value={email} onChangeText={setEmail} autoCapitalize="none" />
          <Text>Password</Text>
          <TextInput style={styles.input} value={password} onChangeText={setPassword} secureTextEntry />
          {err ? <Text style={styles.err}>{err}</Text> : null}
          <Button title={loading ? '…' : 'Login'} onPress={login} disabled={loading} />
        </View>
      ) : (
        <View>
          <Text style={styles.ok}>Logged in</Text>
          {loading ? <ActivityIndicator /> : null}
          {dashboard ? (
            <Text style={styles.mono}>
              Children: {dashboard.children?.length || 0}{'\n'}
              Unread: {dashboard.unread_notifications}
            </Text>
          ) : null}
          <Button title="Logout" onPress={logout} />
        </View>
      )}
    </ScrollView>
  )
}

const styles = StyleSheet.create({
  container: { padding: 20, paddingTop: 48 },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 8 },
  hint: { fontSize: 11, color: '#666', marginBottom: 16 },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 6,
    padding: 10,
    marginBottom: 12,
  },
  err: { color: '#b91c1c', marginBottom: 8 },
  ok: { color: '#15803d', marginBottom: 8 },
  mono: { fontFamily: 'monospace', marginVertical: 12 },
})
