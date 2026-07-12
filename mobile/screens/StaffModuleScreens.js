import { useEffect, useState } from 'react'
import {
  View,
  Text,
  ScrollView,
  ActivityIndicator,
  Pressable,
  StyleSheet,
  RefreshControl,
} from 'react-native'
import { apiClient } from '../apiClient'
import { formatError, paginatedItems } from '../utils/format'
import { Card, EmptyNote, Section, ui } from '../components/ui'

function ScreenWrap({ children, onRefresh }) {
  const [refreshing, setRefreshing] = useState(false)
  async function refresh() {
    if (!onRefresh) return
    setRefreshing(true)
    try {
      await onRefresh()
    } finally {
      setRefreshing(false)
    }
  }
  return (
    <ScrollView
      contentContainerStyle={styles.scroll}
      refreshControl={onRefresh ? <RefreshControl refreshing={refreshing} onRefresh={refresh} /> : undefined}
    >
      {children}
    </ScrollView>
  )
}

export function ApprovalsScreen() {
  const [broadcasts, setBroadcasts] = useState([])
  const [dispatches, setDispatches] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const [busyId, setBusyId] = useState(null)

  async function load() {
    setErr('')
    try {
      const [b, d] = await Promise.all([
        apiClient.get('/efsc/broadcasts/pending'),
        apiClient.get('/efsc/notification-dispatches/pending'),
      ])
      setBroadcasts(Array.isArray(b.data) ? b.data : (b.data?.data ?? []))
      setDispatches(paginatedItems(d.data))
    } catch (e) {
      setErr(formatError(e))
      setBroadcasts([])
      setDispatches([])
    } finally {
      setLoading(false)
    }
  }

  async function act(kind, id, action) {
    setBusyId(`${kind}-${id}`)
    setErr('')
    try {
      if (kind === 'broadcast') {
        await apiClient.post(`/efsc/broadcasts/${id}/${action}`)
      } else {
        await apiClient.post(`/efsc/notification-dispatches/${id}/${action}`, {})
      }
      await load()
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setBusyId(null)
    }
  }

  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />

  return (
    <ScreenWrap onRefresh={load}>
      <Text style={styles.h1}>Approvals</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      <Section title="Broadcast announcements">
        {broadcasts.length === 0 ? <EmptyNote text="No broadcasts awaiting approval." /> : null}
        {broadcasts.map((b) => (
          <View key={`b-${b.id}`} style={styles.actionCard}>
            <Card
              title={b.title}
              meta={b.author?.name || b.audience_type || 'Broadcast'}
              body={b.body}
            />
            <View style={styles.rowActions}>
              <Pressable
                style={[styles.btn, styles.btnOk]}
                disabled={busyId === `broadcast-${b.id}`}
                onPress={() => act('broadcast', b.id, 'approve')}
              >
                <Text style={styles.btnText}>Approve</Text>
              </Pressable>
              <Pressable
                style={[styles.btn, styles.btnDanger]}
                disabled={busyId === `broadcast-${b.id}`}
                onPress={() => act('broadcast', b.id, 'reject')}
              >
                <Text style={styles.btnText}>Reject</Text>
              </Pressable>
            </View>
          </View>
        ))}
      </Section>
      <Section title="System dispatches">
        {dispatches.length === 0 ? <EmptyNote text="No system dispatches awaiting approval." /> : null}
        {dispatches.map((d) => (
          <View key={`d-${d.id}`} style={styles.actionCard}>
            <Card
              title={d.feature?.name || `Dispatch #${d.id}`}
              meta={d.status}
              body={d.school_class?.name || d.section?.name || undefined}
            />
            <View style={styles.rowActions}>
              <Pressable
                style={[styles.btn, styles.btnOk]}
                disabled={busyId === `dispatch-${d.id}`}
                onPress={() => act('dispatch', d.id, 'approve')}
              >
                <Text style={styles.btnText}>Approve</Text>
              </Pressable>
              <Pressable
                style={[styles.btn, styles.btnDanger]}
                disabled={busyId === `dispatch-${d.id}`}
                onPress={() => act('dispatch', d.id, 'reject')}
              >
                <Text style={styles.btnText}>Reject</Text>
              </Pressable>
            </View>
          </View>
        ))}
      </Section>
    </ScreenWrap>
  )
}

