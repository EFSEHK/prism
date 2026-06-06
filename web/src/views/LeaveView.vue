<template>
  <div>
    <h1>Leave requests</h1>
    <div class="card">
      <label>Status filter
        <select v-model="status" @change="load">
          <option value="">all</option>
          <option value="pending">pending</option>
          <option value="approved">approved</option>
          <option value="rejected">rejected</option>
        </select>
      </label>
      <div v-for="l in items" :key="l.id" class="item">
        <strong>{{ l.student?.first_name }} {{ l.student?.last_name }}</strong>
        {{ l.start_date }} – {{ l.end_date }} — <em>{{ l.status }}</em>
        <template v-if="l.status === 'pending'">
          <button type="button" class="primary small" @click="decide(l.id, 'approved')">Approve</button>
          <button type="button" class="secondary small" @click="decide(l.id, 'rejected')">Reject</button>
        </template>
      </div>
    </div>
    <p v-if="msg" class="ok">{{ msg }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/client'
import { paginated } from '../composables/useAcademic'

const items = ref([])
const status = ref('pending')
const msg = ref('')

async function load() {
  const { data } = await api.get('/efsc/leave-requests', {
    params: status.value ? { status: status.value } : {},
  })
  items.value = paginated(data)
}

async function decide(id, s) {
  await api.post(`/efsc/leave-requests/${id}/decide`, { status: s })
  msg.value = `Leave ${s}. Parent notified.`
  await load()
}

onMounted(load)
</script>

<style scoped>
.item { padding: 0.5rem 0; border-bottom: 1px solid #eee; display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
.small { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
.ok { color: #15803d; }
</style>
