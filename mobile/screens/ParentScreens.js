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
import ChildAvatar from '../components/ChildAvatar'

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

/** Parent home: pick a child, then institute-wide announcements. */
export function ParentHomeScreen({ dashboard, user, onSelectChild }) {
  const children = dashboard?.children ?? []
  const announcements = dashboard?.school_announcements ?? []
  const unread = dashboard?.unread_notifications ?? 0

  return (
    <ScreenWrap>
      <View style={ui.hero}>
        <Text style={ui.heroGreeting}>Welcome back</Text>
        <Text style={ui.heroName}>{user?.name || 'Parent'}</Text>
        <Text style={ui.heroMeta}>
          {unread > 0 ? `${unread} unread notification${unread === 1 ? '' : 's'}` : 'Select a child to view their dashboard'}
        </Text>
      </View>

      <Section title="Select a child" badge={children.length || null}>
        {children.length === 0 ? (
          <EmptyNote text="No linked students." />
        ) : (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.childRow}>
            {children.map((s) => (
              <ChildAvatar key={s.id} student={s} onPress={() => onSelectChild(s)} />
            ))}
          </ScrollView>
        )}
      </Section>

      <Section title="General announcements" badge={announcements.length || null}>
        <Text style={styles.sectionHint}>Institute-wide updates for all families.</Text>
        {announcements.length === 0 ? (
          <EmptyNote text="No institute announcements." />
        ) : (
          announcements.map((a) => (
            <Card
              key={a.id}
              title={a.title}
              meta={a.published_at ? formatDate(a.published_at) : null}
              body={a.body}
            />
          ))
        )}
      </Section>
    </ScreenWrap>
  )
}

