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
  API_BRIDGE_HOST,
  USES_EMULATOR_API,
  setAuthToken,
  setViewAsRole,
  setViewAsUser,
  clearViewAs,
} from './apiClient'
import SideMenu, { HamburgerIcon, navItemsForContext } from './components/SideMenu'
import EyeIcon from './components/EyeIcon'
import ViewAsPicker from './components/ViewAsPicker'
import {
  ParentHomeScreen,
  ChildDashboardScreen,
  HomeworkScreen,
  MarksScreen,
  AttendanceScreen,
  StaffAttendanceScreen,
  TimetableScreen,
  FeedScreen,
  FeesScreen,
  OnlineClassScreen,
  LeaveScreen,
} from './screens/ParentScreens'
import { childName, formatError } from './utils/format'
import { ui } from './components/ui'
import { runStartupUpdateChecks } from './services/appUpdates'

const DASHBOARD_INCLUDE =
  'homework,timetable,marks,broadcasts,fees,online_classes,leave,datesheet,notifications'

function LogoutIcon() {
  return (
    <View style={styles.logoutIconWrap}>
      <View style={styles.logoutDoor} />
      <View style={styles.logoutArrowBody} />
      <View style={styles.logoutArrowHeadUp} />
      <View style={styles.logoutArrowHeadDown} />
    </View>
  )
}

