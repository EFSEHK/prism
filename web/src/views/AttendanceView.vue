<template>
  <div class="attendance-page">
    <h1>Attendance</h1>

    <div class="tabs">
      <button
        v-for="t in tabs"
        :key="t.id"
        type="button"
        :class="{ active: activeTab === t.id }"
        @click="activeTab = t.id"
      >
        {{ t.label }}
      </button>
    </div>

    <!-- Mark Attendance -->
    <section v-if="activeTab === 'mark'" class="card">
      <h2>Mark Attendance</h2>
      <div class="enroll-filters">
        <div class="field picker-field">
          <span class="field-label">Class</span>
          <SearchableSelect
            v-model="classId"
            :options="classOptions"
            placeholder="Select class…"
            search-placeholder="Search classes…"
            :allow-empty="false"
            @change="onMarkClassChange"
          />
        </div>
        <div class="field picker-field">
          <span class="field-label">Section</span>
          <SearchableSelect
            v-model="sectionId"
            :options="markSectionOptions"
            placeholder="Select section…"
            search-placeholder="Search sections…"
            :disabled="!classId"
            :allow-empty="false"
          />
        </div>
        <div class="field picker-field">
          <span class="field-label">Date</span>
          <input v-model="markDate" type="date" />
        </div>
      </div>
      <div class="filter-actions">
        <button type="button" class="primary" :disabled="!sectionId || loadingMark" @click="loadMarkStudents">
          {{ loadingMark ? 'Loading…' : 'Load students' }}
        </button>
      </div>

      <div v-if="markStudents.length" class="student-list">
        <div class="table-wrap">
          <table class="data-table mark-table">
            <thead>
              <tr>
                <th class="col-serial">#</th>
                <th class="col-roll">Roll no</th>
                <th class="col-student">Student</th>
                <th class="col-father">Father name</th>
                <th class="col-mark-status">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(s, index) in markStudents" :key="s.id">
                <td class="col-serial">{{ index + 1 }}</td>
                <td class="col-roll">{{ s.roll_no || '—' }}</td>
                <td class="col-student cell-name">{{ s.first_name }} {{ s.last_name }}</td>
                <td class="col-father">{{ s.father_name || '—' }}</td>
                <td class="col-mark-status">
                  <div class="status-radios" role="radiogroup" :aria-label="`Attendance for ${s.first_name}`">
                    <label
                      v-for="opt in attendanceStatuses"
                      :key="opt.value"
                      class="status-radio"
                      :class="[opt.value, { active: statuses[s.id] === opt.value }]"
                    >
                      <input
                        v-model="statuses[s.id]"
                        type="radio"
                        :name="`attendance-${s.id}`"
                        :value="opt.value"
                        :disabled="markLocked"
                      />
                      <span>{{ opt.label }}</span>
                    </label>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="markLocked" class="locked-note">
          This attendance is <strong>{{ statusLabel(currentBatch.status) }}</strong> and cannot be edited.
          Check <strong>Attendance status</strong> for details.
        </p>
        <div v-else class="modal-actions">
          <button type="button" class="secondary" :disabled="saving || submitting" @click="saveDraft">
            {{ saving ? 'Saving…' : 'Save draft' }}
          </button>
          <button type="button" class="primary" :disabled="saving || submitting" @click="saveAndSubmit">
            {{ submitting ? 'Submitting…' : 'Submit attendance' }}
          </button>
        </div>
        <p v-if="currentBatch && !markLocked" class="status-note">
          Status: <strong>{{ statusLabel(currentBatch.status) }}</strong>
        </p>
      </div>
      <p v-if="markMsg" class="ok">{{ markMsg }}</p>
      <p v-if="markErr" class="error">{{ markErr }}</p>
    </section>

    <!-- Pending approval (section head, principal, admin, etc.) -->
    <section v-if="activeTab === 'pending'" class="card">
      <h2>Pending approval</h2>
      <p class="muted small">Submitted attendance awaiting approval. Approved records appear in Attendance summary.</p>
      <div class="enroll-filters">
        <div class="field picker-field">
          <span class="field-label">Class</span>
          <SearchableSelect
            v-model="pendingClassId"
            :options="classOptions"
            placeholder="All classes"
            search-placeholder="Search classes…"
            @change="onPendingClassChange"
          />
        </div>
        <div v-if="pendingClassId" class="field picker-field">
          <span class="field-label">Section</span>
          <SearchableSelect
            v-model="pendingSectionId"
            :options="pendingSectionOptions"
            placeholder="All sections"
            search-placeholder="Search sections…"
          />
        </div>
      </div>
      <div class="filter-actions">
        <button type="button" class="primary" :disabled="loadingPending" @click="loadPending">
          {{ loadingPending ? 'Loading…' : 'Refresh' }}
        </button>
      </div>

      <p v-if="pendingErr" class="error">{{ pendingErr }}</p>
      <p v-else-if="pendingLoaded && !pendingBatches.length" class="empty">No attendance pending approval.</p>

      <div v-if="pendingBatches.length" class="batch-list">
        <div v-for="b in pendingBatches" :key="b.id" class="batch-item">
          <button type="button" class="batch-head" @click="togglePendingBatch(b.id)">
            <span>{{ formatDate(b.date) }}</span>
            <span class="batch-meta">{{ batchClassSection(b) }}</span>
            <span class="status-badge submitted">Pending approval</span>
            <span class="muted">{{ b.records_count }} students · {{ b.submitted_by?.name || '—' }}</span>
          </button>
          <div v-if="expandedPendingId === b.id" class="batch-detail">
            <p v-if="pendingDetailLoading" class="muted">Loading…</p>
            <template v-else-if="pendingDetail">
              <div v-for="r in pendingDetail.records" :key="r.id" class="detail-row">
                <span>{{ r.student?.first_name }} {{ r.student?.last_name }}</span>
                <span :class="['pill', r.status]">{{ r.status }}</span>
              </div>
              <div class="modal-actions">
                <button type="button" class="primary" :disabled="verifying" @click="approveBatch(b.id)">
                  {{ verifying ? 'Approving…' : 'Approve attendance' }}
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </section>

    <!-- Attendance status (teacher / class incharge — submitted & approved only) -->
    <section v-if="activeTab === 'status'" class="card">
      <h2>Attendance status</h2>
      <p class="muted small">View submitted and approved attendance. Drafts remain editable under Mark Attendance.</p>
      <div class="enroll-filters">
        <div class="field picker-field">
          <span class="field-label">Class</span>
          <SearchableSelect
            v-model="statusClassId"
            :options="classOptions"
            placeholder="Select class…"
            search-placeholder="Search classes…"
            :allow-empty="false"
            @change="onStatusClassChange"
          />
        </div>
        <div class="field picker-field">
          <span class="field-label">Section</span>
          <SearchableSelect
            v-model="statusSectionId"
            :options="statusSectionOptions"
            placeholder="Select section…"
            search-placeholder="Search sections…"
            :disabled="!statusClassId"
            :allow-empty="false"
          />
        </div>
      </div>
      <div class="filter-actions">
        <button type="button" class="primary" :disabled="!statusSectionId || loadingStatus" @click="loadStatusBatches">
          {{ loadingStatus ? 'Loading…' : 'Load records' }}
        </button>
      </div>

      <p v-if="statusErr" class="error">{{ statusErr }}</p>
      <p v-else-if="statusLoaded && !statusBatches.length" class="empty">No submitted or approved attendance for this section.</p>

      <div v-if="statusBatches.length" class="batch-list">
        <div v-for="b in statusBatches" :key="b.id" class="batch-item">
          <button type="button" class="batch-head" @click="toggleStatusBatch(b.id)">
            <span>{{ formatDate(b.date) }}</span>
            <span class="status-badge" :class="b.status">{{ statusLabel(b.status) }}</span>
            <span class="muted">{{ b.records_count }} students</span>
          </button>
          <div v-if="expandedStatusId === b.id" class="batch-detail">
            <p v-if="statusDetailLoading" class="muted">Loading…</p>
            <template v-else-if="statusDetail">
              <div v-for="r in statusDetail.records" :key="r.id" class="detail-row">
                <span>{{ r.student?.first_name }} {{ r.student?.last_name }}</span>
                <span :class="['pill', r.status]">{{ r.status }}</span>
              </div>
            </template>
          </div>
        </div>
      </div>
    </section>

    <!-- Attendance Summary -->
    <section v-if="activeTab === 'summary'" class="card">
      <h2>Attendance Summary</h2>
      <div class="enroll-filters">
        <div class="field picker-field">
          <span class="field-label">Class</span>
          <SearchableSelect
            v-model="summaryClassId"
            :options="classOptions"
            placeholder="All classes"
            search-placeholder="Search classes…"
            @change="onSummaryClassChange"
          />
        </div>
        <div v-if="summaryClassId" class="field picker-field">
          <span class="field-label">Section</span>
          <SearchableSelect
            v-model="summarySectionId"
            :options="summarySectionOptions"
            placeholder="All sections"
            search-placeholder="Search sections…"
          />
        </div>
      </div>
      <div class="enroll-filters summary-date-row">
        <div class="field picker-field">
          <span class="field-label">From</span>
          <input v-model="summaryFrom" type="date" />
        </div>
        <div class="field picker-field">
          <span class="field-label">To</span>
          <input v-model="summaryTo" type="date" />
        </div>
        <div class="field field-action">
          <span class="field-label">&nbsp;</span>
          <button type="button" class="primary" :disabled="loadingSummary || !canLoadSummary" @click="loadSummary">
            {{ loadingSummary ? 'Loading…' : 'Load summary' }}
          </button>
        </div>
      </div>

      <p v-if="summaryErr" class="error">{{ summaryErr }}</p>
      <p v-else-if="summaryLoaded && summaryMode === 'none'" class="empty">
        Select a class for a cumulative summary, or select a section for per-student totals.
      </p>

      <template v-else-if="summaryLoaded && summaryMode === 'cumulative'">
        <div class="summary-totals">
          <div class="total-card">
            <span class="total-value">{{ cumulativeTotals.present }}</span>
            <span class="total-label">Present</span>
          </div>
          <div class="total-card">
            <span class="total-value">{{ cumulativeTotals.absent }}</span>
            <span class="total-label">Absent</span>
          </div>
          <div class="total-card">
            <span class="total-value">{{ cumulativeTotals.leave }}</span>
            <span class="total-label">Leave</span>
          </div>
          <div class="total-card muted-card">
            <span class="total-value">{{ cumulativeTotals.students }}</span>
            <span class="total-label">Students</span>
          </div>
          <div class="total-card muted-card">
            <span class="total-value">{{ cumulativeTotals.school_days }}</span>
            <span class="total-label">School days</span>
          </div>
        </div>
        <p v-if="!cumulativeBreakdown.length" class="empty">No sections found for the selected class.</p>
        <div v-else class="table-wrap summary-breakdown">
          <table class="data-table">
            <thead>
              <tr>
                <th>Class</th>
                <th>Section</th>
                <th class="col-status">Total</th>
                <th class="col-status">Present</th>
                <th class="col-status">Absent</th>
                <th class="col-status">Leave</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in cumulativeBreakdown" :key="row.section_id">
                <td>{{ breakdownClassLabel(row) }}</td>
                <td class="cell-name">{{ row.section_name }}</td>
                <td class="col-status">{{ row.total }}</td>
                <td class="col-status">{{ row.present }}</td>
                <td class="col-status">{{ row.absent }}</td>
                <td class="col-status">{{ row.leave }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <template v-else-if="summaryLoaded && summaryMode === 'students'">
        <p v-if="!summaryRows.length" class="empty">No students or attendance found for this section.</p>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student</th>
                <th class="col-status">Present</th>
                <th class="col-status">Absent</th>
                <th class="col-status">Leave</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in summaryRows" :key="row.student_id">
                <td class="cell-name">
                  <span v-if="row.roll_no" class="roll">{{ row.roll_no }}</span>
                  {{ row.first_name }} {{ row.last_name }}
                </td>
                <td class="col-status">{{ row.present }}</td>
                <td class="col-status">{{ row.absent }}</td>
                <td class="col-status">{{ row.leave }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import api from '../api/client'
import SearchableSelect from '../components/SearchableSelect.vue'
import { usePermissions } from '../composables/usePermissions'
import { formatDate, todayInputDate, monthStartInputDate, sortByRollNo } from '../composables/format'

const { can } = usePermissions()
const canMark = computed(() => can('mark_attendance'))
const canApprove = computed(() => can('verify_attendance'))
const canViewSummary = computed(() => can('view_attendance_reports') || can('verify_attendance'))
const canViewStatus = computed(() => canMark.value && !canApprove.value)

const tabs = computed(() => {
  const list = []
  if (canApprove.value) list.push({ id: 'pending', label: 'Pending approval' })
  if (canMark.value) list.push({ id: 'mark', label: 'Mark Attendance' })
  if (canViewStatus.value) list.push({ id: 'status', label: 'Attendance status' })
  if (canViewSummary.value) list.push({ id: 'summary', label: 'Attendance Summary' })
  return list
})

const attendanceStatuses = [
  { value: 'present', label: 'Present' },
  { value: 'absent', label: 'Absent' },
  { value: 'leave', label: 'Leave' },
]
const activeTab = ref('mark')

const allAreas = ref([])
const allClasses = ref([])
const allSections = ref([])

const classId = ref('')
const sectionId = ref('')
const markDate = ref(todayInputDate())
const markStudents = ref([])
const statuses = reactive({})
const currentBatch = ref(null)
const loadingMark = ref(false)
const saving = ref(false)
const submitting = ref(false)
const markMsg = ref('')
const markErr = ref('')

const markLocked = computed(() =>
  Boolean(currentBatch.value && ['submitted', 'verified'].includes(currentBatch.value.status)),
)

const pendingClassId = ref('')
const pendingSectionId = ref('')
const pendingBatches = ref([])
const pendingLoaded = ref(false)
const loadingPending = ref(false)
const pendingErr = ref('')
const expandedPendingId = ref(null)
const pendingDetail = ref(null)
const pendingDetailLoading = ref(false)

const statusClassId = ref('')
const statusSectionId = ref('')
const statusBatches = ref([])
const statusLoaded = ref(false)
const loadingStatus = ref(false)
const statusErr = ref('')
const expandedStatusId = ref(null)
const statusDetail = ref(null)
const statusDetailLoading = ref(false)

const verifying = ref(false)

const summaryClassId = ref('')
const summarySectionId = ref('')
const summaryFrom = ref(monthStartInputDate())
const summaryTo = ref(todayInputDate())
const summaryMode = ref('none')
const summaryRows = ref([])
const cumulativeTotals = ref({ present: 0, absent: 0, leave: 0, students: 0, school_days: 0, sections: 0 })
const cumulativeBreakdown = ref([])
const summaryLoaded = ref(false)
const loadingSummary = ref(false)
const summaryErr = ref('')

const canLoadSummary = computed(
  () => Boolean(summarySectionId.value || summaryClassId.value),
)

function sortSections(items) {
  return [...items].sort((a, b) => {
    const seqCmp = (a.sequence ?? 0) - (b.sequence ?? 0)
    if (seqCmp !== 0) return seqCmp
    return (a.name || '').localeCompare(b.name || '')
  })
}

function sortClassesByAreaThenClass(classes) {
  const areaSeq = new Map(allAreas.value.map((a) => [a.id, a.sequence ?? 0]))
  return [...classes].sort((a, b) => {
    const areaCmp = (areaSeq.get(a.area_id) ?? 0) - (areaSeq.get(b.area_id) ?? 0)
    if (areaCmp !== 0) return areaCmp
    const seqCmp = (a.sequence ?? 0) - (b.sequence ?? 0)
    if (seqCmp !== 0) return seqCmp
    return (a.name || '').localeCompare(b.name || '')
  })
}

function classesForPicker() {
  return sortClassesByAreaThenClass(allClasses.value)
}

function sectionsForClass(classIdValue) {
  const list = classIdValue
    ? allSections.value.filter((s) => String(s.school_class_id) === classIdValue)
    : allSections.value
  return sortSections(list)
}

function toOptions(items) {
  return items.map((i) => ({ value: String(i.id), label: i.name }))
}

function classGender(areaName) {
  return areaName?.toLowerCase().includes('boy') ? 'Boys' : 'Girls'
}

function classLabel(c) {
  if (!c?.name) return '—'
  return `${c.name} (${classGender(c.area?.name)})`
}

function breakdownClassLabel(row) {
  if (!row?.class_name) return '—'
  return `${row.class_name} (${classGender(row.area_name)})`
}

const classOptions = computed(() =>
  classesForPicker().map((c) => ({ value: String(c.id), label: classLabel(c) })),
)

const markSections = computed(() => sectionsForClass(classId.value))

const pendingSections = computed(() => sectionsForClass(pendingClassId.value))

const statusSections = computed(() => sectionsForClass(statusClassId.value))

const markSectionOptions = computed(() => toOptions(markSections.value))
const pendingSectionOptions = computed(() => toOptions(pendingSections.value))
const statusSectionOptions = computed(() => toOptions(statusSections.value))

const summarySections = computed(() => sectionsForClass(summaryClassId.value))

const summarySectionOptions = computed(() => toOptions(summarySections.value))

function statusLabel(status) {
  if (status === 'submitted') return 'Pending approval'
  if (status === 'verified') return 'Approved'
  if (status === 'draft') return 'Draft'
  return status
}

function batchClassSection(batch) {
  const schoolClass = batch.section?.school_class ?? batch.section?.schoolClass
  const cls = schoolClass ? classLabel(schoolClass) : null
  const sec = batch.section?.name
  if (cls && sec) return `${cls} · ${sec}`
  return cls || sec || '—'
}

function setDefaultTab() {
  const ids = tabs.value.map((t) => t.id)
  if (canApprove.value && ids.includes('pending')) activeTab.value = 'pending'
  else if (canMark.value && ids.includes('mark')) activeTab.value = 'mark'
  else if (ids.length) activeTab.value = ids[0]
}

onMounted(async () => {
  await loadAcademic()
  setDefaultTab()
  if (activeTab.value === 'pending') loadPending()
})

async function loadAcademic() {
  const [areaRes, classRes, secRes] = await Promise.all([
    api.get('/efsc/academic/areas').catch(() => ({ data: [] })),
    api.get('/efsc/academic/classes').catch(() => ({ data: [] })),
    api.get('/efsc/academic/sections').catch(() => ({ data: [] })),
  ])
  allAreas.value = areaRes.data?.data ?? areaRes.data ?? []
  allClasses.value = classRes.data?.data ?? classRes.data ?? []
  allSections.value = secRes.data?.data ?? secRes.data ?? []
  const sorted = classesForPicker()
  if (sorted.length) {
    classId.value = String(sorted[0].id)
    statusClassId.value = String(sorted[0].id)
    onMarkClassChange()
    onStatusClassChange()
  }
}

function onMarkClassChange() {
  const secs = markSections.value
  sectionId.value = secs.length ? String(secs[0].id) : ''
  markStudents.value = []
  currentBatch.value = null
  markMsg.value = ''
  markErr.value = ''
}

function onPendingClassChange() {
  pendingSectionId.value = ''
}

function onStatusClassChange() {
  const secs = statusSections.value
  statusSectionId.value = secs.length ? String(secs[0].id) : ''
  statusBatches.value = []
  statusLoaded.value = false
  expandedStatusId.value = null
  statusDetail.value = null
}

function onSummaryClassChange() {
  summarySectionId.value = ''
  maybeAutoLoadSummary()
}

function maybeAutoLoadSummary() {
  if (activeTab.value !== 'summary') return
  if (summarySectionId.value) return
  if (!summaryClassId.value) {
    summaryLoaded.value = false
    summaryMode.value = 'none'
    cumulativeBreakdown.value = []
    return
  }
  loadSummary()
}

watch(summarySectionId, () => {
  if (activeTab.value !== 'summary') return
  if (summarySectionId.value) {
    loadSummary()
  } else {
    maybeAutoLoadSummary()
  }
})

watch(activeTab, (tab) => {
  if (tab === 'summary') maybeAutoLoadSummary()
  if (tab === 'pending') loadPending()
})

watch(tabs, () => {
  if (!tabs.value.some((t) => t.id === activeTab.value)) setDefaultTab()
})

async function loadMarkStudents() {
  if (!sectionId.value) return
  loadingMark.value = true
  markErr.value = ''
  markMsg.value = ''
  try {
    const [studentsRes, batchRes] = await Promise.all([
      api.get('/efsc/students', { params: { section_id: sectionId.value } }),
      api.get('/efsc/attendance/batches', {
        params: { section_id: sectionId.value, date: markDate.value, per_page: 1 },
      }),
    ])
    markStudents.value = sortByRollNo(studentsRes.data?.data ?? studentsRes.data ?? [])
    for (const s of markStudents.value) {
      statuses[s.id] = 'present'
    }
    const batchItems = batchRes.data?.data ?? []
    const existing = batchItems[0] ?? null
    currentBatch.value = existing
    if (existing?.id) {
      const { data: detail } = await api.get(`/efsc/attendance/batches/${existing.id}`)
      for (const r of detail.records || []) {
        statuses[r.student_id] = r.status
      }
    }
  } catch (e) {
    markErr.value = e.response?.data?.message || 'Failed to load students'
    markStudents.value = []
  } finally {
    loadingMark.value = false
  }
}

async function saveDraft() {
  if (!sectionId.value || !markStudents.value.length) return
  saving.value = true
  markErr.value = ''
  markMsg.value = ''
  try {
    const { data } = await api.post('/efsc/attendance/batches', {
      section_id: Number(sectionId.value),
      date: markDate.value,
      records: markStudents.value.map((s) => ({ student_id: s.id, status: statuses[s.id] })),
    })
    currentBatch.value = data
    markMsg.value = 'Draft saved. Submit when ready for section head approval.'
  } catch (e) {
    markErr.value = e.response?.data?.message || 'Save failed'
  } finally {
    saving.value = false
  }
}

async function submitBatch() {
  if (!currentBatch.value?.id) return
  submitting.value = true
  markErr.value = ''
  try {
    const { data } = await api.post(`/efsc/attendance/batches/${currentBatch.value.id}/submit`)
    currentBatch.value = data
    markMsg.value = 'Submitted for approval. Track status under Attendance status.'
    markStudents.value = []
  } catch (e) {
    markErr.value = e.response?.data?.message || 'Submit failed'
  } finally {
    submitting.value = false
  }
}

async function saveAndSubmit() {
  if (!sectionId.value || !markStudents.value.length || markLocked.value) return
  saving.value = true
  markErr.value = ''
  markMsg.value = ''
  try {
    const { data } = await api.post('/efsc/attendance/batches', {
      section_id: Number(sectionId.value),
      date: markDate.value,
      records: markStudents.value.map((s) => ({ student_id: s.id, status: statuses[s.id] })),
    })
    currentBatch.value = data
    saving.value = false
    await submitBatch()
  } catch (e) {
    markErr.value = e.response?.data?.message || 'Submit failed'
    saving.value = false
  }
}

async function loadPending() {
  loadingPending.value = true
  pendingErr.value = ''
  expandedPendingId.value = null
  pendingDetail.value = null
  try {
    const params = { status: 'submitted', per_page: 100 }
    if (pendingSectionId.value) params.section_id = pendingSectionId.value
    else if (pendingClassId.value) params.school_class_id = pendingClassId.value
    const { data } = await api.get('/efsc/attendance/batches', { params })
    pendingBatches.value = data?.data ?? []
    pendingLoaded.value = true
  } catch (e) {
    pendingErr.value = e.response?.data?.message || 'Failed to load pending attendance'
    pendingBatches.value = []
  } finally {
    loadingPending.value = false
  }
}

async function togglePendingBatch(id) {
  if (expandedPendingId.value === id) {
    expandedPendingId.value = null
    pendingDetail.value = null
    return
  }
  expandedPendingId.value = id
  pendingDetailLoading.value = true
  try {
    const { data } = await api.get(`/efsc/attendance/batches/${id}`)
    pendingDetail.value = data
  } catch (e) {
    pendingErr.value = e.response?.data?.message || 'Failed to load batch'
    pendingDetail.value = null
  } finally {
    pendingDetailLoading.value = false
  }
}

async function approveBatch(id) {
  verifying.value = true
  pendingErr.value = ''
  try {
    await api.post(`/efsc/attendance/batches/${id}/verify`)
    pendingBatches.value = pendingBatches.value.filter((b) => b.id !== id)
    expandedPendingId.value = null
    pendingDetail.value = null
  } catch (e) {
    pendingErr.value = e.response?.data?.message || 'Approval failed'
  } finally {
    verifying.value = false
  }
}

async function loadStatusBatches() {
  if (!statusSectionId.value) return
  loadingStatus.value = true
  statusErr.value = ''
  expandedStatusId.value = null
  statusDetail.value = null
  try {
    const params = {
      section_id: statusSectionId.value,
      status_in: 'submitted,verified',
      per_page: 100,
    }
    if (canMark.value && !canViewSummary.value) params.own_only = 1
    const { data } = await api.get('/efsc/attendance/batches', { params })
    statusBatches.value = data?.data ?? []
    statusLoaded.value = true
  } catch (e) {
    statusErr.value = e.response?.data?.message || 'Failed to load attendance records'
    statusBatches.value = []
  } finally {
    loadingStatus.value = false
  }
}

async function toggleStatusBatch(id) {
  if (expandedStatusId.value === id) {
    expandedStatusId.value = null
    statusDetail.value = null
    return
  }
  expandedStatusId.value = id
  statusDetailLoading.value = true
  try {
    const { data } = await api.get(`/efsc/attendance/batches/${id}`)
    statusDetail.value = data
  } catch (e) {
    statusErr.value = e.response?.data?.message || 'Failed to load batch'
    statusDetail.value = null
  } finally {
    statusDetailLoading.value = false
  }
}

async function loadSummary() {
  if (!canLoadSummary.value) {
    summaryMode.value = 'none'
    summaryLoaded.value = true
    summaryRows.value = []
    cumulativeBreakdown.value = []
    return
  }
  loadingSummary.value = true
  summaryErr.value = ''
  try {
    const params = {}
    if (summaryClassId.value) params.school_class_id = summaryClassId.value
    if (summarySectionId.value) params.section_id = summarySectionId.value
    if (summaryFrom.value) params.from = summaryFrom.value
    if (summaryTo.value) params.to = summaryTo.value
    const { data } = await api.get('/efsc/attendance/summary', { params })
    summaryMode.value = data.mode
    if (data.mode === 'students') {
      summaryRows.value = data.students || []
      cumulativeBreakdown.value = []
    } else {
      cumulativeTotals.value = data.totals || { present: 0, absent: 0, leave: 0, students: 0, school_days: 0, sections: 0 }
      cumulativeBreakdown.value = data.by_section || []
      summaryRows.value = []
    }
    summaryLoaded.value = true
  } catch (e) {
    summaryErr.value = e.response?.data?.message || 'Failed to load summary'
    summaryMode.value = 'none'
    summaryRows.value = []
    cumulativeBreakdown.value = []
  } finally {
    loadingSummary.value = false
  }
}
</script>

<style scoped>
.attendance-page h2 {
  margin: 0 0 1rem;
  font-size: 1.05rem;
}

.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 1rem 0;
}
.tabs button {
  padding: 0.5rem 1rem;
  border: 1px solid #d4d4d8;
  background: #fff;
  cursor: pointer;
  border-radius: 6px;
  font-size: 0.9rem;
}
.tabs button.active {
  background: #2563eb;
  color: #fff;
  border-color: #2563eb;
}

.enroll-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 1rem;
  margin-bottom: 0.75rem;
}
.enroll-filters .picker-field {
  flex: 1;
  min-width: 160px;
  max-width: none;
  margin-bottom: 0;
}
.enroll-filters .picker-field :deep(.searchable-select) {
  margin: 0;
  max-width: none;
}
.enroll-filters .picker-field :deep(.trigger) {
  height: 2.375rem;
  box-sizing: border-box;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  min-width: 0;
}
.field-action {
  justify-content: flex-end;
}
.field-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #52525b;
  line-height: 1.2;
  min-height: 1rem;
}
.field input,
.field select {
  display: block;
  width: 100%;
  max-width: none;
  margin: 0;
  padding: 0 0.65rem;
  height: 2.375rem;
  box-sizing: border-box;
  border: 1px solid #d4d4d8;
  border-radius: 6px;
  font-size: 0.9rem;
  background: #fff;
}
.field input:focus,
.field select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.12);
}

