<template>
  <div>
    <AdminBackNav />
    <h1>AIMS Import</h1>
    <p class="muted">Upload CSV files exported from AIMS for import into EFSC-YA. Each data type can be imported independently.</p>

    <div v-if="err" class="error">{{ err }}</div>

    <div class="import-grid">
      <div v-for="card in importCards" :key="card.type" class="card import-card">
        <h2>{{ card.title }}</h2>
        <p class="muted small">{{ card.description }}</p>
        <input
          :ref="(el) => setFileRef(card.type, el)"
          type="file"
          accept=".csv,text/csv"
          class="file-input"
          @change="onFileChange(card.type, $event)"
        />
        <button
          type="button"
          class="primary"
          :disabled="!files[card.type] || loading[card.type]"
          @click="runImport(card.type)"
        >
          {{ loading[card.type] ? 'Importing…' : 'Import' }}
        </button>
        <div v-if="results[card.type]" class="result-panel">
          <p>
            Processed: {{ results[card.type].processed }} ·
            Succeeded: {{ results[card.type].succeeded }} ·
            Skipped: {{ results[card.type].skipped }} ·
            Failed: {{ results[card.type].failed }}
          </p>
          <details v-if="results[card.type].errors?.length">
            <summary>{{ results[card.type].errors.length }} issue(s)</summary>
            <ul class="error-list">
              <li v-for="(e, i) in results[card.type].errors.slice(0, 50)" :key="i">{{ e }}</li>
            </ul>
          </details>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
      <h2>Recent imports</h2>
      <div v-if="logsLoading" class="muted">Loading…</div>
      <div v-else-if="!logs.length" class="muted">No imports yet.</div>
      <div v-else class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>When</th>
              <th>Type</th>
              <th>File</th>
              <th>User</th>
              <th>Result</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td>{{ formatDate(log.created_at) }}</td>
              <td>{{ log.data_type }}</td>
              <td>{{ log.filename }}</td>
              <td>{{ log.user?.name ?? '—' }}</td>
              <td>
                {{ log.stats?.succeeded ?? 0 }} ok /
                {{ log.stats?.skipped ?? 0 }} skip /
                {{ log.stats?.failed ?? 0 }} fail
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../../api/client'
import AdminBackNav from '../../components/AdminBackNav.vue'

const importCards = [
  { type: 'students', title: 'Students', description: 'Student roster CSV from AIMS (EFSC-YA export)', endpoint: 'students' },
  { type: 'attendance', title: 'Attendance', description: 'Daily attendance CSV from AIMS', endpoint: 'attendance' },
  { type: 'fee_vouchers', title: 'Fee vouchers', description: 'Fee voucher CSV from AIMS', endpoint: 'fee-vouchers' },
  { type: 'fee_deposits', title: 'Fee deposits', description: 'Fee deposit CSV from AIMS', endpoint: 'fee-deposits' },
  { type: 'test_results', title: 'Test results', description: 'student_tests_export_*.csv', endpoint: 'test-results' },
  { type: 'exam_results', title: 'Exam results', description: 'student_exams_export_*.csv', endpoint: 'exam-results' },
]

const files = reactive({})
const fileRefs = {}
const loading = reactive({})
const results = reactive({})
const logs = ref([])
const logsLoading = ref(false)
const err = ref('')

function setFileRef(type, el) {
  if (el) fileRefs[type] = el
}

function onFileChange(type, event) {
  files[type] = event.target.files?.[0] ?? null
}

async function runImport(type) {
  const card = importCards.find((c) => c.type === type)
  if (!card || !files[type]) return

  err.value = ''
  loading[type] = true
  results[type] = null

  const form = new FormData()
  form.append('file', files[type])

  try {
    const { data } = await api.post(`/efsc/import/aims/${card.endpoint}`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    results[type] = data.stats
    files[type] = null
    if (fileRefs[type]) fileRefs[type].value = ''
    await loadLogs()
  } catch (e) {
    err.value = e.response?.data?.message ?? e.message ?? 'Import failed'
  } finally {
    loading[type] = false
  }
}

async function loadLogs() {
  logsLoading.value = true
  try {
    const { data } = await api.get('/efsc/import/aims/logs', { params: { per_page: 20 } })
    logs.value = data.data ?? []
  } catch {
    logs.value = []
  } finally {
    logsLoading.value = false
  }
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

onMounted(loadLogs)
</script>

<style scoped>
.admin-back {
  margin-bottom: 1rem;
}
.import-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}
.import-card h2 {
  margin: 0 0 0.25rem;
  font-size: 1.1rem;
}
.file-input {
  display: block;
  margin: 0.75rem 0;
  width: 100%;
}
.result-panel {
  margin-top: 0.75rem;
  padding: 0.5rem 0.75rem;
  background: #f4f4f5;
  border-radius: 6px;
  font-size: 0.85rem;
}
.error-list {
  margin: 0.5rem 0 0;
  padding-left: 1.25rem;
  font-size: 0.8rem;
  color: #b91c1c;
}
.muted { color: #71717a; }
.small { font-size: 0.85rem; }
.error { color: #b91c1c; margin-bottom: 1rem; }
</style>
