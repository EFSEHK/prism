import { useState, useEffect } from 'react'
import {
  View,
  Text,
  ScrollView,
  ActivityIndicator,
  RefreshControl,
  TextInput,
  Pressable,
  Linking,
  StyleSheet,
} from 'react-native'
import { apiClient } from '../apiClient'
import { formatDate, formatTime, childName, formatError, paginatedItems } from '../utils/format'
import { Section, Card, EmptyNote, ui } from '../components/ui'

function ScreenWrap({ children, refreshing, onRefresh }) {
  return (
    <ScrollView
      contentContainerStyle={styles.scroll}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {children}
    </ScrollView>
  )
}

export function HomeScreen({ dashboard, user }) {
  const children = dashboard?.children ?? []
  const homework = dashboard?.homework ?? []
  const timetable = dashboard?.timetable_today ?? []
  const unread = dashboard?.unread_notifications ?? 0

  return (
    <ScreenWrap>
      <View style={ui.hero}>
        <Text style={ui.heroGreeting}>Welcome back</Text>
        <Text style={ui.heroName}>{user?.name || 'Parent'}</Text>
        <Text style={ui.heroMeta}>
          {unread > 0 ? `${unread} unread notification${unread === 1 ? '' : 's'}` : 'All caught up'}
        </Text>
      </View>
      <Section title="My children" badge={children.length || null}>
        {children.length === 0 ? (
          <EmptyNote text="No linked students." />
        ) : (
          children.map((s) => (
            <Card
              key={s.id}
              title={childName(s)}
              meta={`${s.school_class?.name || ''} · Section ${s.section?.name || ''}`}
              sub={s.admission_no ? `Admission ${s.admission_no}` : null}
            />
          ))
        )}
      </Section>
      <Section title="Today" badge={timetable.length || null}>
        {timetable.length === 0 ? (
          <EmptyNote text="No classes today." />
        ) : (
          timetable.map((slot) => (
            <Card
              key={slot.id}
              title={slot.subject?.name || 'Period'}
              meta={`${formatTime(slot.start_time)}${slot.end_time ? ` – ${formatTime(slot.end_time)}` : ''}`}
            />
          ))
        )}
      </Section>
      <Section title="Recent homework" badge={homework.length || null}>
        {homework.length === 0 ? (
          <EmptyNote text="No homework." />
        ) : (
          homework.slice(0, 3).map((h) => (
            <Card key={h.id} title={h.title} meta={h.subject?.name} sub={h.due_date ? `Due ${formatDate(h.due_date)}` : null} />
          ))
        )}
      </Section>
    </ScreenWrap>
  )
}

export function HomeworkScreen() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    setErr('')
    try {
      const { data } = await apiClient.get('/prism/homework', { params: { per_page: 30 } })
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Homework diary</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {items.length === 0 ? <EmptyNote text="No homework posts." /> : null}
      {items.map((h) => (
        <Card
          key={h.id}
          title={h.title}
          meta={`${h.subject?.name || ''} · ${h.school_class?.name || ''}`}
          body={h.body}
          sub={h.due_date ? `Due ${formatDate(h.due_date)}` : null}
        />
      ))}
    </ScreenWrap>
  )
}

export function MarksScreen() {
  const [items, setItems] = useState([])
  const [detail, setDetail] = useState(null)
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    setErr('')
    try {
      const { data } = await apiClient.get('/prism/mark-sheets', { params: { per_page: 30 } })
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  const open = async (id) => {
    try {
      const { data } = await apiClient.get(`/prism/mark-sheets/${id}`)
      setDetail(data)
    } catch (e) {
      setErr(formatError(e))
    }
  }
  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />
  if (detail) {
    return (
      <ScreenWrap>
        <Pressable onPress={() => setDetail(null)}>
          <Text style={styles.back}>← Back</Text>
        </Pressable>
        <Text style={styles.h1}>
          {detail.subject?.name} — {detail.assessment?.name}
        </Text>
        {(detail.entries || []).map((e) => (
          <Card
            key={e.id}
            title={childName(e.student)}
            meta={`${e.marks_obtained ?? '—'} / ${e.max_marks ?? '—'}${e.grade ? ` · ${e.grade}` : ''}`}
          />
        ))}
      </ScreenWrap>
    )
  }
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Marks</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {items.map((m) => (
        <Card
          key={m.id}
          title={`${m.subject?.name} — ${m.assessment?.name || m.assessment?.type}`}
          meta={`${m.school_class?.name} · Section ${m.section?.name}`}
          onPress={() => open(m.id)}
          sub="Tap for details"
        />
      ))}
    </ScreenWrap>
  )
}

