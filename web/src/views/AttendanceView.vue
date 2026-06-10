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
          <table class="data-table">
            <thead>
              <tr>
                <th>Student</th>
                <th class="col-status">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in markStudents" :key="s.id">
                <td class="cell-name">
                  <span v-if="s.roll_no" class="roll">{{ s.roll_no }}</span>
                  {{ s.first_name }} {{ s.last_name }}
                </td>
                <td class="col-status">
                  <select v-model="statuses[s.id]" class="status-select">
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="leave">Leave</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-actions">
          <button type="button" class="primary" :disabled="saving" @click="saveDraft">
            {{ saving ? 'Saving…' : 'Save draft' }}
          </button>
          <button
            v-if="currentBatch?.status === 'draft'"
            type="button"
            class="secondary"
            :disabled="submitting"
            @click="submitBatch"
          >
            {{ submitting ? 'Submitting…' : 'Submit for verification' }}
          </button>
        </div>
        <p v-if="currentBatch" class="status-note">
          Status: <strong>{{ currentBatch.status }}</strong>
        </p>
      </div>
      <p v-if="markMsg" class="ok">{{ markMsg }}</p>
      <p v-if="markErr" class="error">{{ markErr }}</p>
    </section>

    <!-- View Attendance -->
    <section v-if="activeTab === 'view'" class="card">
      <h2>View Attendance</h2>
      <div class="enroll-filters">
        <div class="field picker-field">
          <span class="field-label">Class</span>
          <SearchableSelect
            v-model="viewClassId"
            :options="classOptions"
            placeholder="Select class…"
            search-placeholder="Search classes…"
            :allow-empty="false"
            @change="onViewClassChange"
          />
        </div>
        <div class="field picker-field">
          <span class="field-label">Section</span>
          <SearchableSelect
            v-model="viewSectionId"
            :options="viewSectionOptions"
            placeholder="Select section…"
            search-placeholder="Search sections…"
            :disabled="!viewClassId"
            :allow-empty="false"
          />
        </div>
      </div>
      <div class="filter-actions">
        <button type="button" class="primary" :disabled="!viewSectionId || loadingBatches" @click="loadBatches">
          {{ loadingBatches ? 'Loading…' : 'Load dates' }}
        </button>
      </div>

      <p v-if="viewErr" class="error">{{ viewErr }}</p>
      <p v-else-if="batchesLoaded && !batches.length" class="empty">No attendance records for this section.</p>

      <div v-if="batches.length" class="batch-list">
        <div v-for="b in batches" :key="b.id" class="batch-item">
          <button type="button" class="batch-head" @click="toggleBatch(b.id)">
            <span>{{ formatDate(b.date) }}</span>
            <span class="status-badge" :class="b.status">{{ b.status }}</span>
            <span class="muted">{{ b.records_count }} students</span>
          </button>
          <div v-if="expandedBatchId === b.id" class="batch-detail">
            <p v-if="batchDetailLoading" class="muted">Loading…</p>
            <template v-else-if="batchDetail">
              <div v-for="r in batchDetail.records" :key="r.id" class="detail-row">
                <span>{{ r.student?.first_name }} {{ r.student?.last_name }}</span>
                <span :class="['pill', r.status]">{{ r.status }}</span>
              </div>
              <div v-if="canVerify && ['draft', 'submitted'].includes(batchDetail.status)" class="modal-actions">
                <button type="button" class="primary" :disabled="verifying" @click="verifyBatch(batchDetail.id)">
                  {{ verifying ? 'Approving…' : 'Approve attendance' }}
                </button>
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
          <span class="field-label">Area</span>
          <SearchableSelect
            v-model="summaryAreaId"
            :options="areaOptions"
            placeholder="All areas"
            search-placeholder="Search areas…"
            @change="onSummaryAreaChange"
          />
        </div>
        <div class="field picker-field">
          <span class="field-label">Class</span>
          <SearchableSelect
            v-model="summaryClassId"
            :options="summaryClassOptions"
            placeholder="All classes"
            search-placeholder="Search classes…"
            @change="onSummaryClassChange"
          />
        </div>
        <div class="field picker-field">
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
        Select an area or class for a cumulative summary, or select a section for per-student totals.
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
        <p v-if="!cumulativeBreakdown.length" class="empty">No sections found for the selected area or class.</p>
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
                <td>{{ row.class_name }}</td>
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
import { formatDate, todayInputDate, monthStartInputDate } from '../composables/format'

const { can } = usePermissions()
const canVerify = computed(() => can('verify_attendance'))

const tabs = [
  { id: 'mark', label: 'Mark Attendance' },
  { id: 'view', label: 'View Attendance' },
  { id: 'summary', label: 'Attendance Summary' },
]
const activeTab = ref('mark')

const areas = ref([])
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

const viewClassId = ref('')
const viewSectionId = ref('')
const batches = ref([])
const batchesLoaded = ref(false)
const loadingBatches = ref(false)
const viewErr = ref('')
const expandedBatchId = ref(null)
const batchDetail = ref(null)
const batchDetailLoading = ref(false)
const verifying = ref(false)

const summaryAreaId = ref('')
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
  () => Boolean(summarySectionId.value || summaryAreaId.value || summaryClassId.value),
)

