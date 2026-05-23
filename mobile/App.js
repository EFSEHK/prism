import { useState, useEffect, useCallback } from 'react'
import {
  View,
  Text,
  TextInput,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Pressable,
  Keyboard,
} from 'react-native'
import { StatusBar } from 'expo-status-bar'
import {
  apiClient,
  API_DISPLAY,
  API_BRIDGE_HOST,
  USES_EMULATOR_API,
  setAuthToken,
} from './apiClient'
import SideMenu, { HamburgerIcon, NAV_ITEMS } from './components/SideMenu'
import {
  HomeScreen,
  HomeworkScreen,
  MarksScreen,
  AttendanceScreen,
  TimetableScreen,
  FeedScreen,
  FeesScreen,
  OnlineClassScreen,
  LeaveScreen,
  AlertsScreen,
} from './screens/ParentScreens'
import { formatError } from './utils/format'
import { ui } from './components/ui'

const DASHBOARD_INCLUDE =
  'homework,timetable,marks,feed,fees,online_classes,leave,datesheet,notifications'

export default function App() {
  const [email, setEmail] = useState('parent@school.test')
  const [password, setPassword] = useState('Parent.123')
  const [token, setToken] = useState('')
  const [user, setUser] = useState(null)
  const [dashboard, setDashboard] = useState(null)
  const [tab, setTab] = useState('home')
  const [menuOpen, setMenuOpen] = useState(false)
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')

  useEffect(() => {
    setAuthToken(token)
  }, [token])

  const loadDashboard = useCallback(async () => {
    const { data } = await apiClient.get('/prism/parent/dashboard', {
      params: { include: DASHBOARD_INCLUDE },
    })
    setDashboard(data)
    return data
  }, [])

  async function login() {
    Keyboard.dismiss()
    setErr('')
    setLoading(true)
    setDashboard(null)
    setUser(null)
    try {
      const { data } = await apiClient.post('/login', { email, password })
      if (!data?.access_token) throw new Error('No access token in response')
      setAuthToken(data.access_token)
      setToken(data.access_token)
      setUser(data.user ?? null)
      await loadDashboard()
    } catch (e) {
      setAuthToken('')
      setToken('')
      setUser(null)
      setDashboard(null)
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
    setAuthToken('')
    setToken('')
    setUser(null)
    setDashboard(null)
    setTab('home')
    setMenuOpen(false)
    setErr('')
  }

  const showApp = token && dashboard && !err
  const activeLabel = NAV_ITEMS.find((item) => item.id === tab)?.label ?? 'Home'
  const children = dashboard?.children ?? []

  function renderTab() {
    switch (tab) {
      case 'home':
        return <HomeScreen dashboard={dashboard} user={user} />
      case 'homework':
        return <HomeworkScreen />
      case 'marks':
        return <MarksScreen />
      case 'attendance':
        return <AttendanceScreen children={children} />
      case 'timetable':
        return <TimetableScreen />
      case 'feed':
        return <FeedScreen />
      case 'fees':
        return <FeesScreen />
      case 'online':
        return <OnlineClassScreen />
      case 'leave':
        return <LeaveScreen children={children} />
      case 'alerts':
        return <AlertsScreen />
      default:
        return <HomeScreen dashboard={dashboard} user={user} />
    }
  }

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />
      {!showApp ? (
        <ScrollView contentContainerStyle={styles.login} keyboardShouldPersistTaps="handled">
          <Text style={styles.title}>PRISM</Text>
          <Text style={styles.hint}>API: {API_DISPLAY}</Text>
          {API_BRIDGE_HOST ? (
            <Text style={styles.hint}>LAN: {API_BRIDGE_HOST}</Text>
          ) : null}
          {USES_EMULATOR_API ? (
            <Text style={styles.warn}>Set mobile/.env for Laragon (prism.test).</Text>
          ) : null}
          {!token ? (
            <>
              <Text style={styles.label}>Email</Text>
              <TextInput
                style={styles.input}
                value={email}
                onChangeText={setEmail}
                autoCapitalize="none"
                keyboardType="email-address"
                editable={!loading}
              />
              <Text style={styles.label}>Password</Text>
              <TextInput
                style={styles.input}
                value={password}
                onChangeText={setPassword}
                secureTextEntry
                editable={!loading}
                onSubmitEditing={login}
              />
              {err ? <Text style={ui.err}>{err}</Text> : null}
              {loading ? <ActivityIndicator style={{ marginBottom: 12 }} /> : null}
              <Pressable
                style={[styles.button, loading && styles.buttonDisabled]}
                onPress={login}
                disabled={loading}
              >
                <Text style={styles.buttonText}>{loading ? 'Please wait…' : 'Login'}</Text>
              </Pressable>
            </>
          ) : (
            <>
              <Text style={styles.ok}>Logged in as {user?.name}</Text>
              {loading ? <ActivityIndicator /> : null}
              {err ? <Text style={ui.err}>{err}</Text> : null}
              <Pressable style={styles.button} onPress={loadDashboard}>
                <Text style={styles.buttonText}>Retry</Text>
              </Pressable>
              <Pressable style={styles.logout} onPress={logout}>
                <Text style={styles.logoutText}>Logout</Text>
              </Pressable>
            </>
          )}
        </ScrollView>
      ) : (
        <>
          <View style={styles.header}>
            <Pressable
              onPress={() => setMenuOpen(true)}
              style={styles.menuBtn}
              hitSlop={8}
              accessibilityLabel="Open menu"
            >
              <HamburgerIcon />
            </Pressable>
            <View style={styles.headerCenter}>
              <Text style={styles.headerTitle}>PRISM</Text>
              <Text style={styles.headerSubtitle}>{activeLabel}</Text>
            </View>
            <Pressable onPress={logout}>
              <Text style={styles.headerLogout}>Logout</Text>
            </Pressable>
          </View>
          <View style={styles.body}>{renderTab()}</View>
          <SideMenu
            visible={menuOpen}
            active={tab}
            onChange={setTab}
            onClose={() => setMenuOpen(false)}
          />
        </>
      )}
    </View>
  )
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#f8fafc' },
  login: { padding: 20, paddingTop: 48, paddingBottom: 40 },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 8 },
  hint: { fontSize: 11, color: '#64748b', marginBottom: 4 },
  warn: { fontSize: 11, color: '#b45309', marginBottom: 12 },
  label: { fontSize: 14, color: '#334155', marginBottom: 4 },
  input: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    backgroundColor: '#fff',
  },
  ok: { color: '#15803d', marginBottom: 8 },
  button: {
    backgroundColor: '#2563eb',
    paddingVertical: 14,
    borderRadius: 8,
    alignItems: 'center',
  },
  buttonDisabled: { opacity: 0.6 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  logout: {
    marginTop: 12,
    paddingVertical: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
  },
  logoutText: { color: '#475569', fontWeight: '600' },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 48,
    paddingBottom: 8,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e2e8f0',
  },
  menuBtn: {
    padding: 4,
    marginRight: 12,
  },
  headerCenter: {
    flex: 1,
  },
  headerTitle: { fontSize: 18, fontWeight: '700', color: '#0f172a' },
  headerSubtitle: { fontSize: 12, color: '#64748b', marginTop: 2 },
  headerLogout: { color: '#2563eb', fontWeight: '600' },
  body: { flex: 1 },
})