export function AttendanceScreen({ children }) {
  const [studentId, setStudentId] = useState(children[0]?.id)
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7))
  const [days, setDays] = useState([])
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')
  const load = async () => {
    if (!studentId) return
    setLoading(true)
    setErr('')
    try {
      const { data } = await apiClient.get('/prism/attendance/reports/monthly', {
        params: { student_id: studentId, month },
      })
      setDays(data.days || [])
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => {
    if (studentId) load()
  }, [studentId, month])
  return (
    <ScreenWrap>
      <Text style={styles.h1}>Attendance</Text>
      {children.map((c) => (
        <Pressable
          key={c.id}
          onPress={() => setStudentId(c.id)}
          style={[styles.chip, studentId === c.id && styles.chipOn]}
        >
          <Text style={studentId === c.id ? styles.chipTextOn : styles.chipText}>{childName(c)}</Text>
        </Pressable>
      ))}
      <Text style={styles.label}>Month (YYYY-MM)</Text>
      <TextInput style={styles.input} value={month} onChangeText={setMonth} onSubmitEditing={load} />
      <Pressable style={styles.btn} onPress={load}>
        <Text style={styles.btnText}>Load report</Text>
      </Pressable>
      {loading ? <ActivityIndicator /> : null}
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {days.map((d, i) => (
        <Card key={i} title={formatDate(d.date)} meta={d.status} />
      ))}
    </ScreenWrap>
  )
}

export function TimetableScreen() {
  const [slots, setSlots] = useState([])
  const [datesheet, setDatesheet] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    setErr('')
    try {
      const [s, d] = await Promise.all([
        apiClient.get('/prism/timetable/slots', { params: { per_page: 50 } }),
        apiClient.get('/prism/timetable/datesheet', { params: { per_page: 30 } }),
      ])
      setSlots(paginatedItems(s.data))
      setDatesheet(paginatedItems(d.data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Timetable</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      <Section title="Weekly slots">
        {slots.map((slot) => (
          <Card
            key={slot.id}
            title={slot.subject?.name || 'Period'}
            meta={`${days[slot.day_of_week] || slot.day_of_week} · ${formatTime(slot.start_time)}–${formatTime(slot.end_time)}`}
            sub={slot.room ? `Room ${slot.room}` : null}
          />
        ))}
      </Section>
      <Section title="Exam datesheet">
        {datesheet.map((e) => (
          <Card key={e.id} title={e.title} meta={formatDate(e.exam_date)} body={e.notes} />
        ))}
      </Section>
    </ScreenWrap>
  )
}

export function FeedScreen() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    try {
      const { data } = await apiClient.get('/prism/feed')
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Events & announcements</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {items.map((f) => (
        <Card
          key={f.id}
          title={f.title}
          meta={`${f.type} · ${f.scope}${f.published_at ? ` · ${formatDate(f.published_at)}` : ''}`}
          body={f.body}
        />
      ))}
    </ScreenWrap>
  )
}

export function FeesScreen() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    try {
      const { data } = await apiClient.get('/prism/fee-vouchers')
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Fee vouchers</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {items.map((v) => (
        <Card
          key={v.id}
          title={v.title}
          meta={childName(v.student)}
          sub={`Status: ${v.submission_status}`}
        />
      ))}
    </ScreenWrap>
  )
}