.filter-actions .primary {
  margin: 0;
}
.summary-date-row {
  margin-bottom: 1rem;
}
.summary-date-row .field-action {
  flex: 0 0 auto;
  min-width: 140px;
}
.summary-date-row .field-action .primary {
  width: 100%;
  margin: 0;
  height: 2.375rem;
  box-sizing: border-box;
}
.summary-breakdown {
  margin-top: 0.5rem;
}
.summary-totals {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin: 1rem 0 0.75rem;
}
.total-card {
  flex: 1;
  min-width: 100px;
  padding: 0.85rem 1rem;
  background: #fafafa;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
  text-align: center;
}
.total-card.muted-card {
  background: #fff;
}
.total-value {
  display: block;
  font-size: 1.35rem;
  font-weight: 700;
  color: #18181b;
}
.total-label {
  display: block;
  margin-top: 0.2rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #71717a;
}

.student-list {
  margin-top: 1rem;
}
.mark-table th,
.mark-table td {
  vertical-align: middle;
}
.mark-table th {
  color: #71717a;
}
.mark-table td {
  color: #27272a;
}
.mark-table .col-serial {
  width: 2.5rem;
  text-align: center;
}
.mark-table td.col-serial {
  color: #71717a;
}
.mark-table .col-roll {
  width: 5rem;
  text-align: left;
  font-variant-numeric: tabular-nums;
}
.mark-table .col-student {
  min-width: 9rem;
  text-align: left;
}
.mark-table .col-father {
  min-width: 9rem;
  text-align: left;
}
.mark-table .col-mark-status {
  text-align: left;
  white-space: nowrap;
  min-width: 16rem;
}
.mark-table .cell-name {
  color: #18181b;
}
.status-radios {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: center;
  gap: 0.35rem;
  justify-content: flex-start;
}
.status-radio {
  position: relative;
  display: inline-flex;
  flex-shrink: 0;
  cursor: pointer;
  margin: 0;
}
.status-radio input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
  margin: 0;
}
.status-radio span {
  display: inline-block;
  padding: 0.3rem 0.6rem;
  border-radius: 999px;
  border: 1px solid #d4d4d8;
  background: #fff;
  font-size: 0.78rem;
  font-weight: 600;
  color: #52525b;
  white-space: nowrap;
  transition: background 0.12s, border-color 0.12s, color 0.12s;
}
.status-radio:hover span {
  border-color: #a1a1aa;
}
.status-radio.present.active span,
.status-radio.present input:focus-visible + span {
  background: #dcfce7;
  border-color: #86efac;
  color: #166534;
}
.status-radio.absent.active span,
.status-radio.absent input:focus-visible + span {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #991b1b;
}
.status-radio.leave.active span,
.status-radio.leave input:focus-visible + span {
  background: #fef3c7;
  border-color: #fcd34d;
  color: #92400e;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e4e4e7;
}
.modal-actions .primary,
.modal-actions .secondary {
  margin: 0;
}

