import { useState, useEffect, useMemo } from 'react'
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
import { formatDate, formatError, sortByRollNo } from '../utils/format'
import { withTimeout } from '../utils/withTimeout'
import { Card, EmptyNote, ui } from '../components/ui'
import { useHardwareBack } from '../hooks/useHardwareBack'

const ATTENDANCE_STATUSES = [
  { value: 'present', label: 'Present' },
  { value: 'absent', label: 'Absent' },
  { value: 'leave', label: 'Leave' },
]

function todayInputDate() {
  return new Date().toISOString().slice(0, 10)
}

function monthStartInputDate() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
}

function statusLabel(status) {
  if (status === 'submitted') return 'Pending approval'
  if (status === 'verified') return 'Approved'
  if (status === 'draft') return 'Draft'
  return status || '—'
}

function batchClassSection(batch) {
  const cls = batch.section?.school_class?.name ?? batch.section?.schoolClass?.name
  const sec = batch.section?.name
  if (cls && sec) return `${cls} · ${sec}`
  return cls || sec || '—'
}

function permissionNames(list) {
  return (list || []).map((p) => (typeof p === 'string' ? p : p?.name)).filter(Boolean)
}

function buildTabs(perms) {
  const can = (name) => perms.includes(name)
  const canMark = can('mark_attendance')
  const canApprove = can('verify_attendance')
  const canViewSummary = can('view_attendance_reports') || canApprove
  const canViewStatus = canMark && !canApprove
  const list = []
  if (canApprove) list.push({ id: 'pending', label: 'Pending approval' })
  if (canMark) list.push({ id: 'mark', label: 'Mark Attendance' })
  if (canViewStatus) list.push({ id: 'status', label: 'Attendance status' })
  if (canViewSummary) list.push({ id: 'summary', label: 'Attendance Summary' })
  return list
}

function defaultTabId(tabs, perms) {
  const ids = tabs.map((t) => t.id)
  if (perms.includes('verify_attendance') && ids.includes('pending')) return 'pending'
  if (perms.includes('mark_attendance') && ids.includes('mark')) return 'mark'
  return ids[0] || 'mark'
}