export function OnlineClassScreen() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    try {
      const { data } = await apiClient.get('/prism/online-classes')
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Online classes</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {items.map((l) => (
        <Card
          key={l.id}
          title={l.label}
          meta={l.subject?.name}
          sub={l.url}
          onPress={() => Linking.openURL(l.url)}
        />
      ))}
    </ScreenWrap>
  )
}

export function LeaveScreen({ children }) {
  const [items, setItems] = useState([])
  const [studentId, setStudentId] = useState(children[0]?.id)
  const [startDate, setStartDate] = useState('')
  const [endDate, setEndDate] = useState('')
  const [reason, setReason] = useState('')
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const [ok, setOk] = useState('')
  const load = async () => {
    try {
      const { data } = await apiClient.get('/prism/leave-requests')
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  const submit = async () => {
    setErr('')
    setOk('')
    try {
      await apiClient.post('/prism/leave-requests', {
        student_id: studentId,
        start_date: startDate,
        end_date: endDate,
        reason,
      })
      setOk('Leave request submitted.')
      setStartDate('')
      setEndDate('')
      setReason('')
      await load()
    } catch (e) {
      setErr(formatError(e))
    }
  }
  useEffect(() => { load() }, [])
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Leave requests</Text>
      <Text style={styles.label}>New request</Text>
      {children.map((c) => (
        <Pressable
          key={c.id}
          onPress={() => setStudentId(c.id)}
          style={[styles.chip, studentId === c.id && styles.chipOn]}
        >
          <Text style={studentId === c.id ? styles.chipTextOn : styles.chipText}>{childName(c)}</Text>
        </Pressable>
      ))}
      <TextInput style={styles.input} placeholder="Start date YYYY-MM-DD" value={startDate} onChangeText={setStartDate} />
      <TextInput style={styles.input} placeholder="End date YYYY-MM-DD" value={endDate} onChangeText={setEndDate} />
      <TextInput style={styles.input} placeholder="Reason" value={reason} onChangeText={setReason} />
      <Pressable style={styles.btn} onPress={submit}>
        <Text style={styles.btnText}>Submit</Text>
      </Pressable>
      {ok ? <Text style={styles.ok}>{ok}</Text> : null}
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {loading ? <ActivityIndicator /> : null}
      <Section title="Your requests">
        {items.map((l) => (
          <Card
            key={l.id}
            title={childName(l.student)}
            meta={`${formatDate(l.start_date)} – ${formatDate(l.end_date)}`}
            sub={`Status: ${l.status}`}
          />
        ))}
      </Section>
    </ScreenWrap>
  )
}

export function AlertsScreen() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    try {
      const { data } = await apiClient.get('/prism/in-app-notifications')
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  const markRead = async (id) => {
    await apiClient.post(`/prism/in-app-notifications/${id}/read`)
    await load()
  }
  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Notifications</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {items.map((n) => (
        <Card
          key={n.id}
          title={n.title}
          meta={formatDate(n.created_at)}
          body={n.body}
          sub={n.read_at ? 'Read' : 'Unread — tap to mark read'}
          onPress={!n.read_at ? () => markRead(n.id) : undefined}
        />
      ))}
    </ScreenWrap>
  )
}

const styles = StyleSheet.create({
  scroll: { padding: 16, paddingBottom: 24 },
  center: { marginTop: 40 },
  h1: { fontSize: 22, fontWeight: '700', color: '#0f172a', marginBottom: 12 },
  back: { color: '#2563eb', marginBottom: 12, fontSize: 16 },
  label: { fontSize: 13, color: '#64748b', marginBottom: 4 },
  input: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
    padding: 10,
    marginBottom: 8,
    backgroundColor: '#fff',
  },
  btn: {
    backgroundColor: '#2563eb',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 12,
  },
  btnText: { color: '#fff', fontWeight: '600' },
  ok: { color: '#15803d', marginBottom: 8 },
  chip: {
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
    backgroundColor: '#e2e8f0',
    marginRight: 8,
    marginBottom: 8,
  },
  chipOn: { backgroundColor: '#2563eb' },
  chipText: { color: '#475569', fontWeight: '600' },
  chipTextOn: { color: '#fff', fontWeight: '600' },
})