function toOptions(items) {
  return items.map((i) => ({ value: String(i.id), label: i.name }))
}

function classLabel(c) {
  const area = c.area?.name
  return area ? `${c.name} (${area})` : c.name
}

const classOptions = computed(() =>
  allClasses.value.map((c) => ({ value: String(c.id), label: classLabel(c) }))
)

const areaOptions = computed(() => toOptions(areas.value))

const markSections = computed(() =>
  classId.value
    ? allSections.value.filter((s) => String(s.school_class_id) === classId.value)
    : []
)

const viewSections = computed(() =>
  viewClassId.value
    ? allSections.value.filter((s) => String(s.school_class_id) === viewClassId.value)
    : []
)

const markSectionOptions = computed(() => toOptions(markSections.value))
const viewSectionOptions = computed(() => toOptions(viewSections.value))

const summaryClasses = computed(() =>
  summaryAreaId.value
    ? allClasses.value.filter((c) => String(c.area_id) === summaryAreaId.value)
    : allClasses.value
)

const summarySections = computed(() => {
  if (summaryClassId.value) {
    return allSections.value.filter((s) => String(s.school_class_id) === summaryClassId.value)
  }
  if (summaryAreaId.value) {
    const classIds = new Set(summaryClasses.value.map((c) => c.id))
    return allSections.value.filter((s) => classIds.has(s.school_class_id))
  }
  return allSections.value
})

const summaryClassOptions = computed(() =>
  summaryClasses.value.map((c) => ({ value: String(c.id), label: classLabel(c) }))
)
const summarySectionOptions = computed(() => toOptions(summarySections.value))

onMounted(loadAcademic)

async function loadAcademic() {
  const [areaRes, classRes, secRes] = await Promise.all([
    api.get('/efsc/academic/areas').catch(() => ({ data: [] })),
    api.get('/efsc/academic/classes').catch(() => ({ data: [] })),
    api.get('/efsc/academic/sections').catch(() => ({ data: [] })),
  ])
  areas.value = areaRes.data?.data ?? areaRes.data ?? []
  allClasses.value = classRes.data?.data ?? classRes.data ?? []
  allSections.value = secRes.data?.data ?? secRes.data ?? []
  if (allClasses.value.length) {
    classId.value = String(allClasses.value[0].id)
    viewClassId.value = String(allClasses.value[0].id)
    onMarkClassChange()
    onViewClassChange()
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

function onViewClassChange() {
  const secs = viewSections.value
  viewSectionId.value = secs.length ? String(secs[0].id) : ''
  batches.value = []
  batchesLoaded.value = false
  expandedBatchId.value = null
  batchDetail.value = null
}

function onSummaryAreaChange() {
  summaryClassId.value = ''
  summarySectionId.value = ''
  maybeAutoLoadSummary()
}

function onSummaryClassChange() {
  summarySectionId.value = ''
  maybeAutoLoadSummary()
}

function maybeAutoLoadSummary() {
  if (activeTab.value !== 'summary') return
  if (summarySectionId.value) return
  if (!summaryAreaId.value && !summaryClassId.value) {
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
    markStudents.value = studentsRes.data?.data ?? studentsRes.data ?? []
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
    markMsg.value = 'Draft saved. Section head will verify and approve.'
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
    markMsg.value = 'Submitted for section head verification.'
  } catch (e) {
    markErr.value = e.response?.data?.message || 'Submit failed'
  } finally {
    submitting.value = false
  }
}

async function loadBatches() {
  if (!viewSectionId.value) return
  loadingBatches.value = true
  viewErr.value = ''
  expandedBatchId.value = null
  batchDetail.value = null
  try {
    const { data } = await api.get('/efsc/attendance/batches', {
      params: { section_id: viewSectionId.value, per_page: 100 },
    })
    batches.value = data?.data ?? []
    batchesLoaded.value = true
  } catch (e) {
    viewErr.value = e.response?.data?.message || 'Failed to load attendance dates'
    batches.value = []
  } finally {
    loadingBatches.value = false
  }
}

async function toggleBatch(id) {
  if (expandedBatchId.value === id) {
    expandedBatchId.value = null
    batchDetail.value = null
    return
  }
  expandedBatchId.value = id
  batchDetailLoading.value = true
  try {
    const { data } = await api.get(`/efsc/attendance/batches/${id}`)
    batchDetail.value = data
  } catch (e) {
    viewErr.value = e.response?.data?.message || 'Failed to load batch'
    batchDetail.value = null
  } finally {
    batchDetailLoading.value = false
  }
}

async function verifyBatch(id) {
  verifying.value = true
  viewErr.value = ''
  try {
    const { data } = await api.post(`/efsc/attendance/batches/${id}/verify`)
    batchDetail.value = data
    const idx = batches.value.findIndex((b) => b.id === id)
    if (idx >= 0) batches.value[idx] = { ...batches.value[idx], status: 'verified' }
  } catch (e) {
    viewErr.value = e.response?.data?.message || 'Verification failed'
  } finally {
    verifying.value = false
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
    if (summaryAreaId.value) params.area_id = summaryAreaId.value
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
.status-select {
  width: auto;
  min-width: 7rem;
  height: 2rem;
  margin: 0;
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
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #fafafa;
  border: none;
  cursor: pointer;
  text-align: left;
  font-size: 0.9rem;
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