export function UsersScreen() {
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')

  async function load() {
    setErr('')
    try {
      const { data } = await apiClient.get('/users')
      setUsers(Array.isArray(data) ? data : (data?.data ?? []))
    } catch (e) {
      setErr(formatError(e))
      setUsers([])
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />

  return (
    <ScreenWrap onRefresh={load}>
      <Text style={styles.h1}>Users</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {users.length === 0 ? <EmptyNote text="No users found." /> : null}
      {users.map((u) => (
        <Card
          key={u.id}
          title={u.name}
          meta={u.email}
          sub={(u.roles || []).map((r) => (typeof r === 'string' ? r : r.name)).filter(Boolean).join(', ') || 'No roles'}
        />
      ))}
    </ScreenWrap>
  )
}

export function ConfigurationScreen() {
  const [classes, setClasses] = useState([])
  const [sections, setSections] = useState([])
  const [areas, setAreas] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')

  async function load() {
    setErr('')
    try {
      const [c, s, a] = await Promise.all([
        apiClient.get('/efsc/academic/classes'),
        apiClient.get('/efsc/academic/sections'),
        apiClient.get('/efsc/academic/areas'),
      ])
      setClasses(c.data?.data ?? c.data ?? [])
      setSections(s.data?.data ?? s.data ?? [])
      setAreas(a.data?.data ?? a.data ?? [])
    } catch (e) {
      setErr(formatError(e))
      setClasses([])
      setSections([])
      setAreas([])
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />

  return (
    <ScreenWrap onRefresh={load}>
      <Text style={styles.h1}>Configuration</Text>
      <Text style={styles.hint}>Academic structure overview. Full editing remains on web.</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      <Section title={`Areas (${areas.length})`}>
        {areas.length === 0 ? <EmptyNote text="No areas." /> : null}
        {areas.map((a) => (
          <Card key={a.id} title={a.name} meta={a.section_head?.name || a.sectionHead?.name || undefined} />
        ))}
      </Section>
      <Section title={`Classes (${classes.length})`}>
        {classes.length === 0 ? <EmptyNote text="No classes." /> : null}
        {classes.map((c) => (
          <Card key={c.id} title={c.name} meta={c.area?.name} />
        ))}
      </Section>
      <Section title={`Sections (${sections.length})`}>
        {sections.length === 0 ? <EmptyNote text="No sections." /> : null}
        {sections.map((s) => (
          <Card
            key={s.id}
            title={s.name}
            meta={s.school_class?.name || s.schoolClass?.name}
          />
        ))}
      </Section>
    </ScreenWrap>
  )
}

export function PermissionsScreen() {
  const [roles, setRoles] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')

  async function load() {
    setErr('')
    try {
      const { data } = await apiClient.get('/roles')
      setRoles(Array.isArray(data) ? data : (data?.data ?? []))
    } catch (e) {
      setErr(formatError(e))
      setRoles([])
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />

  return (
    <ScreenWrap onRefresh={load}>
      <Text style={styles.h1}>Permissions</Text>
      <Text style={styles.hint}>Role list. Assign permissions on web.</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {roles.length === 0 ? <EmptyNote text="No roles found." /> : null}
      {roles.map((r) => (
        <Card
          key={r.id}
          title={r.name}
          meta={r.guard_name || 'web'}
          sub={Array.isArray(r.permissions) ? `${r.permissions.length} permissions` : undefined}
        />
      ))}
    </ScreenWrap>
  )
}

const styles = StyleSheet.create({
  scroll: { padding: 16, paddingBottom: 32 },
  center: { marginTop: 24 },
  h1: { fontSize: 22, fontWeight: '700', marginBottom: 8, color: '#0f172a' },
  hint: { fontSize: 13, color: '#64748b', marginBottom: 12, lineHeight: 18 },
  actionCard: { marginBottom: 8 },
  rowActions: { flexDirection: 'row', gap: 8, marginBottom: 12, marginTop: -4 },
  btn: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnOk: { backgroundColor: '#15803d' },
  btnDanger: { backgroundColor: '#b91c1c' },
  btnText: { color: '#fff', fontWeight: '600' },
})
