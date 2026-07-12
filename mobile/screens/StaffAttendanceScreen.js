import { useState, useEffect } from 'react'
import {
  View,
  Text,
  ScrollView,
  ActivityIndicator,
  TextInput,
  Pressable,
  StyleSheet,
} from 'react-native'
import { apiClient } from '../apiClient'
import { formatDate, formatError } from '../utils/format'
import { Card, EmptyNote, ui } from '../components/ui'

const TABS = [
  { id: 'mark', label: 'Mark' },
  { id: 'view', label: 'View' },
  { id: 'summary', label: 'Summary' },
]

function Picker({ label, value, options, onChange }) {
  return (
    <View style={styles.pickerWrap}>
      <Text style={styles.label}>{label}</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
        {options.map((opt) => (
          <Pressable
            key={String(opt.id)}
            onPress={() => onChange(String(opt.id))}
            style={[styles.chip, value === String(opt.id) && styles.chipActive]}
          >
            <Text style={[styles.chipText, value === String(opt.id) && styles.chipTextActive]}>
              {opt.name}
            </Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  )
}

function MarkTab({ classes, sections, areas }) {
  const [classId, setClassId] = useState('')
  const [sectionId, setSectionId] = useState('')
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10))
  const [students, setStudents] = useState([])
  const [statuses, setStatuses] = useState({})
  const [batch, setBatch] = useState(null)
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [msg, setMsg] = useState('')
  const [err, setErr] = useState('')

  const classSections = sections.filter((s) => String(s.school_class_id) === classId)

  useEffect(() => {
    if (classes.length && !classId) {
      setClassId(String(classes[0].id))
    }
  }, [classes])

  useEffect(() => {
    if (classSections.length) {
      setSectionId(String(classSections[0].id))
    } else {
      setSectionId('')
    }
  }, [classId, sections])

  async function loadStudents() {
    if (!sectionId) return
    setLoading(true)
    setErr('')
    setMsg('')
    try {
      const [studentsRes, batchRes] = await Promise.all([
        apiClient.get('/efsc/students', { params: { section_id: sectionId } }),
        apiClient.get('/efsc/attendance/batches', {
          params: { section_id: sectionId, date, per_page: 1 },
        }),
      ])
      const list = studentsRes.data?.data ?? studentsRes.data ?? []
      const next = {}
      for (const s of list) next[s.id] = 'present'
      const existing = (batchRes.data?.data ?? [])[0] ?? null
      setBatch(existing)
      if (existing?.id) {
        const { data: detail } = await apiClient.get(`/efsc/attendance/batches/${existing.id}`)
        for (const r of detail.records || []) next[r.student_id] = r.status
      }
      setStatuses(next)
      setStudents(list)
    } catch (e) {
      setErr(formatError(e))
      setStudents([])
    } finally {
      setLoading(false)
    }
  }

  async function saveDraft() {
    if (!sectionId || !students.length) return
    setSaving(true)
    setErr('')
    setMsg('')
    try {
      const { data } = await apiClient.post('/efsc/attendance/batches', {
        section_id: Number(sectionId),
        date,
        records: students.map((s) => ({ student_id: s.id, status: statuses[s.id] || 'present' })),
      })
      setBatch(data)
      setMsg('Draft saved. Section head will verify and approve.')
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setSaving(false)
    }
  }

  function cycleStatus(studentId) {
    const order = ['present', 'absent', 'leave']
    const current = statuses[studentId] || 'present'
    const next = order[(order.indexOf(current) + 1) % order.length]
    setStatuses((prev) => ({ ...prev, [studentId]: next }))
  }

  return (
    <View>
      <Picker label="Class" value={classId} options={classes} onChange={setClassId} />
      <Picker label="Section" value={sectionId} options={classSections} onChange={setSectionId} />
      <Text style={styles.label}>Date (YYYY-MM-DD)</Text>
      <TextInput style={styles.input} value={date} onChangeText={setDate} />
      <Pressable style={styles.btn} onPress={loadStudents} disabled={!sectionId || loading}>
        <Text style={styles.btnText}>{loading ? 'Loading…' : 'Load students'}</Text>
      </Pressable>
      {students.map((s) => (
        <Pressable key={s.id} style={styles.studentRow} onPress={() => cycleStatus(s.id)}>
          <Text style={styles.studentName}>
            {s.roll_no ? `${s.roll_no} · ` : ''}
            {s.first_name} {s.last_name}
          </Text>
          <Text style={[styles.statusPill, styles[`pill_${statuses[s.id] || 'present'}`]]}>
            {statuses[s.id] || 'present'}
          </Text>
        </Pressable>
      ))}
      {students.length > 0 ? (
        <Pressable style={styles.btn} onPress={saveDraft} disabled={saving}>
          <Text style={styles.btnText}>{saving ? 'Saving…' : 'Save draft'}</Text>
        </Pressable>
      ) : null}
      {batch ? <Text style={styles.meta}>Status: {batch.status}</Text> : null}
      {msg ? <Text style={styles.ok}>{msg}</Text> : null}
      {err ? <Text style={ui.err}>{err}</Text> : null}
    </View>
  )
}

function ViewTab({ classes, sections }) {
  const [classId, setClassId] = useState('')
  const [sectionId, setSectionId] = useState('')
  const [batches, setBatches] = useState([])
  const [expandedId, setExpandedId] = useState(null)
  const [detail, setDetail] = useState(null)
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')

  const classSections = sections.filter((s) => String(s.school_class_id) === classId)

  useEffect(() => {
    if (classes.length && !classId) setClassId(String(classes[0].id))
  }, [classes])

  useEffect(() => {
    if (classSections.length) setSectionId(String(classSections[0].id))
    else setSectionId('')
  }, [classId, sections])

  async function loadBatches() {
    if (!sectionId) return
    setLoading(true)
    setErr('')
    setExpandedId(null)
    setDetail(null)
    try {
      const { data } = await apiClient.get('/efsc/attendance/batches', {
        params: { section_id: sectionId, per_page: 100 },
      })
      setBatches(data?.data ?? [])
    } catch (e) {
      setErr(formatError(e))
      setBatches([])
    } finally {
      setLoading(false)
    }
  }

  async function openBatch(id) {
    if (expandedId === id) {
      setExpandedId(null)
      setDetail(null)
      return
    }
    setExpandedId(id)
    try {
      const { data } = await apiClient.get(`/efsc/attendance/batches/${id}`)
      setDetail(data)
    } catch (e) {
      setErr(formatError(e))
      setDetail(null)
    }
  }

  return (
    <View>
      <Picker label="Class" value={classId} options={classes} onChange={setClassId} />
      <Picker label="Section" value={sectionId} options={classSections} onChange={setSectionId} />
      <Pressable style={styles.btn} onPress={loadBatches} disabled={!sectionId || loading}>
        <Text style={styles.btnText}>{loading ? 'Loading…' : 'Load dates'}</Text>
      </Pressable>
      {batches.length === 0 && !loading ? <EmptyNote text="No attendance records yet." /> : null}
      {batches.map((b) => (
        <View key={b.id}>
          <Card
            title={formatDate(b.date)}
            meta={`${b.status} · ${b.records_count ?? 0} students`}
            onPress={() => openBatch(b.id)}
          />
          {expandedId === b.id && detail ? (
            <View style={styles.detailBox}>
              {(detail.records || []).map((r) => (
                <Text key={r.id} style={styles.detailRow}>
                  {r.student?.first_name} {r.student?.last_name} — {r.status}
                </Text>
              ))}
            </View>
          ) : null}
        </View>
      ))}
      {err ? <Text style={ui.err}>{err}</Text> : null}
    </View>
  )
}

function SummaryTab({ classes, sections, areas }) {
  const [areaId, setAreaId] = useState('')
  const [classId, setClassId] = useState('')
  const [sectionId, setSectionId] = useState('')
  const [rows, setRows] = useState([])
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')

  const filteredClasses = areaId
    ? classes.filter((c) => String(c.area_id) === areaId)
    : classes

  const filteredSections = sectionId
    ? sections.filter((s) => String(s.id) === sectionId)
    : classId
      ? sections.filter((s) => String(s.school_class_id) === classId)
      : areaId
        ? sections.filter((s) => filteredClasses.some((c) => c.id === s.school_class_id))
        : sections

  async function loadSummary() {
    setLoading(true)
    setErr('')
    try {
      const params = {}
      if (areaId) params.area_id = areaId
      if (classId) params.school_class_id = classId
      if (sectionId) params.section_id = sectionId
      const { data } = await apiClient.get('/efsc/attendance/summary', { params })
      setRows(data.students || [])
    } catch (e) {
      setErr(formatError(e))
      setRows([])
    } finally {
      setLoading(false)
    }
  }

  return (
    <View>
      <Picker
        label="Area"
        value={areaId || 'all'}
        options={[{ id: 'all', name: 'All areas' }, ...areas]}
        onChange={(v) => {
          setAreaId(v === 'all' ? '' : v)
          setClassId('')
          setSectionId('')
        }}
      />
      <Picker
        label="Class"
        value={classId || 'all'}
        options={[{ id: 'all', name: 'All classes' }, ...filteredClasses]}
        onChange={(v) => {
          setClassId(v === 'all' ? '' : v)
          setSectionId('')
        }}
      />
      <Picker
        label="Section"
        value={sectionId || 'all'}
        options={[{ id: 'all', name: 'All sections' }, ...filteredSections]}
        onChange={(v) => setSectionId(v === 'all' ? '' : v)}
      />
      <Pressable style={styles.btn} onPress={loadSummary} disabled={loading}>
        <Text style={styles.btnText}>{loading ? 'Loading…' : 'Load summary'}</Text>
      </Pressable>
      {rows.map((row) => (
        <Card
          key={row.student_id}
          title={`${row.first_name} ${row.last_name}`}
          meta={row.section?.name || '—'}
          sub={`Present ${row.present} · Absent ${row.absent} · Leave ${row.leave}`}
        />
      ))}
      {rows.length === 0 && !loading ? <EmptyNote text="No summary data." /> : null}
      {err ? <Text style={ui.err}>{err}</Text> : null}
    </View>
  )
}

export default function StaffAttendanceScreen() {
  const [tab, setTab] = useState('mark')
  const [classes, setClasses] = useState([])
  const [sections, setSections] = useState([])
  const [areas, setAreas] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    Promise.all([
      apiClient.get('/efsc/academic/classes'),
      apiClient.get('/efsc/academic/sections'),
      apiClient.get('/efsc/academic/areas'),
    ])
      .then(([c, s, a]) => {
        setClasses(c.data?.data ?? c.data ?? [])
        setSections(s.data?.data ?? s.data ?? [])
        setAreas(a.data?.data ?? a.data ?? [])
      })
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <ActivityIndicator style={{ marginTop: 24 }} />

  return (
    <ScrollView contentContainerStyle={styles.scroll}>
      <Text style={styles.h1}>Attendance</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabRow}>
        {TABS.map((t) => (
          <Pressable
            key={t.id}
            onPress={() => setTab(t.id)}
            style={[styles.tab, tab === t.id && styles.tabActive]}
          >
            <Text style={[styles.tabText, tab === t.id && styles.tabTextActive]}>{t.label}</Text>
          </Pressable>
        ))}
      </ScrollView>
      {tab === 'mark' ? <MarkTab classes={classes} sections={sections} areas={areas} /> : null}
      {tab === 'view' ? <ViewTab classes={classes} sections={sections} /> : null}
      {tab === 'summary' ? <SummaryTab classes={classes} sections={sections} areas={areas} /> : null}
    </ScrollView>
  )
}