.table-wrap {
  overflow-x: auto;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.data-table th,
.data-table td {
  text-align: left;
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #e4e4e7;
}
.data-table tbody tr:last-child td {
  border-bottom: none;
}
.data-table tbody tr:hover {
  background: #fafafa;
}
.data-table th {
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  color: #71717a;
  background: #fafafa;
}
.cell-name {
  font-weight: 600;
  color: #18181b;
}
.col-status {
  width: 1%;
  white-space: nowrap;
}
.roll {
  display: inline-block;
  min-width: 2rem;
  margin-right: 0.5rem;
  color: #71717a;
  font-size: 0.85rem;
  font-weight: 400;
}
.status-note {
  margin-top: 0.75rem;
  color: #52525b;
  font-size: 0.9rem;
}

.batch-list {
  margin-top: 1rem;
}
.batch-item {
  border: 1px solid #e4e4e7;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  overflow: hidden;
}
.batch-head {
  width: 100%;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem 0.75rem;
  padding: 0.75rem 1rem;
  background: #fafafa;
  border: none;
  cursor: pointer;
  text-align: left;
  font-size: 0.9rem;
}
.batch-meta {
  color: #52525b;
  font-size: 0.85rem;
}
.locked-note {
  margin-top: 1rem;
  padding: 0.65rem 0.85rem;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 6px;
  font-size: 0.9rem;
  color: #92400e;
}
.small {
  font-size: 0.85rem;
  margin: -0.5rem 0 1rem;
}
.batch-detail {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e4e4e7;
}
.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.35rem 0;
  border-bottom: 1px solid #f4f4f5;
  font-size: 0.9rem;
}
.status-badge {
  display: inline-block;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
  background: #f4f4f5;
}
.status-badge.draft { background: #fef3c7; color: #92400e; }
.status-badge.submitted { background: #dbeafe; color: #1e40af; }
.status-badge.verified { background: #dcfce7; color: #166534; }
.status-radio input:disabled + span {
  opacity: 0.55;
  cursor: not-allowed;
}
.pill {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  text-transform: capitalize;
}
.pill.present { background: #dcfce7; color: #166534; }
.pill.absent { background: #fee2e2; color: #991b1b; }
.pill.leave { background: #fef3c7; color: #92400e; }

.ok {
  color: #166534;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  font-size: 0.9rem;
  margin-top: 0.75rem;
}
.error {
  margin-top: 0.75rem;
}
.empty {
  margin: 0.5rem 0 0;
  padding: 1rem;
  text-align: center;
  color: #71717a;
  background: #fafafa;
  border: 1px dashed #d4d4d8;
  border-radius: 6px;
  font-size: 0.9rem;
}
.muted {
  color: #71717a;
  font-size: 0.9rem;
}
</style>
