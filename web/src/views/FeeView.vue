<template>
  <div>
    <h1>Fee vouchers</h1>
    <div class="card">
      <h2>Create voucher</h2>
      <label>Student
        <input
          v-model="studentQuery"
          list="student-options"
          placeholder="Search name or admission no…"
          @input="searchStudents"
          @change="resolveStudent"
          @blur="resolveStudent"
        />
        <datalist id="student-options">
          <option v-for="s in studentOptions" :key="s.id" :value="studentLabel(s)" />
        </datalist>
      </label>
      <p v-if="selectedStudent" class="muted">Selected: {{ studentLabel(selectedStudent) }} (ID {{ selectedStudent.id }})</p>
      <label>Title <input v-model="form.title" /></label>
      <label>File <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFile" /></label>
      <button type="button" class="primary" :disabled="saving || !selectedStudent" @click="create">
        {{ saving ? 'Creating…' : 'Create voucher' }}
      </button>
      <p v-if="msg" class="ok">{{ msg }}</p>
      <p v-if="err" class="error">{{ err }}</p>
    </div>
    <div class="card">
      <h2>Vouchers</h2>
      <div v-for="v in items" :key="v.id" class="item">
        <strong>{{ v.title }}</strong> — {{ v.student?.first_name }} {{ v.student?.last_name }}
        <span class="muted">{{ v.submission_status }}</span>
        <a v-if="v.file_path" :href="fileUrl(v.file_path)" target="_blank" rel="noopener" class="link">File</a>
        <select v-model="statusPick[v.id]" @change="updateStatus(v.id)">
          <option value="pending">pending</option>
          <option value="submitted">submitted</option>
          <option value="verified">verified</option>
        </select>
      </div>
      <p v-if="!items.length" class="muted">No vouchers yet.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../api/client'
import { paginated } from '../composables/format'

const form = reactive({ title: 'Term fee' })
const file = ref(null)
const items = ref([])
const statusPick = reactive({})
const msg = ref('')
const err = ref('')
const saving = ref(false)
const studentQuery = ref('')
const studentOptions = ref([])
const selectedStudent = ref(null)
let searchTimer = null

function studentLabel(s) {
  return `${s.first_name || ''} ${s.last_name || ''}`.trim() + (s.admission_no ? ` (${s.admission_no})` : '')
}

function fileUrl(path) {
  if (!path) return '#'
  if (path.startsWith('http')) return path
  return `/storage/${path.replace(/^\/+/, '')}`
}

function onFile(e) {
  file.value = e.target.files?.[0] || null
}

async function searchStudents() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    const q = studentQuery.value.trim()
    selectedStudent.value = null
    if (q.length < 2) {
      studentOptions.value = []
      return
    }
    try {
      const { data } = await api.get('/efsc/students/search', { params: { q } })
      studentOptions.value = Array.isArray(data) ? data : (data.data || [])
      resolveStudent()
    } catch {
      studentOptions.value = []
    }
  }, 250)
}

function resolveStudent() {
  const q = studentQuery.value.trim()
  const match = studentOptions.value.find((s) => studentLabel(s) === q)
  selectedStudent.value = match || null
}

async function load() {
  const { data } = await api.get('/efsc/fee-vouchers')
  items.value = paginated(data)
  for (const v of items.value) statusPick[v.id] = v.submission_status
}

async function create() {
  msg.value = ''
  err.value = ''
  if (!selectedStudent.value) {
    // Try resolve from exact label match
    const match = studentOptions.value.find((s) => studentLabel(s) === studentQuery.value.trim())
    if (match) selectedStudent.value = match
  }
  if (!selectedStudent.value) {
    err.value = 'Select a student from search results.'
    return
  }
  saving.value = true
  try {
    const body = new FormData()
    body.append('student_id', String(selectedStudent.value.id))
    body.append('title', form.title)
    if (file.value) body.append('file', file.value)
    await api.post('/efsc/fee-vouchers', body, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    msg.value = 'Voucher created; parent notify pending approval.'
    form.title = 'Term fee'
    file.value = null
    studentQuery.value = ''
    selectedStudent.value = null
    await load()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to create voucher'
  } finally {
    saving.value = false
  }
}

async function updateStatus(id) {
  try {
    await api.patch(`/efsc/fee-vouchers/${id}/status`, {
      submission_status: statusPick[id],
    })
    msg.value = 'Status updated.'
  } catch (e) {
    err.value = e.response?.data?.message || 'Status update failed'
  }
}

onMounted(load)
</script>

<style scoped>
.item { padding: 0.5rem 0; border-bottom: 1px solid #eee; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
.muted { color: #71717a; }
.ok { color: #15803d; }
.error { color: #b91c1c; }
.link { color: #0f766e; }
</style>
