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
  setViewAsRole,
  setViewAsUser,
  clearViewAs,
} from './apiClient'
import SideMenu, {
  BellIcon,
  HamburgerIcon,
  HomeIcon,
  navItemsForContext,
  staffNavItemsFromModules,
} from './components/SideMenu'
import EyeIcon from './components/EyeIcon'
import ViewAsPicker from './components/ViewAsPicker'
import FeatureDashboard from './components/FeatureDashboard'
import {
  ParentHomeScreen,
  HomeworkScreen,
  MarksScreen,
  AttendanceScreen,
  StaffAttendanceScreen,
  FeedScreen,
  OnlineClassScreen,
  LeaveScreen,
  TimetableScreen,
  FeesScreen,
} from './screens/ParentScreens'
import {
  ApprovalsScreen,
  UsersScreen,
  PermissionsScreen,
} from './screens/StaffModuleScreens'
import ConfigurationScreen from './screens/ConfigurationScreen'
import NavigationErrorBoundary from './components/NavigationErrorBoundary'
import { childName, formatError } from './utils/format'
import { ui } from './components/ui'
import { runStartupUpdateChecks } from './services/appUpdates'
import { loadSession, saveSession, clearSession } from './services/session'
import { learnerFeatures, staffFeaturesFor, isCatalogComingSoon, isCatalogLive, moduleStatus } from './features'

