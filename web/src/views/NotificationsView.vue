<template>
  <div>
    <h1>Notifications</h1>
    <p class="muted">Create and manage school-wide or targeted announcements.</p>

    <div v-if="msg" class="ok">{{ msg }}</div>
    <div v-if="err" class="error">{{ err }}</div>

    <div class="card">
      <h2>Create broadcast</h2>
      <p class="muted small">General announcements require admin approval. Scoped and individual announcements require section head or admin approval.</p>

      <form class="broadcast-form" @submit.prevent="publish">
        <div class="field picker-field">
          <span class="field-label">Audience</span>
          <SearchableSelect
            v-model="form.audience_type"
            :options="audienceOptions"
            placeholder="Select audience…"
            search-placeholder="Search audience types…"
            :allow-empty="false"
            @change="onAudienceChange"
          />
        </div>

        <div v-if="form.audience_type === 'scoped'" class="cascade">
          <p class="muted small">Narrow the audience by area, class, section, or study group. Select at least one.</p>
          <div class="cascade-row cascade-row-2">
            <div class="field cascade-field">
              <span class="field-label">Area</span>
              <SearchableSelect
                v-model="form.area_id"
                :options="areaOptions"
                placeholder="All areas"
                search-placeholder="Search areas…"
                @change="onAreaChange"
              />
            </div>
            <div class="field cascade-field">
              <span class="field-label">Class</span>
              <SearchableSelect
                v-model="form.school_class_id"
                :options="classOptions"
                placeholder="All classes"
                search-placeholder="Search classes…"
                :disabled="!form.area_id"
                @change="onClassChange"
              />
            </div>
          </div>
          <div class="cascade-row cascade-row-2">
            <div class="field cascade-field">
              <span class="field-label">Section</span>
              <SearchableSelect
                v-model="form.section_id"
                :options="sectionOptions"
                placeholder="All sections"
                search-placeholder="Search sections…"
                :disabled="!form.school_class_id"
              />
            </div>
            <div class="field cascade-field">
              <span class="field-label">Study group</span>
              <SearchableSelect
                v-model="form.study_group_id"
                :options="studyGroupOptions"
                placeholder="All study groups"
                search-placeholder="Search study groups…"
              />
            </div>
          </div>
        </div>

        <div v-if="form.audience_type === 'individual'" class="cascade">
          <div class="field picker-field">
            <span class="field-label">Student</span>
            <SearchableSelect
              v-model="form.student_id"
              :options="studentOptions"
              placeholder="Search by name or father name…"
              search-placeholder="Type name or father name…"
              :allow-empty="false"
              remote
              :loading="studentSearchLoading"
              :min-search-length="2"
              empty-options-text="No students found"
              @search="searchStudents"
              @change="onStudentSelected"
            />
          </div>
          <label class="checkbox-field">
            <input v-model="form.visible_to_student" type="checkbox" />
            <span>Also visible to the student</span>
          </label>
        </div>

        <div class="field">
          <span class="field-label">Title</span>
          <input v-model="form.title" required placeholder="Announcement title" />
        </div>
        <div class="field">
          <span class="field-label">Message</span>
          <textarea v-model="form.body" rows="4" placeholder="Write your message…" />
        </div>

        <button type="submit" class="primary" :disabled="saving">
          {{ saving ? 'Submitting…' : 'Submit for publishing' }}
        </button>
      </form>
    </div>

    <div v-if="canApprove && pendingItems.length" class="card">
      <h2>Pending approval</h2>
      <p class="muted small">Broadcasts waiting for your review.</p>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Audience</th>
              <th>Author</th>
              <th>Created</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in pendingItems" :key="b.id">
              <td class="cell-name">{{ b.title }}</td>
              <td>{{ audienceLabel(b) }}</td>
              <td>{{ b.author?.name ?? '—' }}</td>
              <td class="cell-period">{{ formatDate(b.created_at) }}</td>
              <td class="col-actions">
                <button type="button" class="row-action" @click="approveBroadcast(b.id)">Approve</button>
                <button type="button" class="row-action danger" @click="rejectBroadcast(b.id)">Reject</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h2>Recent broadcasts</h2>
      <div v-if="loading" class="empty">Loading…</div>
      <div v-else-if="items.length" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Audience</th>
              <th>Status</th>
              <th>Author</th>
              <th>Published</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in items" :key="b.id">
              <td class="cell-name">{{ b.title }}</td>
              <td>{{ audienceLabel(b) }}</td>
              <td><span class="status-badge" :class="statusClass(b)">{{ statusLabel(b) }}</span></td>
              <td>{{ b.author?.name ?? '—' }}</td>
              <td class="cell-period">{{ b.published_at ? formatDate(b.published_at) : '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="empty">No broadcasts yet.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../api/client'
import SearchableSelect from '../components/SearchableSelect.vue'
import { useRoles } from '../composables/useRoles'

const { canApprove } = useRoles()

const items = ref([])
const pendingItems = ref([])
const loading = ref(true)
const saving = ref(false)
const err = ref('')
const msg = ref('')

const areas = ref([])
const classes = ref([])
const sections = ref([])
const studyGroups = ref([])
const studentResults = ref([])
const selectedStudent = ref(null)
const studentSearchLoading = ref(false)

const form = reactive({
  audience_type: 'general',
  area_id: '',
  school_class_id: '',
  section_id: '',
  study_group_id: '',
  student_id: '',
  visible_to_student: false,
  title: '',
  body: '',
})

const audienceOptions = [
  { value: 'general', label: 'General (all parents & students)' },
  { value: 'scoped', label: 'Scoped (area / class / section / study group)' },
  { value: 'individual', label: 'Individual student' },
]

const areaOptions = computed(() => areas.value.map((a) => ({ value: a.id, label: a.name })))
const classOptions = computed(() =>
  classes.value
    .filter((c) => !form.area_id || String(c.area_id) === String(form.area_id))
    .map((c) => ({ value: c.id, label: c.name }))
)
const sectionOptions = computed(() =>
  sections.value
    .filter((s) => !form.school_class_id || String(s.school_class_id) === String(form.school_class_id))
    .map((s) => ({ value: s.id, label: s.name }))
)
const studyGroupOptions = computed(() => studyGroups.value.map((g) => ({ value: g.id, label: g.name })))

const studentOptions = computed(() => {
  const opts = studentResults.value.map((st) => ({
    value: st.id,
    label: studentDisplayName(st),
  }))
  if (selectedStudent.value && !opts.some((o) => String(o.value) === String(selectedStudent.value.id))) {
    opts.unshift({
      value: selectedStudent.value.id,
      label: studentDisplayName(selectedStudent.value),
    })
  }
  return opts
})

function studentDisplayName(st) {
  const name = [st.first_name, st.last_name].filter(Boolean).join(' ')
  const father = st.father_name?.trim()
  return father ? `${name} (${father})` : name
}

function onAudienceChange() {
  form.area_id = ''
  form.school_class_id = ''
  form.section_id = ''
  form.study_group_id = ''
  form.student_id = ''
  form.visible_to_student = false
  studentResults.value = []
  selectedStudent.value = null
}

function onStudentSelected(id) {
  selectedStudent.value = studentResults.value.find((s) => String(s.id) === String(id)) ?? selectedStudent.value
}

function onAreaChange() {
  form.school_class_id = ''
  form.section_id = ''
}

function onClassChange() {
  form.section_id = ''
}

async function searchStudents(q) {
  if (q.length < 2) {
    studentResults.value = selectedStudent.value ? [selectedStudent.value] : []
    return
  }
  studentSearchLoading.value = true
  try {
    const { data } = await api.get('/efsc/students/search', { params: { q } })
    studentResults.value = data ?? []
  } catch {
    studentResults.value = selectedStudent.value ? [selectedStudent.value] : []
  } finally {
    studentSearchLoading.value = false
  }
}

function audienceLabel(b) {
  if (b.audience_type === 'general') return 'General'
  if (b.audience_type === 'individual') {
    const st = b.student
    if (st) return `Individual: ${studentDisplayName(st)}`
    return 'Individual'
  }
  const parts = [
    b.area?.name,
    b.school_class?.name || b.schoolClass?.name,
    b.section?.name,
    b.study_group?.name || b.studyGroup?.name,
  ].filter(Boolean)
  return parts.length ? parts.join(' / ') : 'Scoped'
}

function statusLabel(b) {
  if (b.approval_status === 'pending_approval') return 'Pending approval'
  if (b.approval_status === 'rejected') return 'Rejected'
  return 'Published'
}

function statusClass(b) {
  if (b.approval_status === 'pending_approval') return 'pending'
  if (b.approval_status === 'rejected') return 'rejected'
  return 'published'
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString()
}

async function loadAreas() {
  const { data } = await api.get('/efsc/academic/areas')
  areas.value = data ?? []
}

async function loadClasses() {
  const { data } = await api.get('/efsc/academic/classes')
  classes.value = data ?? []
}

async function loadSections() {
  const { data } = await api.get('/efsc/academic/sections')
  sections.value = data ?? []
}

async function loadStudyGroups() {
  const { data } = await api.get('/efsc/academic/study-groups')
  studyGroups.value = data ?? []
}

async function load() {
  loading.value = true
  err.value = ''
  try {
    const { data } = await api.get('/efsc/broadcasts')
    items.value = data?.data ?? data ?? []
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load broadcasts'
  } finally {
    loading.value = false
  }
}

async function loadPending() {
  if (!canApprove.value) return
  try {
    const { data } = await api.get('/efsc/broadcasts/pending')
    pendingItems.value = data ?? []
  } catch {
    pendingItems.value = []
  }
}

async function publish() {
  err.value = ''
  msg.value = ''
  saving.value = true
  try {
    const payload = {
      audience_type: form.audience_type,
      title: form.title,
      body: form.body,
      publish: true,
      visible_to_student: form.visible_to_student,
      area_id: form.area_id ? Number(form.area_id) : null,
      school_class_id: form.school_class_id ? Number(form.school_class_id) : null,
      section_id: form.section_id ? Number(form.section_id) : null,
      study_group_id: form.study_group_id ? Number(form.study_group_id) : null,
      student_id: form.student_id ? Number(form.student_id) : null,
    }
    const { data } = await api.post('/efsc/broadcasts', payload)
    if (data.approval_status === 'pending_approval') {
      msg.value = 'Broadcast submitted and is awaiting approval.'
    } else {
      msg.value = 'Broadcast published successfully.'
    }
    form.title = ''
    form.body = ''
    form.student_id = ''
    form.visible_to_student = false
    studentResults.value = []
    selectedStudent.value = null
    await Promise.all([load(), loadPending()])
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      err.value = Object.values(errors).flat().join(' ')
    } else {
      err.value = e.response?.data?.message || 'Failed to submit broadcast'
    }
  } finally {
    saving.value = false
  }
}

async function approveBroadcast(id) {
  err.value = ''
  try {
    await api.post(`/efsc/broadcasts/${id}/approve`)
    msg.value = 'Broadcast approved and published.'
    await Promise.all([load(), loadPending()])
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to approve'
  }
}

async function rejectBroadcast(id) {
  err.value = ''
  try {
    await api.post(`/efsc/broadcasts/${id}/reject`, {})
    msg.value = 'Broadcast rejected.'
    await Promise.all([load(), loadPending()])
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to reject'
  }
}

onMounted(async () => {
  await Promise.all([loadAreas(), loadClasses(), loadSections(), loadStudyGroups(), load(), loadPending()])
})
</script>

<style scoped>
.muted {
  color: #71717a;
}
.small {
  font-size: 0.85rem;
  margin-top: -0.25rem;
  margin-bottom: 1rem;
}
.ok {
  color: #166534;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}
.error {
  margin-bottom: 1rem;
}
.broadcast-form {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  min-width: 0;
  margin-bottom: 0.75rem;
}
.field-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #52525b;
  line-height: 1.2;
}
.field input,
.field select,
.field textarea {
  display: block;
  width: 100%;
  max-width: none;
  margin: 0;
  padding: 0 0.65rem;
  box-sizing: border-box;
  border: 1px solid #d4d4d8;
  border-radius: 6px;
  font-size: 0.9rem;
  background: #fff;
  font-family: inherit;
}
.field input,
.field select {
  height: 2.375rem;
}
.field textarea {
  padding: 0.65rem;
  resize: vertical;
  min-height: 5rem;
}
.field input:focus,
.field select:focus,
.field textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.12);
}
.checkbox-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #3f3f46;
  cursor: pointer;
  margin: 0 0 0.75rem;
}
.checkbox-field input[type='checkbox'] {
  width: 1rem;
  height: 1rem;
  margin: 0;
  flex-shrink: 0;
}
.picker-field {
  max-width: 420px;
}
.picker-field :deep(.searchable-select) {
  margin: 0;
}
.cascade {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
  padding: 1rem;
  background: #fafafa;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
}
.cascade-row {
  display: grid;
  gap: 0.75rem 1rem;
}
.cascade-row-2 {
  grid-template-columns: 1fr 1fr;
}
.cascade-field :deep(.searchable-select) {
  margin: 0;
  max-width: none;
}
.cascade-field :deep(.trigger) {
  height: 2.375rem;
  box-sizing: border-box;
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
.cell-period {
  color: #3f3f46;
  white-space: nowrap;
}
.col-actions {
  width: 1%;
  white-space: nowrap;
  text-align: right;
}
.row-action {
  background: none;
  border: none;
  padding: 0.15rem 0.4rem;
  color: #2563eb;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}
.row-action:hover {
  text-decoration: underline;
}
.row-action.danger {
  color: #dc2626;
}
.row-action + .row-action {
  margin-left: 0.35rem;
}
.status-badge {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}
.status-badge.published {
  background: #f0fdf4;
  color: #166534;
}
.status-badge.pending {
  background: #fef9c3;
  color: #854d0e;
}
.status-badge.rejected {
  background: #fef2f2;
  color: #991b1b;
}
.primary {
  align-self: flex-start;
  margin-top: 0.25rem;
}
@media (max-width: 640px) {
  .cascade-row-2 {
    grid-template-columns: 1fr;
  }
}
</style>
