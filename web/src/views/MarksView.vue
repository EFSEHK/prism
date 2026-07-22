<template>
  <div>
    <h1>Marks</h1>
    <p class="muted">Create assessments, enter marks, and verify submitted sheets.</p>

    <div v-if="tabs.length" class="tabs">
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
    <p v-else class="empty">You do not have marks permissions.</p>

    <div v-if="msg" class="ok">{{ msg }}</div>
    <div v-if="err" class="error">{{ err }}</div>

    <section v-if="activeTab === 'assessments'" class="card">
      <h2>Assessments</h2>
      <div class="form-row">
        <label>Name <input v-model="assessmentForm.name" /></label>
        <label>Type
          <select v-model="assessmentForm.type">
            <option value="test">Test</option>
            <option value="exam">Exam</option>
          </select>
        </label>
        <label>Number <input v-model.number="assessmentForm.number" type="number" min="1" /></label>
        <label>Held on <input v-model="assessmentForm.held_on" type="date" /></label>
        <button type="button" class="primary" :disabled="saving" @click="createAssessment">Create</button>
      </div>
      <p v-if="assessmentsLoading">Loading…</p>
      <div v-for="a in assessments" :key="a.id" class="item">
        <strong>{{ a.name }}</strong>
        <span class="muted"> · {{ a.type }}{{ a.number ? ` #${a.number}` : '' }}</span>
        <span v-if="a.held_on" class="muted"> · {{ formatDate(a.held_on) }}</span>
      </div>
    </section>

    <section v-if="activeTab === 'sheets'" class="card">
      <h2>Mark sheets</h2>
      <label>Study group
        <select v-model="academic.studyGroupId">
          <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
        </select>
      </label>
      <div class="form-row">
        <label>Assessment
          <select v-model="sheetForm.assessment_id">
            <option value="">—</option>
            <option v-for="a in assessments" :key="a.id" :value="a.id">{{ a.name }}</option>
          </select>
        </label>
        <label>Subject
          <select v-model="sheetForm.subject_id">
            <option value="">—</option>
            <option v-for="s in academic.subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </label>
        <button type="button" class="secondary" :disabled="saving" @click="openOrCreateSheet">Open / create sheet</button>
      </div>
      <p v-if="loading">Loading…</p>
      <div v-for="m in sheets" :key="m.id" class="item">
        <strong>{{ m.subject?.name }}</strong> — {{ m.assessment?.name }}
        <span class="badge" :data-status="m.status">{{ m.status }}</span>
        <span class="muted"> · {{ m.study_group?.name }}</span>
        <button type="button" class="secondary small" @click="openSheet(m)">Entries</button>
        <button
          v-if="m.status === 'verified'"
          type="button"
          class="primary small"
          @click="notify(m.id)"
        >
          Notify parents
        </button>
      </div>
    </section>

    <section v-if="activeTab === 'verify'" class="card">
      <h2>Pending verification</h2>
      <p v-if="verifyLoading">Loading…</p>
      <p v-else-if="!pendingSheets.length" class="empty">No submitted sheets awaiting verification.</p>
      <div v-for="m in pendingSheets" :key="m.id" class="item">
        <strong>{{ m.subject?.name }}</strong> — {{ m.assessment?.name }}
        <span class="muted"> · {{ m.study_group?.name }}</span>
        <button type="button" class="secondary small" @click="openSheet(m)">View entries</button>
        <button type="button" class="primary small" :disabled="busyId === m.id" @click="verify(m.id)">Verify</button>
      </div>
    </section>

    <div v-if="activeSheet" class="card">
      <h2>Entries — {{ activeSheet.subject?.name }} / {{ activeSheet.assessment?.name }}</h2>
      <div v-for="s in roster" :key="s.id" class="row-student">
        <span>{{ s.first_name }} {{ s.last_name }}</span>
        <input v-model="entries[s.id].marks" placeholder="Marks" style="width: 60px" />
        <input v-model="entries[s.id].max" placeholder="Max" style="width: 60px" />
        <input v-model="entries[s.id].grade" placeholder="Grade" style="width: 50px" />
      </div>
      <button
        v-if="canEnter && activeSheet.status !== 'verified'"
        type="button"
        class="primary"
        @click="saveEntries"
      >
        Save &amp; submit
      </button>
      <button type="button" class="secondary" @click="activeSheet = null">Close</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/client'
import { useAcademic } from '../composables/useAcademic'
import { usePermissions } from '../composables/usePermissions'
import { formatDate, paginated } from '../composables/format'

const route = useRoute()
const academic = useAcademic()
const { can } = usePermissions()

const canManageAssessments = computed(() => can('manage_assessments'))
const canEnter = computed(() => can('enter_marks'))
const canVerify = computed(() => can('verify_marks'))
const canView = computed(() => can('enter_marks') || can('view_marks_reports') || can('verify_marks'))

const tabs = computed(() => {
  const list = []
  if (canManageAssessments.value) list.push({ id: 'assessments', label: 'Assessments' })
  if (canEnter.value || canView.value) list.push({ id: 'sheets', label: 'Mark sheets' })
  if (canVerify.value) list.push({ id: 'verify', label: 'Verify' })
  return list
})

const activeTab = ref('sheets')
const assessments = ref([])
const sheets = ref([])
const pendingSheets = ref([])
const assessmentsLoading = ref(false)
const loading = ref(false)
const verifyLoading = ref(false)
const saving = ref(false)
const busyId = ref(null)
const activeSheet = ref(null)
const roster = ref([])
const entries = reactive({})
const msg = ref('')
const err = ref('')

const assessmentForm = reactive({ name: '', type: 'test', number: null, held_on: '' })
const sheetForm = reactive({ assessment_id: '', subject_id: '' })