const styles = StyleSheet.create({
  scroll: { padding: 16, paddingBottom: 32 },
  h1: { fontSize: 22, fontWeight: '700', marginBottom: 12, color: '#0f172a' },
  tabRow: { gap: 8, marginBottom: 16 },
  tab: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#f1f5f9',
  },
  tabActive: { backgroundColor: '#2563eb' },
  tabText: { fontWeight: '600', color: '#475569' },
  tabTextActive: { color: '#fff' },
  pickerWrap: { marginBottom: 12 },
  label: { fontSize: 13, fontWeight: '600', color: '#64748b', marginBottom: 6 },
  chipRow: { gap: 8 },
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
  input: {
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 8,
    padding: 10,
    marginBottom: 12,
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
  studentRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  studentName: { flex: 1, fontSize: 15, color: '#0f172a' },
  statusPill: {
    fontSize: 12,
    fontWeight: '600',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    textTransform: 'capitalize',
    overflow: 'hidden',
  },
  pill_present: { backgroundColor: '#dcfce7', color: '#166534' },
  pill_absent: { backgroundColor: '#fee2e2', color: '#991b1b' },
  pill_leave: { backgroundColor: '#fef3c7', color: '#92400e' },
  meta: { color: '#64748b', marginBottom: 8 },
  detailBox: {
    marginLeft: 12,
    marginBottom: 12,
    paddingLeft: 12,
    borderLeftWidth: 2,
    borderLeftColor: '#e2e8f0',
  },
  detailRow: { fontSize: 14, color: '#475569', marginBottom: 4 },
  ok: { color: '#15803d', marginBottom: 8 },
})