function Picker({ label, value, options, onChange, disabled }) {
  return (
    <View style={[styles.pickerWrap, disabled && styles.disabled]}>
      <Text style={styles.label}>{label}</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
        {options.map((opt) => (
          <Pressable
            key={String(opt.id)}
            onPress={() => !disabled && onChange(String(opt.id))}
            style={[styles.chip, value === String(opt.id) && styles.chipActive]}
            disabled={disabled}
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

function MarkTab({ classes, sections }) {
  const [classId, setClassId] = useState('')
  const [sectionId, setSectionId] = useState('')
  const [date, setDate] = useState(todayInputDate())
  const [students, setStudents] = useState([])
  const [statuses, setStatuses] = useState({})
  const [batch, setBatch] = useState(null)
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [msg, setMsg] = useState('')
  const [err, setErr] = useState('')

  const classSections = sections.filter((s) => String(s.school_class_id) === classId)
  const markLocked = Boolean(batch && ['submitted', 'verified'].includes(batch.status))

  useEffect(() => {
    if (classes.length && !classId) setClassId(String(classes[0].id))
  }, [classes])

  useEffect(() => {
    if (classSections.length) setSectionId(String(classSections[0].id))
    else setSectionId('')
    setStudents([])
    setBatch(null)
    setMsg('')
    setErr('')
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
      const list = sortByRollNo(studentsRes.data?.data ?? studentsRes.data ?? [])
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
    if (!sectionId || !students.length || markLocked) return
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
      setMsg('Draft saved. Submit when ready for section head approval.')
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setSaving(false)
    }
  }

  async function submitBatch(current) {
    if (!current?.id) return
    setSubmitting(true)
    setErr('')
    try {
      const { data } = await apiClient.post(`/efsc/attendance/batches/${current.id}/submit`)
      setBatch(data)
      setMsg('Submitted for approval. Track status under Attendance status.')
      setStudents([])
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setSubmitting(false)
    }
  }

  async function saveAndSubmit() {
    if (!sectionId || !students.length || markLocked) return
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
      setSaving(false)
      await submitBatch(data)
    } catch (e) {
      setErr(formatError(e))
      setSaving(false)
    }
  }

  function setStudentStatus(studentId, value) {
    if (markLocked) return
    setStatuses((prev) => ({ ...prev, [studentId]: value }))
  }

  return (
    <View>
      <Text style={styles.h2}>Mark Attendance</Text>
      <Picker label="Class" value={classId} options={classes} onChange={setClassId} />
      <Picker
        label="Section"
        value={sectionId}
        options={classSections}
        onChange={setSectionId}
        disabled={!classId}
      />
      <Text style={styles.label}>Date (YYYY-MM-DD)</Text>
      <TextInput style={styles.input} value={date} onChangeText={setDate} />
      <Pressable style={styles.btn} onPress={loadStudents} disabled={!sectionId || loading}>
        <Text style={styles.btnText}>{loading ? 'Loading…' : 'Load students'}</Text>
      </Pressable>

      {students.map((s, index) => (
        <View key={s.id} style={styles.studentBlock}>
          <Text style={styles.studentName}>
            {index + 1}. {s.roll_no ? `${s.roll_no} · ` : ''}
            {s.first_name} {s.last_name}
          </Text>
          {s.father_name ? <Text style={styles.meta}>Father: {s.father_name}</Text> : null}
          <View style={styles.statusRow}>
            {ATTENDANCE_STATUSES.map((opt) => {
              const active = (statuses[s.id] || 'present') === opt.value
              return (
                <Pressable
                  key={opt.value}
                  onPress={() => setStudentStatus(s.id, opt.value)}
                  disabled={markLocked}
                  style={[
                    styles.statusChip,
                    active && styles.statusChipActive,
                    markLocked && styles.disabled,
                  ]}
                >
                  <Text style={[styles.statusChipText, active && styles.statusChipTextActive]}>
                    {opt.label}
                  </Text>
                </Pressable>
              )
            })}
          </View>
        </View>
      ))}

      {students.length > 0 && markLocked ? (
        <Text style={styles.lockedNote}>
          This attendance is {statusLabel(batch?.status)} and cannot be edited. Check Attendance status for details.
        </Text>
      ) : null}

      {students.length > 0 && !markLocked ? (
        <View style={styles.actionRow}>
          <Pressable
            style={[styles.btn, styles.btnSecondary]}
            onPress={saveDraft}
            disabled={saving || submitting}
          >
            <Text style={[styles.btnText, styles.btnSecondaryText]}>
              {saving ? 'Saving…' : 'Save draft'}
            </Text>
          </Pressable>
          <Pressable style={styles.btn} onPress={saveAndSubmit} disabled={saving || submitting}>
            <Text style={styles.btnText}>{submitting ? 'Submitting…' : 'Submit attendance'}</Text>
          </Pressable>
        </View>
      ) : null}

      {batch && !markLocked ? (
        <Text style={styles.meta}>Status: {statusLabel(batch.status)}</Text>
      ) : null}
      {msg ? <Text style={styles.ok}>{msg}</Text> : null}
      {err ? <Text style={ui.err}>{err}</Text> : null}
    </View>
  )
}

function PendingTab({ classes, sections }) {
  const [classId, setClassId] = useState('')
  const [sectionId, setSectionId] = useState('')
  const [batches, setBatches] = useState([])
  const [loaded, setLoaded] = useState(false)
  const [expandedId, setExpandedId] = useState(null)
  const [detail, setDetail] = useState(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [loading, setLoading] = useState(false)
  const [verifying, setVerifying] = useState(false)
  const [err, setErr] = useState('')

  const classSections = classId
    ? sections.filter((s) => String(s.school_class_id) === classId)
    : sections

  async function loadPending() {
    setLoading(true)
    setErr('')
    setExpandedId(null)
    setDetail(null)
    try {
      const params = { status: 'submitted', per_page: 100 }
      if (sectionId) params.section_id = sectionId
      else if (classId) params.school_class_id = classId
      const { data } = await apiClient.get('/efsc/attendance/batches', { params })
      setBatches(data?.data ?? [])
      setLoaded(true)
    } catch (e) {
      setErr(formatError(e))
      setBatches([])
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadPending()
  }, [])

  async function toggleBatch(id) {
    if (expandedId === id) {
      setExpandedId(null)
      setDetail(null)
      return
    }
    setExpandedId(id)
    setDetailLoading(true)
    try {
      const { data } = await apiClient.get(`/efsc/attendance/batches/${id}`)
      setDetail(data)
    } catch (e) {
      setErr(formatError(e))
      setDetail(null)
    } finally {
      setDetailLoading(false)
    }
  }

  async function approveBatch(id) {
    setVerifying(true)
    setErr('')
    try {
      await apiClient.post(`/efsc/attendance/batches/${id}/verify`)
      setBatches((prev) => prev.filter((b) => b.id !== id))
      setExpandedId(null)
      setDetail(null)
    } catch (e) {
      setErr(formatError(e))
    } finally {
      setVerifying(false)
    }
  }

  return (
    <View>
      <Text style={styles.h2}>Pending approval</Text>
      <Text style={styles.hint}>
        Submitted attendance awaiting approval. Approved records appear in Attendance summary.
      </Text>
      <Picker
        label="Class"
        value={classId || 'all'}
        options={[{ id: 'all', name: 'All classes' }, ...classes]}
        onChange={(v) => {
          setClassId(v === 'all' ? '' : v)
          setSectionId('')
        }}
      />
      <Picker
        label="Section"
        value={sectionId || 'all'}
        options={[{ id: 'all', name: 'All sections' }, ...classSections]}
        onChange={(v) => setSectionId(v === 'all' ? '' : v)}
        disabled={!classId}
      />
      <Pressable style={styles.btn} onPress={loadPending} disabled={loading}>
        <Text style={styles.btnText}>{loading ? 'Loading…' : 'Refresh'}</Text>
      </Pressable>

      {err ? <Text style={ui.err}>{err}</Text> : null}
      {loaded && !batches.length && !loading ? (
        <EmptyNote text="No attendance pending approval." />
      ) : null}

      {batches.map((b) => (
        <View key={b.id}>
          <Card
            title={formatDate(b.date)}
            meta={`${batchClassSection(b)} · Pending approval`}
            sub={`${b.records_count ?? 0} students · ${b.submitted_by?.name || '—'}`}
            onPress={() => toggleBatch(b.id)}
          />
          {expandedId === b.id ? (
            <View style={styles.detailBox}>
              {detailLoading ? <ActivityIndicator /> : null}
              {!detailLoading && detail ? (
                <>
                  {(detail.records || []).map((r) => (
                    <View key={r.id} style={styles.detailLine}>
                      <Text style={styles.detailRow}>
                        {r.student?.first_name} {r.student?.last_name}
                      </Text>
                      <Text style={[styles.statusPill, styles[`pill_${r.status}`]]}>{r.status}</Text>
                    </View>
                  ))}
                  <Pressable
                    style={styles.btn}
                    onPress={() => approveBatch(b.id)}
                    disabled={verifying}
                  >
                    <Text style={styles.btnText}>
                      {verifying ? 'Approving…' : 'Approve attendance'}
                    </Text>
                  </Pressable>
                </>
              ) : null}
            </View>
          ) : null}
        </View>
      ))}
    </View>
  )
}

function StatusTab({ classes, sections, canMark, canViewSummary }) {
  const [classId, setClassId] = useState('')
  const [sectionId, setSectionId] = useState('')
  const [batches, setBatches] = useState([])
  const [loaded, setLoaded] = useState(false)
  const [expandedId, setExpandedId] = useState(null)
  const [detail, setDetail] = useState(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')

  const classSections = sections.filter((s) => String(s.school_class_id) === classId)

  useEffect(() => {
    if (classes.length && !classId) setClassId(String(classes[0].id))
  }, [classes])

  useEffect(() => {
    if (classSections.length) setSectionId(String(classSections[0].id))
    else setSectionId('')
    setBatches([])
    setLoaded(false)
    setExpandedId(null)
    setDetail(null)
  }, [classId, sections])

  async function loadBatches() {
    if (!sectionId) return
    setLoading(true)
    setErr('')
    setExpandedId(null)
    setDetail(null)
    try {
      const params = {
        section_id: sectionId,
        status_in: 'submitted,verified',
        per_page: 100,
      }
      if (canMark && !canViewSummary) params.own_only = 1
      const { data } = await apiClient.get('/efsc/attendance/batches', { params })
      setBatches(data?.data ?? [])
      setLoaded(true)
    } catch (e) {
      setErr(formatError(e))
      setBatches([])
    } finally {
      setLoading(false)
    }
  }

  async function toggleBatch(id) {
    if (expandedId === id) {
      setExpandedId(null)
      setDetail(null)
      return
    }
    setExpandedId(id)
    setDetailLoading(true)
    try {
      const { data } = await apiClient.get(`/efsc/attendance/batches/${id}`)
      setDetail(data)
    } catch (e) {
      setErr(formatError(e))
      setDetail(null)
    } finally {
      setDetailLoading(false)
    }
  }

  return (
    <View>
      <Text style={styles.h2}>Attendance status</Text>
      <Text style={styles.hint}>
        View submitted and approved attendance. Drafts remain editable under Mark Attendance.
      </Text>
      <Picker label="Class" value={classId} options={classes} onChange={setClassId} />
      <Picker
        label="Section"
        value={sectionId}
        options={classSections}
        onChange={setSectionId}
        disabled={!classId}
      />
      <Pressable style={styles.btn} onPress={loadBatches} disabled={!sectionId || loading}>
        <Text style={styles.btnText}>{loading ? 'Loading…' : 'Load records'}</Text>
      </Pressable>

      {err ? <Text style={ui.err}>{err}</Text> : null}
      {loaded && !batches.length && !loading ? (
        <EmptyNote text="No submitted or approved attendance for this section." />
      ) : null}

      {batches.map((b) => (
        <View key={b.id}>
          <Card
            title={formatDate(b.date)}
            meta={statusLabel(b.status)}
            sub={`${b.records_count ?? 0} students`}
            onPress={() => toggleBatch(b.id)}
          />
          {expandedId === b.id ? (
            <View style={styles.detailBox}>
              {detailLoading ? <ActivityIndicator /> : null}
              {!detailLoading && detail
                ? (detail.records || []).map((r) => (
                    <View key={r.id} style={styles.detailLine}>
                      <Text style={styles.detailRow}>
                        {r.student?.first_name} {r.student?.last_name}
                      </Text>
                      <Text style={[styles.statusPill, styles[`pill_${r.status}`]]}>{r.status}</Text>
                    </View>
                  ))
                : null}
            </View>
          ) : null}
        </View>
      ))}
    </View>
  )
}

function SummaryTab({ classes, sections, areas }) {
  const [areaId, setAreaId] = useState('')
  const [classId, setClassId] = useState('')
  const [sectionId, setSectionId] = useState('')
  const [from, setFrom] = useState(monthStartInputDate())
  const [to, setTo] = useState(todayInputDate())
  const [mode, setMode] = useState('none')
  const [rows, setRows] = useState([])
  const [totals, setTotals] = useState({
    present: 0,
    absent: 0,
    leave: 0,
    students: 0,
    school_days: 0,
  })
  const [breakdown, setBreakdown] = useState([])
  const [loaded, setLoaded] = useState(false)
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState('')

  const filteredClasses = areaId
    ? classes.filter((c) => String(c.area_id) === areaId)
    : classes

  const filteredSections = classId
    ? sections.filter((s) => String(s.school_class_id) === classId)
    : areaId
      ? sections.filter((s) => filteredClasses.some((c) => c.id === s.school_class_id))
      : sections

  const canLoad = Boolean(sectionId || areaId || classId)

  async function loadSummary() {
    if (!canLoad) {
      setMode('none')
      setLoaded(true)
      setRows([])
      setBreakdown([])
      return
    }
    setLoading(true)
    setErr('')
    try {
      const params = {}
      if (areaId) params.area_id = areaId
      if (classId) params.school_class_id = classId
      if (sectionId) params.section_id = sectionId
      if (from) params.from = from
      if (to) params.to = to
      const { data } = await apiClient.get('/efsc/attendance/summary', { params })
      setMode(data.mode || 'none')
      if (data.mode === 'students') {
        setRows(data.students || [])
        setBreakdown([])
      } else {
        setTotals(
          data.totals || { present: 0, absent: 0, leave: 0, students: 0, school_days: 0 },
        )
        setBreakdown(data.by_section || [])
        setRows([])
      }
      setLoaded(true)
    } catch (e) {
      setErr(formatError(e))
      setMode('none')
      setRows([])
      setBreakdown([])
    } finally {
      setLoading(false)
    }
  }

  return (
    <View>
      <Text style={styles.h2}>Attendance Summary</Text>
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
      <Text style={styles.label}>From (YYYY-MM-DD)</Text>
      <TextInput style={styles.input} value={from} onChangeText={setFrom} />
      <Text style={styles.label}>To (YYYY-MM-DD)</Text>
      <TextInput style={styles.input} value={to} onChangeText={setTo} />
      <Pressable style={styles.btn} onPress={loadSummary} disabled={loading || !canLoad}>
        <Text style={styles.btnText}>{loading ? 'Loading…' : 'Load summary'}</Text>
      </Pressable>

      {err ? <Text style={ui.err}>{err}</Text> : null}
      {loaded && mode === 'none' ? (
        <EmptyNote text="Select an area or class for a cumulative summary, or select a section for per-student totals." />
      ) : null}

      {loaded && mode === 'cumulative' ? (
        <>
          <View style={styles.totalsRow}>
            <View style={styles.totalCard}>
              <Text style={styles.totalValue}>{totals.present}</Text>
              <Text style={styles.totalLabel}>Present</Text>
            </View>
            <View style={styles.totalCard}>
              <Text style={styles.totalValue}>{totals.absent}</Text>
              <Text style={styles.totalLabel}>Absent</Text>
            </View>
            <View style={styles.totalCard}>
              <Text style={styles.totalValue}>{totals.leave}</Text>
              <Text style={styles.totalLabel}>Leave</Text>
            </View>
            <View style={styles.totalCard}>
              <Text style={styles.totalValue}>{totals.students}</Text>
              <Text style={styles.totalLabel}>Students</Text>
            </View>
            <View style={styles.totalCard}>
              <Text style={styles.totalValue}>{totals.school_days}</Text>
              <Text style={styles.totalLabel}>School days</Text>
            </View>
          </View>
          {breakdown.length === 0 ? (
            <EmptyNote text="No sections found for the selected area or class." />
          ) : (
            breakdown.map((row) => (
              <Card
                key={row.section_id}
                title={`${row.class_name} · ${row.section_name}`}
                sub={`Total ${row.total} · Present ${row.present} · Absent ${row.absent} · Leave ${row.leave}`}
              />
            ))
          )}
        </>
      ) : null}

      {loaded && mode === 'students' ? (
        <>
          {rows.length === 0 ? <EmptyNote text="No students or attendance found for this section." /> : null}
          {rows.map((row) => (
            <Card
              key={row.student_id}
              title={`${row.roll_no ? `${row.roll_no} · ` : ''}${row.first_name} ${row.last_name}`}
              sub={`Present ${row.present} · Absent ${row.absent} · Leave ${row.leave}`}
            />
          ))}
        </>
      ) : null}
    </View>
  )
}

export default function StaffAttendanceScreen({ permissions = [] }) {
  const perms = useMemo(() => permissionNames(permissions), [permissions])
  const tabs = useMemo(() => buildTabs(perms), [perms])
  const [tab, setTab] = useState(() =>
    defaultTabId(buildTabs(permissionNames(permissions)), permissionNames(permissions)),
  )
  const [classes, setClasses] = useState([])
  const [sections, setSections] = useState([])
  const [areas, setAreas] = useState([])
  const [loading, setLoading] = useState(true)
  const [err, setErr] = useState('')

  const canMark = perms.includes('mark_attendance')
  const canViewSummary = perms.includes('view_attendance_reports') || perms.includes('verify_attendance')
  const defaultTab = useMemo(() => defaultTabId(tabs, perms), [tabs, perms])

  useHardwareBack(() => {
    if (tab === defaultTab) return false
    setTab(defaultTab)
    return true
  }, [tab, defaultTab])

  useEffect(() => {
    const next = defaultTabId(tabs, perms)
    if (!tabs.some((t) => t.id === tab)) setTab(next)
  }, [tabs, perms, tab])

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setErr('')
    withTimeout(
      Promise.all([
        apiClient.get('/efsc/academic/classes'),
        apiClient.get('/efsc/academic/sections'),
        apiClient.get('/efsc/academic/areas'),
      ]),
      20000,
      'Attendance setup',
    )
      .then(([c, s, a]) => {
        if (cancelled) return
        setClasses(c.data?.data ?? c.data ?? [])
        setSections(s.data?.data ?? s.data ?? [])
        setAreas(a.data?.data ?? a.data ?? [])
      })
      .catch((e) => {
        if (cancelled) return
        setErr(formatError(e))
        setClasses([])
        setSections([])
        setAreas([])
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [])

  if (loading) return <ActivityIndicator style={{ marginTop: 24 }} />

  if (!tabs.length) {
    return (
      <View style={styles.scroll}>
        <Text style={styles.h1}>Attendance</Text>
        <EmptyNote text="You do not have attendance permissions for this account." />
      </View>
    )
  }

  return (
    <ScrollView contentContainerStyle={styles.scroll}>
      <Text style={styles.h1}>Attendance</Text>
      {err ? <Text style={ui.err}>{err}</Text> : null}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabRow}>
        {tabs.map((t) => (
          <Pressable
            key={t.id}
            onPress={() => setTab(t.id)}
            style={[styles.tab, tab === t.id && styles.tabActive]}
          >
            <Text style={[styles.tabText, tab === t.id && styles.tabTextActive]}>{t.label}</Text>
          </Pressable>
        ))}
      </ScrollView>
      {tab === 'pending' ? <PendingTab classes={classes} sections={sections} /> : null}
      {tab === 'mark' ? <MarkTab classes={classes} sections={sections} /> : null}
      {tab === 'status' ? (
        <StatusTab
          classes={classes}
          sections={sections}
          canMark={canMark}
          canViewSummary={canViewSummary}
        />
      ) : null}
      {tab === 'summary' ? (
        <SummaryTab classes={classes} sections={sections} areas={areas} />
      ) : null}
    </ScrollView>
  )
}

const styles = StyleSheet.create({
  scroll: { padding: 16, paddingBottom: 32 },
  h1: { fontSize: 22, fontWeight: '700', marginBottom: 12, color: '#0f172a' },
  h2: { fontSize: 17, fontWeight: '700', marginBottom: 8, color: '#0f172a' },
  hint: { fontSize: 13, color: '#64748b', marginBottom: 12 },
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
  disabled: { opacity: 0.55 },
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
    flex: 1,
  },
  btnSecondary: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#cbd5e1',
  },
  btnText: { color: '#fff', fontWeight: '600' },
  btnSecondaryText: { color: '#334155' },
  actionRow: { flexDirection: 'row', gap: 8 },
  studentBlock: {
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  studentName: { fontSize: 15, color: '#0f172a', fontWeight: '600' },
  statusRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 8 },
  statusChip: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    backgroundColor: '#f8fafc',
  },
  statusChipActive: { borderColor: '#2563eb', borderWidth: 2, backgroundColor: '#dbeafe' },
  statusChipText: { fontSize: 12, fontWeight: '600', color: '#64748b' },
  statusChipTextActive: { color: '#1d4ed8' },
  lockedNote: { color: '#b45309', marginBottom: 12, fontSize: 13 },
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
  meta: { color: '#64748b', marginBottom: 8, fontSize: 13 },
  detailBox: {
    marginLeft: 12,
    marginBottom: 12,
    paddingLeft: 12,
    borderLeftWidth: 2,
    borderLeftColor: '#e2e8f0',
  },
  detailLine: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  detailRow: { fontSize: 14, color: '#475569', flex: 1, marginRight: 8 },
  ok: { color: '#15803d', marginBottom: 8 },
  totalsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 12 },
  totalCard: {
    minWidth: 88,
    flexGrow: 1,
    backgroundColor: '#f8fafc',
    borderRadius: 8,
    padding: 10,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  totalValue: { fontSize: 18, fontWeight: '700', color: '#0f172a' },
  totalLabel: { fontSize: 12, color: '#64748b', marginTop: 2 },
})