async function loadAssessments() {
  assessmentsLoading.value = true
  try {
    const { data } = await api.get('/efsc/assessments', { params: { per_page: 50 } })
    assessments.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load assessments'
  } finally {
    assessmentsLoading.value = false
  }
}

async function createAssessment() {
  msg.value = ''
  err.value = ''
  if (!assessmentForm.name.trim()) {
    err.value = 'Assessment name is required.'
    return
  }
  saving.value = true
  try {
    await api.post('/efsc/assessments', {
      name: assessmentForm.name,
      type: assessmentForm.type,
      number: assessmentForm.number || null,
      held_on: assessmentForm.held_on || null,
    })
    msg.value = 'Assessment created.'
    assessmentForm.name = ''
    assessmentForm.number = null
    assessmentForm.held_on = ''
    await loadAssessments()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to create assessment'
  } finally {
    saving.value = false
  }
}

async function loadSheets() {
  loading.value = true
  try {
    const params = { per_page: 30 }
    if (academic.studyGroupId) params.study_group_id = academic.studyGroupId
    const { data } = await api.get('/efsc/mark-sheets', { params })
    sheets.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load sheets'
    sheets.value = []
  } finally {
    loading.value = false
  }
}

async function loadPending() {
  if (!canVerify.value) return
  verifyLoading.value = true
  try {
    const { data } = await api.get('/efsc/mark-sheets', {
      params: { status: 'submitted', per_page: 50 },
    })
    pendingSheets.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load pending sheets'
    pendingSheets.value = []
  } finally {
    verifyLoading.value = false
  }
}

async function openOrCreateSheet() {
  msg.value = ''
  err.value = ''
  if (!academic.studyGroupId || !sheetForm.assessment_id || !sheetForm.subject_id) {
    err.value = 'Study group, assessment, and subject are required.'
    return
  }
  saving.value = true
  try {
    const { data } = await api.post('/efsc/mark-sheets', {
      assessment_id: Number(sheetForm.assessment_id),
      study_group_id: Number(academic.studyGroupId),
      subject_id: Number(sheetForm.subject_id),
    })
    await loadSheets()
    await openSheet(data)
    msg.value = 'Sheet ready for entries.'
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to open sheet'
  } finally {
    saving.value = false
  }
}

async function openSheet(sheetOrId) {
  const id = typeof sheetOrId === 'object' ? sheetOrId.id : sheetOrId
  try {
    const { data } = await api.get(`/efsc/mark-sheets/${id}`)
    activeSheet.value = data
    const groupId = data.study_group_id || academic.studyGroupId
    const { data: st } = await api.get('/efsc/students', {
      params: { study_group_id: groupId },
    })
    roster.value = st.data || st
    for (const s of roster.value) {
      const existing = (data.entries || []).find((e) => e.student_id === s.id)
      entries[s.id] = {
        marks: existing?.marks_obtained ?? '',
        max: existing?.max_marks ?? '',
        grade: existing?.grade ?? '',
      }
    }
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to open sheet'
  }
}

async function saveEntries() {
  if (!activeSheet.value) return
  try {
    await api.post(`/efsc/mark-sheets/${activeSheet.value.id}/entries`, {
      entries: roster.value.map((s) => ({
        student_id: s.id,
        marks_obtained: entries[s.id].marks !== '' ? Number(entries[s.id].marks) : null,
        max_marks: entries[s.id].max !== '' ? Number(entries[s.id].max) : null,
        grade: entries[s.id].grade || null,
      })),
    })
    msg.value = 'Entries saved and submitted for verification.'
    activeSheet.value = null
    await loadSheets()
    await loadPending()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to save entries'
  }
}

async function verify(id) {
  busyId.value = id
  try {
    await api.post(`/efsc/mark-sheets/${id}/verify`)
    msg.value = 'Mark sheet verified.'
    await loadPending()
    await loadSheets()
  } catch (e) {
    err.value = e.response?.data?.message || 'Verify failed'
  } finally {
    busyId.value = null
  }
}

async function notify(id) {
  try {
    await api.post(`/efsc/mark-sheets/${id}/notify-parents`)
    msg.value = 'Parent notification queued for approval.'
  } catch (e) {
    err.value = e.response?.data?.message || 'Notify failed'
  }
}

watch(() => academic.studyGroupId, loadSheets)
watch(activeTab, (t) => {
  if (t === 'verify') loadPending()
  if (t === 'assessments') loadAssessments()
  if (t === 'sheets') loadSheets()
})

onMounted(async () => {
  await loadAssessments()
  await loadSheets()
  await loadPending()
  if (route.query.tab === 'verify' && canVerify.value) activeTab.value = 'verify'
  else if (tabs.value.length) activeTab.value = tabs.value[0].id
})
</script>

<style scoped>
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.tabs button { padding: 0.4rem 0.85rem; border: 1px solid #e4e4e7; background: #fff; border-radius: 6px; cursor: pointer; }
.tabs button.active { background: #0f766e; color: #fff; border-color: #0f766e; }
.form-row { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end; margin-bottom: 1rem; }
.item { padding: 0.5rem 0; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; border-bottom: 1px solid #f4f4f5; }
.small { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
.row-student { display: flex; gap: 0.5rem; align-items: center; padding: 0.25rem 0; }
.badge { font-size: 0.75rem; padding: 0.15rem 0.45rem; border-radius: 999px; background: #f4f4f5; }
.badge[data-status="draft"] { background: #e4e4e7; }
.badge[data-status="submitted"] { background: #fef3c7; color: #92400e; }
.badge[data-status="verified"] { background: #dcfce7; color: #166534; }
.ok { color: #15803d; }
.error { color: #b91c1c; }
.muted { color: #71717a; }
.empty { color: #a1a1aa; }
</style>
