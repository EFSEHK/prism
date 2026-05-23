<template>
  <div>
    <h1>Fee vouchers</h1>
    <div class="card">
      <h2>Upload voucher</h2>
      <label>Student ID <input v-model="form.student_id" type="number" placeholder="1" /></label>
      <label>Title <input v-model="form.title" /></label>
      <label>File path (URL or path) <input v-model="form.file_path" /></label>
      <button type="button" class="primary" @click="create">Create voucher</button>
      <p v-if="msg" class="ok">{{ msg }}</p>
    </div>
    <div class="card">
      <h2>Vouchers</h2>
      <div v-for="v in items" :key="v.id" class="item">
        <strong>{{ v.title }}</strong> — {{ v.student?.first_name }} {{ v.student?.last_name }}
        <span class="muted">{{ v.submission_status }}</span>
        <select v-model="statusPick[v.id]" @change="updateStatus(v.id)">
          <option value="pending">pending</option>
          <option value="submitted">submitted</option>
          <option value="verified">verified</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../api/client'
import { paginated } from '../composables/useAcademic'

const form = reactive({ student_id: '1', title: 'Term fee', file_path: '' })
const items = ref([])
const statusPick = reactive({})
const msg = ref('')

async function load() {
  const { data } = await api.get('/prism/fee-vouchers')
  items.value = paginated(data)
  for (const v of items.value) statusPick[v.id] = v.submission_status
}

async function create() {
  await api.post('/prism/fee-vouchers', {
    student_id: Number(form.student_id),
    title: form.title,
    file_path: form.file_path || null,
  })
  msg.value = 'Voucher created; parent notify pending approval.'
  await load()
}

async function updateStatus(id) {
  await api.patch(`/prism/fee-vouchers/${id}/status`, {
    submission_status: statusPick[id],
  })
  msg.value = 'Status updated.'
}

onMounted(load)
</script>

<style scoped>
.item { padding: 0.5rem 0; border-bottom: 1px solid #eee; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
.muted { color: #71717a; }
.ok { color: #15803d; }
</style>