const DASHBOARD_INCLUDE =
  'homework,timetable,marks,broadcasts,fees,online_classes,leave,datesheet,notifications'

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
  const [booting, setBooting] = useState(true)
  const [err, setErr] = useState('')
  const [viewAsRole, setViewAsRoleState] = useState('')
  const [viewAsOptions, setViewAsOptions] = useState([])
  const [viewAsUsers, setViewAsUsers] = useState([])
  const [viewAsUsersLoading, setViewAsUsersLoading] = useState(false)
  const [impersonateUser, setImpersonateUser] = useState(null)
  const [modules, setModules] = useState([])

  useEffect(() => {
    runStartupUpdateChecks()
  }, [])

  useEffect(() => {
    setAuthToken(token)
  }, [token])

  // Restore persisted session after refresh / app reload.
  useEffect(() => {
    let cancelled = false
    ;(async () => {
      try {
        const session = await loadSession()
        if (cancelled || !session?.token) return

        setAuthToken(session.token)
        setToken(session.token)
        setUser(session.user ?? null)

        try {
          const { data: me } = await apiClient.get('/user')
          if (cancelled) return
          setUser(me)
          await saveSession(session.token, me)

          const roles = (me?.roles || []).map((r) => r.name)
          if (roles.includes('superadmin')) {
            try {
              await Promise.all([loadViewAsOptions(), loadViewAsUsers()])
            } catch {
              /* ignore */
            }
          }
          await refreshModulesSafe()
          if (roles.includes('parent') || roles.includes('student')) {
            await enterLearnerContext(roles)
          } else {
            setTab('dashboard')
          }
        } catch {
          if (cancelled) return
          await clearSession()
          setAuthToken('')
          setToken('')
          setUser(null)
          setDashboard(null)
          setModules([])
        }
      } finally {
        if (!cancelled) setBooting(false)
      }
    })()
    return () => {
      cancelled = true
    }
    // Intentionally once on mount — restore uses setters + stable apiClient.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  useEffect(() => {
    if (impersonateUser?.id) {
      setViewAsUser(impersonateUser.id)
    } else {
      setViewAsRole(viewAsRole)
    }
  }, [viewAsRole, impersonateUser])

  // Students never use the parent child-picker; bind self if dashboard has a profile.
  useEffect(() => {
    const roles = impersonateUser
      ? (impersonateUser.roles || []).map((r) => r.name)
      : viewAsRole
        ? [viewAsRole]
        : (user?.roles || []).map((r) => r.name)
    if (!roles.includes('student') || roles.includes('parent') || selectedChild) return
    const kids = dashboard?.children ?? []
    if (kids.length === 0) return
    setSelectedChild(kids[0])
    setTab((current) => (current === 'home' ? 'dashboard' : current))
  }, [impersonateUser, viewAsRole, user, selectedChild, dashboard])

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

  const loadModules = useCallback(async () => {
    const { data } = await apiClient.get('/efsc/modules', { params: { platform: 'mobile' } })
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
    setModules(list.filter((m) => m && moduleStatus(m) !== 'disabled'))
    return list
  }, [])

  const loadDashboard = useCallback(async (studentId = null) => {
    const params = { include: DASHBOARD_INCLUDE }
    if (studentId) {
      params.student_id = studentId
    }
    const { data } = await apiClient.get('/efsc/learner/dashboard', { params })
    setDashboard(data)
    return data
  }, [])

  async function refreshModulesSafe() {
    try {
      await loadModules()
    } catch {
      setModules([])
    }
  }

  async function enterLearnerContext(roles) {
    const data = await loadDashboard()
    const isParent = roles.includes('parent')
    const isStudent = roles.includes('student')
    // Parents pick a child first; students go straight to the feature dashboard.
    if (isStudent && !isParent) {
      const kids = data?.children ?? []
      if (kids.length > 0) {
        await loadDashboard(kids[0].id)
        setSelectedChild(kids[0])
      } else {
        setSelectedChild(null)
      }
      setTab('dashboard')
      return
    }
    setSelectedChild(null)
    setTab('home')
  }

  async function enterContext({ roles }) {
    setDashboard(null)
    setSelectedChild(null)
    setTab('home')
    setErr('')

    await refreshModulesSafe()

    if (roles.includes('parent') || roles.includes('student')) {
      setLoading(true)
      try {
        await enterLearnerContext(roles)
      } catch (e) {
        setErr(formatError(e))
      } finally {
        setLoading(false)
      }
    } else {
      setTab('dashboard')
    }
  }

  async function applyViewAs(roleName) {
    setImpersonateUser(null)
    setViewAsUser(null)
    setViewAsRoleState(roleName)
    setViewAsRole(roleName)

    const effectiveRoles = roleName ? [roleName] : actualRoleNames
    await enterContext({ roles: effectiveRoles })
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
      await enterContext({ roles })
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
    setTab('dashboard')
    setErr('')
    setMenuOpen(false)
    await refreshModulesSafe()
  }

  async function login() {
    Keyboard.dismiss()
    setErr('')
    setLoading(true)
    setDashboard(null)
    setUser(null)
    setSelectedChild(null)
    setModules([])
    setTab('home')
    try {
      const { data } = await apiClient.post('/login', {
        email: String(email || '').trim(),
        password: String(password || '').trim(),
      })
      if (!data?.access_token) throw new Error('No access token in response')
      setAuthToken(data.access_token)
      setToken(data.access_token)
      setUser(data.user ?? null)
      await saveSession(data.access_token, data.user ?? null)
      setViewAsRoleState('')
      setViewAsRole('')
      setViewAsUser(null)
      setImpersonateUser(null)
      setViewAsOptions([])
      setViewAsUsers([])
      const roles = (data.user?.roles || []).map((r) => r.name)
      const privileged = roles.includes('superadmin')
      if (privileged) {
        try {
          await Promise.all([loadViewAsOptions(), loadViewAsUsers()])
        } catch {
          /* ignore */
        }
      }
      await refreshModulesSafe()
      if (roles.includes('parent') || roles.includes('student')) {
        await enterLearnerContext(roles)
      } else {
        setTab('dashboard')
      }
    } catch (e) {
      setAuthToken('')
      setToken('')
      setUser(null)
      setDashboard(null)
      setSelectedChild(null)
      setModules([])
      await clearSession()
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
    await clearSession()
    setToken('')
    setUser(null)
    setDashboard(null)
    setSelectedChild(null)
    setModules([])
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
    selectFeatureSafe(id)
  }

  function openNotificationsFromHeader() {
    setMenuOpen(false)
    selectFeatureSafe('notifications')
  }

  const roleNames = impersonateUser
    ? (impersonateUser.roles || []).map((r) => r.name)
    : viewAsRole
      ? [viewAsRole]
      : actualRoleNames
  const effectivePermissions = impersonateUser
    ? permissionNamesFrom(impersonateUser)
    : viewAsRole
      ? (viewAsOptions.find((o) => o.name === viewAsRole)?.permissions || [])
      : permissionNamesFrom(user)
  const isParentRole = roleNames.includes('parent')
  const isStudentRole = roleNames.includes('student')
  const isLearnerRole = isParentRole || isStudentRole
  const isStaffRole = !isLearnerRole
  const moduleEnabledIds = new Set(
    modules.filter((m) => moduleStatus(m) !== 'disabled').map((m) => m.id),
  )
  const comingSoonIds = new Set(
    modules.filter((m) => moduleStatus(m) === 'coming_soon').map((m) => m.id),
  )
  const attendanceEnabled = isCatalogLive(modules, 'attendance')
  const showApp = token && !err && (isLearnerRole ? !!dashboard : true)
  const hasSelectedChild = Boolean(selectedChild)
  const navItems = isStaffRole
    ? staffNavItemsFromModules(modules)
    : navItemsForContext(hasSelectedChild, false, isStudentRole, moduleEnabledIds, comingSoonIds)
  const activeLabel = navItems.find((item) => item.id === tab)?.label ?? 'Home'
  const children = dashboard?.children ?? []
  const learnerChild = selectedChild || (isStudentRole ? children[0] ?? null : null)
  const headerSubtitle = activeLabel
  const selectedChildLabel = learnerChild ? childName(learnerChild) : ''
  const headerRightName = selectedChildLabel
    || (impersonateUser?.name)
    || user?.name
    || ''
  const displayUserName = impersonateUser?.name || user?.name || ''
  const staffFeatureList = staffFeaturesFor(modules)
  const learnerFeatureList = learnerFeatures(modules)
  const unread = dashboard?.unread_notifications ?? 0

  function goHome() {
    if (isStaffRole || isStudentRole || hasSelectedChild) {
      setTab('dashboard')
      return
    }
    setTab('home')
  }

  function selectFeatureSafe(id) {
    try {
      if (!id || isCatalogComingSoon(modules, id)) {
        return
      }
      if (id !== 'dashboard' && id !== 'home' && !isCatalogLive(modules, id) && isStaffRole) {
        setErr('This feature is not available.')
        setTab('dashboard')
        return
      }
      setErr('')
      setTab(id)
    } catch (e) {
      setErr(formatError(e) || 'Something went wrong — please try again')
      setTab(isStaffRole || isStudentRole || hasSelectedChild ? 'dashboard' : 'home')
    }
  }

  function renderFeatureDashboard({ child = null, features, subtitle }) {
    return (
      <FeatureDashboard
        features={features}
        child={child}
        userName={displayUserName}
        subtitle={subtitle}
        onSelectFeature={selectFeatureSafe}
      />
    )
  }

  function renderTab() {
    if (isStaffRole) {
      if (tab === 'dashboard' || tab === 'home') {
        return renderFeatureDashboard({
          features: staffFeatureList,
          subtitle: staffFeatureList.length
            ? 'Choose a feature to continue'
            : 'No mobile features for this role yet',
        })
      }
      if (isCatalogComingSoon(modules, tab)) {
        return renderFeatureDashboard({
          features: staffFeatureList,
          subtitle: staffFeatureList.length
            ? 'Choose a feature to continue'
            : 'No mobile features for this role yet',
        })
      }
      if (!isCatalogLive(modules, tab) && tab !== 'dashboard' && tab !== 'home') {
        return renderFeatureDashboard({
          features: staffFeatureList,
          subtitle: staffFeatureList.length
            ? 'Choose a feature to continue'
            : 'No mobile features for this role yet',
        })
      }
      switch (tab) {
        case 'attendance':
          return attendanceEnabled ? <StaffAttendanceScreen permissions={effectivePermissions} /> : null
        case 'approvals':
          return <ApprovalsScreen />
        case 'marks':
          return <MarksScreen permissions={effectivePermissions} />
        case 'homework':
          return <HomeworkScreen permissions={effectivePermissions} isLearner={false} />
        case 'online':
          return <OnlineClassScreen permissions={effectivePermissions} />
        case 'timetable':
          return <TimetableScreen />
        case 'fees':
          return <FeesScreen />
        case 'notifications':
          return <FeedScreen />
        case 'leave':
          return <LeaveScreen permissions={effectivePermissions} />
        case 'users':
          return <UsersScreen />
        case 'configuration':
          return <ConfigurationScreen permissions={effectivePermissions} roles={roleNames} />
        case 'permissions':
          return <PermissionsScreen />
        default:
          return (
            <View style={{ flex: 1, padding: 24, justifyContent: 'center' }}>
              <Text style={{ fontSize: 18, fontWeight: '700', marginBottom: 8 }}>Something went wrong</Text>
              <Text style={{ color: '#64748b', marginBottom: 16 }}>
                This screen is not available. Please try again from the dashboard.
              </Text>
              <Pressable style={styles.button} onPress={() => setTab('dashboard')}>
                <Text style={styles.buttonText}>Back to dashboard</Text>
              </Pressable>
            </View>
          )
      }
    }

    // Parents must pick a child; students skip this and land on the dashboard.
    if (isParentRole && !hasSelectedChild) {
      return (
        <ParentHomeScreen dashboard={dashboard} user={user} onSelectChild={selectChild} />
      )
    }

    const childId = learnerChild?.id
    const featureOpen = tab === 'dashboard'
      || tab === 'home'
      || (moduleEnabledIds.has(tab) && !comingSoonIds.has(tab))

    if (!featureOpen) {
      return renderFeatureDashboard({
        child: learnerChild,
        features: learnerFeatureList,
        subtitle: unread > 0
          ? `${unread} unread notification${unread === 1 ? '' : 's'}`
          : 'Choose a feature to continue',
      })
    }

    switch (tab) {
      case 'dashboard':
      case 'home':
        return renderFeatureDashboard({
          child: learnerChild,
          features: learnerFeatureList,
          subtitle: unread > 0
            ? `${unread} unread notification${unread === 1 ? '' : 's'}`
            : 'Choose a feature to continue',
        })
      case 'homework':
        return <HomeworkScreen permissions={effectivePermissions} isLearner />
      case 'marks':
        return <MarksScreen />
      case 'attendance':
        return <AttendanceScreen children={children} selectedChildId={childId} />
      case 'timetable':
        return <TimetableScreen />
      case 'fees':
        return <FeesScreen />
      case 'notifications':
        return <FeedScreen />
      case 'online':
        return <OnlineClassScreen />
      case 'leave':
        return <LeaveScreen children={children} selectedChildId={childId} />
      case 'alerts':
        return <FeedScreen />
      default:
        return renderFeatureDashboard({
          child: learnerChild,
          features: learnerFeatureList,
          subtitle: 'Choose a feature to continue',
        })
    }
  }

  if (booting) {
    return (
      <View style={[styles.root, styles.boot]}>
        <StatusBar style="dark" />
        <ActivityIndicator size="large" color="#0f766e" />
      </View>
    )
  }

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />
      {!showApp ? (
        <ScrollView contentContainerStyle={styles.login} keyboardShouldPersistTaps="handled">
          <Text style={styles.title}>EFSC-YA</Text>
          <Text style={styles.hint}>API: {API_DISPLAY}</Text>
          {API_BRIDGE_HOST ? (
            <Text style={styles.hint}>LAN: {API_BRIDGE_HOST}</Text>
          ) : null}
          {USES_EMULATOR_API ? (
            <Text style={styles.warn}>
              Emulator fallback (10.0.2.2). On a real phone set EXPO_PUBLIC_API_LAN_IP and rebuild the APK.
            </Text>
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
              onPress={goHome}
              style={styles.menuBtn}
              hitSlop={8}
              accessibilityLabel="Home"
            >
              <HomeIcon />
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
              <Pressable
                onPress={openNotificationsFromHeader}
                style={styles.headerIconBtn}
                hitSlop={8}
                accessibilityLabel={`Open notifications${unread > 0 ? ` (${unread} unread)` : ''}`}
              >
                <BellIcon />
                {unread > 0 ? (
                  <View style={styles.notificationBadge}>
                    <Text style={styles.notificationBadgeText}>
                      {unread > 99 ? '99+' : String(unread)}
                    </Text>
                  </View>
                ) : null}
              </Pressable>
              <View style={styles.headerSeparator} />
              <Pressable
                onPress={() => setMenuOpen(true)}
                style={styles.headerMenuBtn}
                hitSlop={8}
                accessibilityLabel="Open menu"
              >
                <HamburgerIcon />
              </Pressable>
            </View>
          </View>
          {loading ? (
            <View style={styles.loadingOverlay}>
              <ActivityIndicator size="large" color="#2563eb" />
            </View>
          ) : null}
          <View style={styles.body}>
            <NavigationErrorBoundary
              key={tab}
              onReset={() => setTab(isStaffRole || isStudentRole || hasSelectedChild ? 'dashboard' : 'home')}
            >
              {renderTab()}
            </NavigationErrorBoundary>
          </View>
          <SideMenu
            visible={menuOpen}
            active={tab}
            items={navItems}
            onChange={handleNavChange}
            onClose={() => setMenuOpen(false)}
            onLogout={logout}
          />
        </>
      )}
    </View>
  )
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#f8fafc' },
  boot: { alignItems: 'center', justifyContent: 'center' },
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
    maxWidth: '56%',
    flexShrink: 1,
    minWidth: 0,
  },
  headerRightName: {
    color: '#334155',
    fontWeight: '600',
    fontSize: 13,
    maxWidth: 96,
    flexShrink: 1,
  },
  headerSeparator: {
    width: 1,
    height: 18,
    backgroundColor: '#cbd5e1',
    marginHorizontal: 6,
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
  headerMenuBtn: {
    width: 28,
    height: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerIconBtn: {
    width: 28,
    height: 28,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  notificationBadge: {
    position: 'absolute',
    top: -3,
    right: -8,
    minWidth: 14,
    height: 14,
    paddingHorizontal: 3,
    borderRadius: 999,
    backgroundColor: '#dc2626',
    alignItems: 'center',
    justifyContent: 'center',
  },
  notificationBadgeText: {
    color: '#fff',
    fontSize: 9,
    fontWeight: '700',
    lineHeight: 11,
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
