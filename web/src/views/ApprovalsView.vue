<template>
  <div>
    <h1>Notification approvals</h1>
    <p v-if="loading">Loading…</p>
    <p v-else-if="err" class="error">{{ err }}</p>
    <div v-for="d in items" :key="d.id" class="card">
      <p>
        <strong>{{ d.feature?.name }}</strong> — {{ d.status }} ·
        {{ d.school_class?.name }} / {{ d.section?.name }}
      </p>
      <pre class="payload">{{ JSON.stringify(d.payload_json, null, 2) }}</pre>
      <button type="button" class="primary" @click="approve(d.id)">Approve</button>
      <button type="button" class="secondary" @click="reject(d.id)">Reject</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/client'

const items = ref([])
const loading = ref(true)
const err = ref('')

async function load() {
  loading.value = true
  err.value = ''
  try {
    const { data } = await api.get('/prism/notification-dispatches/pending')
    items.value = data.data || []
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load'
  } finally {
    loading.value = false
  }
}

async function approve(id) {
  await api.post(`/prism/notification-dispatches/${id}/approve`, {})
  await load()
}

async function reject(id) {
  await api.post(`/prism/notification-dispatches/${id}/reject`, {})
  await load()
}

onMounted(load)
</script>

<style scoped>
.payload {
  font-size: 0.75rem;
  background: #f4f4f5;
  padding: 0.5rem;
  overflow: auto;
  max-height: 120px;
}
.secondary {
  margin-left: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: 1px solid #d4d4d8;
  background: #fff;
  cursor: pointer;
}
</style>