export default function App() {
  const [email, setEmail] = useState('parent@efsc-ya.com')
  const [password, setPassword] = useState('Test.123')
  const [showPassword, setShowPassword] = useState(false)
  const [token, setToken] = useState('')
  const [user, setUser] = useState(null)
  const [dashboard, setDashboard] = useState(null)
  const [selectedChild, setSelectedChild] = useState(null)
  const [tab, setTab] = useState('home')
  const [menuOpen, setMenuOpen] = useState(false)
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')
  const [viewAsRole, setViewAsRoleState] = useState('')
  const [viewAsOptions, setViewAsOptions] = useState([])
  const [viewAsUsers, setViewAsUsers] = useState([])
  const [viewAsUsersLoading, setViewAsUsersLoading] = useState(false)
  const [impersonateUser, setImpersonateUser] = useState(null)

  useEffect(() => {
    runStartupUpdateChecks()
  }, [])

  useEffect(() => {
    setAuthToken(token)
  }, [token])

  useEffect(() => {
    if (impersonateUser?.id) {
      setViewAsUser(impersonateUser.id)
    } else {
      setViewAsRole(viewAsRole)
    }
  }, [viewAsRole, impersonateUser])

  const actualRoleNames = (user?.roles || []).map((r) => r.name)
  const isActuallySuperadmin = actualRoleNames.includes('superadmin')
  const canViewAs = isActuallySuperadmin && !impersonateUser
  const isImpersonating = Boolean(impersonateUser)

  async function loadViewAsOptions() {
    const { data } = await apiClient.get('/view-as/roles')
    setViewAsOptions(data)
    return data
  }

  async function loadViewAsUsers() {
    setViewAsUsersLoading(true)
    try {
      const { data } = await apiClient.get('/users')
      setViewAsUsers(Array.isArray(data) ? data : [])
    } catch {
      setViewAsUsers([])
    } finally {
      setViewAsUsersLoading(false)
    }
  }

  function permissionNamesFrom(source) {
    return (source?.permissions || []).map((p) => (typeof p === 'string' ? p : p.name))
  }

  async function enterContext({ roles, perms }) {
    setDashboard(null)
    setSelectedChild(null)
    setTab('home')
    setErr('')

    if (roles.includes('parent') || roles.includes('student')) {
      setLoading(true)
      try {
        await loadDashboard()
      } catch (e) {
        setErr(formatError(e))
      } finally {
        setLoading(false)
      }
    } else if (perms.includes('mark_attendance') || roles.includes('computer_operator')) {
      setTab('attendance')
    }
  }

  async function applyViewAs(roleName) {
    setImpersonateUser(null)
    setViewAsUser(null)
    setViewAsRoleState(roleName)
    setViewAsRole(roleName)

    const effectiveRoles = roleName ? [roleName] : actualRoleNames
    const option = viewAsOptions.find((r) => r.name === roleName)
    const perms = roleName
      ? (option?.permissions || [])
      : permissionNamesFrom(user)

    await enterContext({ roles: effectiveRoles, perms })
  }

  async function applyViewAsUser(summary) {
    setErr('')
    setLoading(true)
    try {
      // Call as real superadmin (clear any role preview first).
      clearViewAs()
      setViewAsRoleState('')
      const { data } = await apiClient.get(`/users/${summary.id}`)
      const permissionNames = permissionNamesFrom(data)
      const next = {
        id: data.id,
        name: data.name,
        email: data.email,
        roles: data.roles || [],
        permissions: permissionNames,
      }
      setImpersonateUser(next)
      setViewAsUser(next.id)
      setLoading(false)
      const roles = (next.roles || []).map((r) => r.name)
      await enterContext({ roles, perms: permissionNames })
    } catch (e) {
      clearViewAs()
      setImpersonateUser(null)
      setLoading(false)
      setErr(formatError(e))
    }
  }

  async function exitImpersonation() {
    setImpersonateUser(null)
    setViewAsUser(null)
    setViewAsRoleState('')
    setViewAsRole('')
    setDashboard(null)
    setSelectedChild(null)
    setTab('home')
    setErr('')
    setMenuOpen(false)
  }

  const loadDashboard = useCallback(async (studentId = null) => {
    const params = { include: DASHBOARD_INCLUDE }
    if (studentId) {
      params.student_id = studentId
    }
    const { data } = await apiClient.get('/efsc/learner/dashboard', { params })
    setDashboard(data)
    return data
  }, [])

  async function login() {
    Keyboard.dismiss()
    setErr('')
    setLoading(true)
    setDashboard(null)
    setUser(null)
    setSelectedChild(null)
    setTab('home')
    try {
      const { data } = await apiClient.post('/login', { email, password })
      if (!data?.access_token) throw new Error('No access token in response')
      setAuthToken(data.access_token)
      setToken(data.access_token)
      setUser(data.user ?? null)
      setViewAsRoleState('')
      setViewAsRole('')
      setViewAsUser(null)
      setImpersonateUser(null)
      setViewAsOptions([])
      setViewAsUsers([])
      const roles = (data.user?.roles || []).map((r) => r.name)
      const perms = (data.user?.permissions || []).map((p) => p.name)
      const privileged = roles.includes('superadmin')
      if (privileged) {
        try {
          await Promise.all([loadViewAsOptions(), loadViewAsUsers()])
        } catch {
          /* ignore */
        }
      }
      if (roles.includes('parent') || roles.includes('student')) {
        await loadDashboard()
      } else if (perms.includes('mark_attendance') || roles.includes('computer_operator')) {
        setTab('attendance')
      }
    } catch (e) {
      setAuthToken('')
      setToken('')
      setUser(null)
      setDashboard(null)
      setSelectedChild(null)
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
    clearViewAs()
    setToken('')
    setUser(null)
    setDashboard(null)
    setSelectedChild(null)
    setViewAsRoleState('')
    setViewAsOptions([])
    setViewAsUsers([])
    setImpersonateUser(null)
    setTab('home')
    setMenuOpen(false)
    setErr('')
  }

  async function selectChild(child) {
    setLoading(true)
    setErr('')
    try {
      await loadDashboard(child.id)
      setSelectedChild(child)
      setTab('dashboard')
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }

  function switchChild() {
    setSelectedChild(null)
    setTab('home')
    loadDashboard().catch(() => {})
  }

  function handleNavChange(id) {
    if (id === 'home' && selectedChild) {
      switchChild()
      return
    }
    setTab(id)
  }

  const roleNames = impersonateUser
    ? (impersonateUser.roles || []).map((r) => r.name)
    : viewAsRole
      ? [viewAsRole]
      : actualRoleNames
  const viewAsOption = viewAsOptions.find((r) => r.name === viewAsRole)
  const permissions = impersonateUser
    ? (impersonateUser.permissions || [])
    : viewAsRole
      ? (viewAsOption?.permissions || [])
      : (user?.permissions || []).map((p) => p.name)
  const isLearnerRole = roleNames.includes('parent') || roleNames.includes('student')
  const isStaffAttendance =
    permissions.includes('mark_attendance') || roleNames.includes('computer_operator')
  const showApp = token && !err && (isLearnerRole ? !!dashboard : true)
  const hasSelectedChild = Boolean(selectedChild)
  const navItems = navItemsForContext(hasSelectedChild, isStaffAttendance && !isLearnerRole)
  const activeLabel = navItems.find((item) => item.id === tab)?.label ?? 'Home'
  const children = dashboard?.children ?? []
  const headerSubtitle = activeLabel
  const selectedChildLabel = hasSelectedChild ? childName(selectedChild) : ''
  const headerRightName = selectedChildLabel
    || (impersonateUser?.name)
    || user?.name
    || ''

  function renderTab() {
    if (!isLearnerRole) {
      if (isStaffAttendance) {
        return <StaffAttendanceScreen />
      }
      return (
        <View style={styles.staffMsg}>
          <Text style={styles.staffMsgTitle}>Staff account</Text>
          <Text style={styles.hint}>Use the EFSC-YA web app for staff features. Mobile is optimized for parents and students.</Text>
        </View>
      )
    }
    if (!hasSelectedChild) {
      return (
        <ParentHomeScreen dashboard={dashboard} user={user} onSelectChild={selectChild} />
      )
    }

    switch (tab) {
      case 'dashboard':
        return <ChildDashboardScreen dashboard={dashboard} child={selectedChild} user={user} />
      case 'homework':
        return <HomeworkScreen />
      case 'marks':
        return <MarksScreen />
      case 'attendance':
        return <AttendanceScreen children={children} selectedChildId={selectedChild.id} />
      case 'timetable':
        return <TimetableScreen />
      case 'notifications':
        return <FeedScreen />
      case 'fees':
        return <FeesScreen />
      case 'online':
        return <OnlineClassScreen />
      case 'leave':
        return <LeaveScreen children={children} selectedChildId={selectedChild.id} />
      case 'alerts':
        return <FeedScreen />
      default:
        return <ChildDashboardScreen dashboard={dashboard} child={selectedChild} user={user} />
    }
  }

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />
      {!showApp ? (
        <ScrollView contentContainerStyle={styles.login} keyboardShouldPersistTaps="handled">
          <Text style={styles.title}>EFSC-YA</Text>
          {API_BRIDGE_HOST ? (
            <Text style={styles.hint}>LAN: {API_BRIDGE_HOST}</Text>
          ) : null}
          {USES_EMULATOR_API ? (
            <Text style={styles.warn}>Dev: set mobile/.env (prism.test). Prod: sap-api.innovisiq.com</Text>
          ) : null}
          {!token ? (
            <>
              <Text style={styles.label}>Admission no. / CNIC / Email</Text>
              <TextInput
                style={styles.input}
                value={email}
                onChangeText={setEmail}
                autoCapitalize="none"
                autoCorrect={false}
                editable={!loading}
              />
              <Text style={styles.label}>Password</Text>
              <View style={styles.passwordField}>
                <TextInput
                  style={styles.passwordInput}
                  value={password}
                  onChangeText={setPassword}
                  secureTextEntry={!showPassword}
                  editable={!loading}
                  onSubmitEditing={login}
                />
                <Pressable
                  style={styles.passwordToggle}
                  onPress={() => setShowPassword((v) => !v)}
                  disabled={loading}
                  accessibilityLabel={showPassword ? 'Hide password' : 'Show password'}
                  accessibilityRole="button"
                  hitSlop={8}
                >
                  <EyeIcon hidden={showPassword} />
                </Pressable>
              </View>
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
              <Pressable style={styles.button} onPress={() => loadDashboard()}>
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
              <Text style={styles.headerTitle}>EFSC-YA</Text>
              <Text style={styles.headerSubtitle}>{headerSubtitle}</Text>
            </View>
            <View style={styles.headerRight}>
              <Text numberOfLines={1} style={styles.headerRightName}>{headerRightName}</Text>
              {isImpersonating ? (
                <>
                  <View style={styles.headerSeparator} />
                  <Pressable
                    onPress={exitImpersonation}
                    style={styles.exitViewBtn}
                    hitSlop={6}
                    accessibilityLabel="Back to Super Admin"
                  >
                    <Text style={styles.exitViewText} numberOfLines={1}>Back</Text>
                  </Pressable>
                </>
              ) : canViewAs ? (
                <>
                  <View style={styles.headerSeparator} />
                  <ViewAsPicker
                    options={viewAsOptions}
                    users={viewAsUsers}
                    usersLoading={viewAsUsersLoading}
                    value={viewAsRole}
                    onChangeRole={(name) => applyViewAs(name)}
                    onChangeUser={(u) => applyViewAsUser(u)}
                  />
                </>
              ) : null}
              <View style={styles.headerSeparator} />
              <Pressable onPress={logout} style={styles.headerLogoutBtn} hitSlop={8} accessibilityLabel="Logout">
                <LogoutIcon />
              </Pressable>
            </View>
          </View>
          {loading ? (
            <View style={styles.loadingOverlay}>
              <ActivityIndicator size="large" color="#2563eb" />
            </View>
          ) : null}
          <View style={styles.body}>{renderTab()}</View>
          <SideMenu
            visible={menuOpen}
            active={tab}
            items={navItems}
            onChange={handleNavChange}
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
  passwordField: {
    position: 'relative',
    marginBottom: 12,
  },
  passwordInput: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
    padding: 12,
    paddingRight: 48,
    backgroundColor: '#fff',
  },
  passwordToggle: {
    position: 'absolute',
    right: 4,
    top: 0,
    bottom: 0,
    width: 44,
    alignItems: 'center',
    justifyContent: 'center',
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
  staffMsg: { padding: 24 },
  staffMsgTitle: { fontSize: 20, fontWeight: '700', marginBottom: 8 },
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
  headerRight: {
    flexDirection: 'row',
    alignItems: 'center',
    maxWidth: '48%',
  },
  headerRightName: {
    color: '#334155',
    fontWeight: '600',
    fontSize: 13,
    maxWidth: 140,
  },
  headerSeparator: {
    width: 1,
    height: 18,
    backgroundColor: '#cbd5e1',
    marginHorizontal: 10,
  },
  exitViewBtn: {
    borderWidth: 1,
    borderColor: '#93c5fd',
    backgroundColor: '#eff6ff',
    borderRadius: 4,
    paddingHorizontal: 8,
    paddingVertical: 4,
  },
  exitViewText: {
    fontSize: 11,
    color: '#2563eb',
    fontWeight: '700',
  },
  headerLogoutBtn: {
    width: 28,
    height: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoutIconWrap: {
    width: 16,
    height: 16,
    position: 'relative',
  },
  logoutDoor: {
    position: 'absolute',
    right: 1,
    top: 2,
    width: 7,
    height: 12,
    borderWidth: 1.5,
    borderColor: '#2563eb',
    borderLeftWidth: 0,
    borderRadius: 1,
  },
  logoutArrowBody: {
    position: 'absolute',
    left: 1,
    top: 7,
    width: 8,
    height: 1.8,
    backgroundColor: '#2563eb',
  },
  logoutArrowHeadUp: {
    position: 'absolute',
    left: 6,
    top: 5,
    width: 5,
    height: 1.8,
    backgroundColor: '#2563eb',
    transform: [{ rotate: '35deg' }],
  },
  logoutArrowHeadDown: {
    position: 'absolute',
    left: 6,
    top: 9,
    width: 5,
    height: 1.8,
    backgroundColor: '#2563eb',
    transform: [{ rotate: '-35deg' }],
  },
  body: { flex: 1 },
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    top: 88,
    backgroundColor: 'rgba(248, 250, 252, 0.75)',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 10,
  },
})