/** Per-child dashboard after selection. */
export function ChildDashboardScreen({ dashboard, child, user }) {
  const homework = dashboard?.homework ?? []
  const timetable = dashboard?.timetable_today ?? []
  const unread = dashboard?.unread_notifications ?? 0
  const classLabel = child?.school_class?.name || ''
  const sectionLabel = child?.section?.name || ''

  return (
    <ScreenWrap>
      <View style={styles.childHeader}>
        <ChildAvatar student={child} selected />
        <View style={styles.childHeaderText}>
          <Text style={styles.childHeaderName}>{childName(child)}</Text>
          <Text style={styles.childHeaderMeta}>
            {classLabel}
            {sectionLabel ? ` · Section ${sectionLabel}` : ''}
          </Text>
          {child?.admission_no ? (
            <Text style={styles.childHeaderSub}>Admission {child.admission_no}</Text>
          ) : null}
        </View>
      </View>

      <View style={ui.heroCompact}>
        <Text style={ui.heroGreeting}>Dashboard</Text>
        <Text style={ui.heroName}>{user?.name || 'Parent'}</Text>
        <Text style={ui.heroMeta}>
          {unread > 0 ? `${unread} unread notification${unread === 1 ? '' : 's'}` : 'All caught up'}
        </Text>
      </View>

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

/** @deprecated Use ParentHomeScreen or ChildDashboardScreen */
export function HomeScreen(props) {
  if (props.child) {
    return <ChildDashboardScreen {...props} />
  }
  return <ParentHomeScreen {...props} onSelectChild={props.onSelectChild} />
}

export function HomeworkScreen({ permissions = [], isLearner = false }) {
  const perms = (permissions || []).map((p) => (typeof p === 'string' ? p : p?.name)).filter(Boolean)
  const canPost = !isLearner && perms.includes('post_homework')
  const canApprove = !isLearner && perms.includes('approve_homework')

  const [items, setItems] = useState([])
  const [pending, setPending] = useState([])
  const [studyGroups, setStudyGroups] = useState([])
  const [subjects, setSubjects] = useState([])
  const [studyGroupId, setStudyGroupId] = useState('')
  const [title, setTitle] = useState('')
  const [body, setBody] = useState('')
  const [dueDate, setDueDate] = useState('')
  const [subjectId, setSubjectId] = useState('')
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [busyId, setBusyId] = useState(null)
  const [err, setErr] = useState('')
  const [ok, setOk] = useState('')

  const loadMeta = async () => {
    if (isLearner) return
    const [sg, sub] = await Promise.all([
      apiClient.get('/efsc/academic/study-groups'),
      apiClient.get('/efsc/academic/subjects'),
    ])
    const groups = sg.data?.data ?? sg.data ?? []
    const subjectList = sub.data?.data ?? sub.data ?? []
    setStudyGroups(Array.isArray(groups) ? groups : [])
    setSubjects(Array.isArray(subjectList) ? subjectList : [])
    if (!studyGroupId && groups.length) {
      setStudyGroupId(String(groups[0].id))
    }
  }

  const load = async () => {
    setErr('')
    try {
      const params = { per_page: 30 }
      if (!isLearner && studyGroupId) params.study_group_id = studyGroupId
      const { data } = await apiClient.get('/efsc/homework', { params })
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
      setItems([])
    } finally {
      setLoading(false)
    }
  }

  const loadPending = async () => {
    if (!canApprove) {
      setPending([])
      return
    }
    try {
      const params = { status: 'pending_approval', per_page: 50 }
      if (studyGroupId) params.study_group_id = studyGroupId
      const { data } = await apiClient.get('/efsc/homework', { params })
      setPending(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
      setPending([])
    }
  }

  const create = async () => {
    setErr('')
    setOk('')
    if (!studyGroupId || !title.trim()) {
      setErr('Study group and title are required.')
      return
    }
    setSaving(true)
    try {
      await apiClient.post('/efsc/homework', {
        study_group_id: Number(studyGroupId),
        subject_id: subjectId ? Number(subjectId) : null,
        title: title.trim(),
        body,
        due_date: dueDate || null,
      })
      setOk('Posted — awaiting section head approval.')
      setTitle('')
      setBody('')
      setDueDate('')
      setSubjectId('')
      await load()
      await loadPending()
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setSaving(false)
    }
  }

  const act = async (id, action) => {
    setBusyId(id)
    setErr('')
    setOk('')
    try {
      await apiClient.post(`/efsc/homework/${id}/${action}`)
      setOk(action === 'approve' ? 'Homework approved.' : 'Homework rejected.')
      await loadPending()
      await load()
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setBusyId(null)
    }
  }

  useEffect(() => {
    let cancelled = false
    ;(async () => {
      try {
        await loadMeta()
      } catch (e) {
        if (!cancelled) setErr(formatError(e))
      }
    })()
    return () => { cancelled = true }
  }, [])

  useEffect(() => {
    load()
    loadPending()
  }, [studyGroupId, canApprove, isLearner])

  if (loading && items.length === 0 && pending.length === 0) {
    return <ActivityIndicator style={styles.center} />
  }

  return (
    <ScreenWrap refreshing={false} onRefresh={async () => { await load(); await loadPending() }}>
      <Text style={styles.h1}>Homework diary</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {ok ? <Text style={styles.ok}>{ok}</Text> : null}

      {!isLearner && studyGroups.length > 0 ? (
        <View style={styles.pickerWrap}>
          <Text style={styles.label}>Study group</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
            {studyGroups.map((g) => (
              <Pressable
                key={g.id}
                onPress={() => setStudyGroupId(String(g.id))}
                style={[styles.chip, studyGroupId === String(g.id) && styles.chipActive]}
              >
                <Text style={[styles.chipText, studyGroupId === String(g.id) && styles.chipTextActive]}>
                  {g.name}
                </Text>
              </Pressable>
            ))}
          </ScrollView>
        </View>
      ) : null}

      {canApprove ? (
        <Section title="Pending approval" badge={pending.length || null}>
          {pending.length === 0 ? <EmptyNote text="No homework awaiting approval." /> : null}
          {pending.map((h) => (
            <View key={`p-${h.id}`} style={styles.actionCard}>
              <Card
                title={h.title}
                meta={`${h.subject?.name || 'No subject'} · ${h.created_by?.name || 'Staff'}`.trim()}
                body={h.body}
                sub={h.due_date ? `Due ${formatDate(h.due_date)}` : null}
              />
              <View style={styles.rowActions}>
                <Pressable
                  style={[styles.btn, styles.btnOk, busyId === h.id && styles.btnDisabled]}
                  disabled={busyId === h.id}
                  onPress={() => act(h.id, 'approve')}
                >
                  <Text style={styles.btnText}>Approve</Text>
                </Pressable>
                <Pressable
                  style={[styles.btn, styles.btnDanger, busyId === h.id && styles.btnDisabled]}
                  disabled={busyId === h.id}
                  onPress={() => act(h.id, 'reject')}
                >
                  <Text style={styles.btnText}>Reject</Text>
                </Pressable>
              </View>
            </View>
          ))}
        </Section>
      ) : null}

      {canPost ? (
        <Section title="New post">
          <TextInput style={styles.input} placeholder="Title" value={title} onChangeText={setTitle} />
          <TextInput
            style={[styles.input, styles.textarea]}
            placeholder="Body"
            value={body}
            onChangeText={setBody}
            multiline
          />
          <TextInput
            style={styles.input}
            placeholder="Due date (YYYY-MM-DD)"
            value={dueDate}
            onChangeText={setDueDate}
          />
          {subjects.length > 0 ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
              <Pressable
                onPress={() => setSubjectId('')}
                style={[styles.chip, !subjectId && styles.chipActive]}
              >
                <Text style={[styles.chipText, !subjectId && styles.chipTextActive]}>—</Text>
              </Pressable>
              {subjects.map((s) => (
                <Pressable
                  key={s.id}
                  onPress={() => setSubjectId(String(s.id))}
                  style={[styles.chip, subjectId === String(s.id) && styles.chipActive]}
                >
                  <Text style={[styles.chipText, subjectId === String(s.id) && styles.chipTextActive]}>
                    {s.name}
                  </Text>
                </Pressable>
              ))}
            </ScrollView>
          ) : null}
          <Pressable style={[styles.btn, saving && styles.btnDisabled]} onPress={create} disabled={saving}>
            <Text style={styles.btnText}>{saving ? 'Posting…' : 'Post homework'}</Text>
          </Pressable>
        </Section>
      ) : null}

      <Section title={isLearner ? 'Homework' : 'Recent'}>
        {items.length === 0 ? <EmptyNote text="No homework posts." /> : null}
        {items.map((h) => (
          <Card
            key={h.id}
            title={h.title}
            meta={[
              h.subject?.name,
              isLearner ? h.study_group?.name : h.status,
              h.due_date ? `Due ${formatDate(h.due_date)}` : null,
            ].filter(Boolean).join(' · ')}
            body={h.body}
          />
        ))}
      </Section>
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
      const { data } = await apiClient.get('/efsc/mark-sheets', { params: { per_page: 30 } })
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  const open = async (id) => {
    try {
      const { data } = await apiClient.get(`/efsc/mark-sheets/${id}`)
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

export { default as StaffAttendanceScreen } from './StaffAttendanceScreen'

export function AttendanceScreen({ children, selectedChildId }) {
  const [studentId, setStudentId] = useState(selectedChildId ?? children[0]?.id)
  useEffect(() => {
    if (selectedChildId) setStudentId(selectedChildId)
  }, [selectedChildId])
  const [month, setMonth] = useState(new Date().toISOString().slice(0, 7))
  const [days, setDays] = useState([])
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')
  const load = async () => {
    if (!studentId) return
    setLoading(true)
    setErr('')
    try {
      const { data } = await apiClient.get('/efsc/attendance/reports/monthly', {
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
      {studentId ? (
        <Text style={styles.childLockText}>
          Showing report for {childName(children.find((c) => c.id === studentId))}
        </Text>
      ) : null}
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
        apiClient.get('/efsc/timetable/slots', { params: { per_page: 50 } }),
        apiClient.get('/efsc/timetable/datesheet', { params: { per_page: 30 } }),
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
  const [alerts, setAlerts] = useState([])
  const [broadcasts, setBroadcasts] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    try {
      const [alertsRes, broadcastsRes] = await Promise.all([
        apiClient.get('/efsc/in-app-notifications'),
        apiClient.get('/efsc/broadcasts'),
      ])
      setAlerts(paginatedItems(alertsRes.data))
      setBroadcasts(paginatedItems(broadcastsRes.data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  const markRead = async (id) => {
    await apiClient.post(`/efsc/in-app-notifications/${id}/read`)
    await load()
  }
  useEffect(() => { load() }, [])
  if (loading) return <ActivityIndicator style={styles.center} />
  return (
    <ScreenWrap refreshing={false} onRefresh={load}>
      <Text style={styles.h1}>Notifications</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      <Section title="Alerts">
        {alerts.length === 0 ? <EmptyNote text="No alerts yet." /> : null}
        {alerts.map((n) => (
          <Card
            key={n.id}
            title={n.title}
            meta={formatDate(n.created_at)}
            body={n.body}
            sub={n.read_at ? 'Read' : 'Unread — tap to mark read'}
            onPress={!n.read_at ? () => markRead(n.id) : undefined}
          />
        ))}
      </Section>
      <Section title="Announcements">
        {broadcasts.length === 0 ? <EmptyNote text="No announcements yet." /> : null}
        {broadcasts.map((f) => (
          <Card
            key={f.id}
            title={f.title}
            meta={f.published_at ? formatDate(f.published_at) : ''}
            body={f.body}
          />
        ))}
      </Section>
    </ScreenWrap>
  )
}

export function FeesScreen() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const load = async () => {
    try {
      const { data } = await apiClient.get('/efsc/fee-vouchers')
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
      const { data } = await apiClient.get('/efsc/online-classes')
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

export function LeaveScreen({ children = [], selectedChildId }) {
  const [items, setItems] = useState([])
  const [studentId, setStudentId] = useState(selectedChildId ?? children[0]?.id)
  useEffect(() => {
    if (selectedChildId) setStudentId(selectedChildId)
  }, [selectedChildId])
  const [startDate, setStartDate] = useState('')
  const [endDate, setEndDate] = useState('')
  const [reason, setReason] = useState('')
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')
  const [ok, setOk] = useState('')
  const load = async () => {
    try {
      const { data } = await apiClient.get('/efsc/leave-requests')
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
      await apiClient.post('/efsc/leave-requests', {
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
      {studentId ? (
        <Text style={styles.childLockText}>
          Submitting leave for {childName(children.find((c) => c.id === studentId))}
        </Text>
      ) : null}
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
      const { data } = await apiClient.get('/efsc/in-app-notifications')
      setItems(paginatedItems(data))
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setLoading(false)
    }
  }
  const markRead = async (id) => {
    await apiClient.post(`/efsc/in-app-notifications/${id}/read`)
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
  childRow: { paddingVertical: 4, paddingRight: 8 },
  sectionHint: { fontSize: 13, color: '#64748b', marginBottom: 8 },
  childHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  childHeaderText: { flex: 1, marginLeft: 8 },
  childHeaderName: { fontSize: 18, fontWeight: '700', color: '#0f172a' },
  childHeaderMeta: { fontSize: 14, color: '#64748b', marginTop: 4 },
  childHeaderSub: { fontSize: 12, color: '#94a3b8', marginTop: 2 },
  h1: { fontSize: 22, fontWeight: '700', color: '#0f172a', marginBottom: 12 },
  childLockText: { fontSize: 13, color: '#1e40af', marginBottom: 8, fontWeight: '600' },
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
  btnDisabled: { opacity: 0.6 },
  btnText: { color: '#fff', fontWeight: '600' },
  btnOk: { backgroundColor: '#15803d', flex: 1 },
  btnDanger: { backgroundColor: '#b91c1c', flex: 1 },
  rowActions: { flexDirection: 'row', gap: 8, marginBottom: 12, marginTop: -4 },
  actionCard: { marginBottom: 4 },
  ok: { color: '#15803d', marginBottom: 8 },
  textarea: { minHeight: 80, textAlignVertical: 'top' },
  pickerWrap: { marginBottom: 12 },
  chipRow: { gap: 8, paddingVertical: 4 },
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  chipActive: { backgroundColor: '#dbeafe', borderColor: '#2563eb' },
  chipText: { fontSize: 14, color: '#334155' },
  chipTextActive: { color: '#1d4ed8', fontWeight: '600' },
})
