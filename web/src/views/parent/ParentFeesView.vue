<template>
  <div>
    <h1>Fee vouchers</h1>
    <p v-if="child" class="muted">For {{ childName(child) }}</p>
    <div class="card">
      <p v-if="loading">Loading…</p>
      <p v-else-if="err" class="error">{{ err }}</p>
      <p v-else-if="items.length === 0" class="muted">No fee vouchers.</p>
      <div v-for="v in items" :key="v.id" class="item">
        <strong>{{ v.title }}</strong>
        <span class="muted"> · {{ childName(v.student) }}</span>
        <p class="muted small">Status: {{ v.submission_status }}</p>
        <a v-if="v.file_path" :href="fileUrl(v.file_path)" target="_blank" rel="noopener" class="link">View file</a>
        <button
          v-if="v.submission_status === 'pending'"
          type="button"
          class="primary small"
          :disabled="busyId === v.id"
          @click="markSubmitted(v)"
        >
          Mark as submitted
        </button>
      </div>
      <p v-if="msg" class="ok">{{ msg }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import api from '../../api/client'
import { useParentStore } from '../../stores/parent'
import { useParentList } from '../../composables/useParentList'
import { childName } from '../../composables/format'

const parent = useParentStore()
const child = computed(() => parent.selectedChild)
const { items, loading, err, load } = useParentList('/efsc/fee-vouchers')
const busyId = ref(null)
const msg = ref('')

function fileUrl(path) {
  if (!path) return '#'
  if (path.startsWith('http')) return path
  return `/storage/${path.replace(/^\/+/, '')}`
}

async function markSubmitted(v) {
  busyId.value = v.id
  msg.value = ''
  try {
    await api.patch(`/efsc/fee-vouchers/${v.id}/status`, { submission_status: 'submitted' })
    msg.value = 'Marked as submitted.'
    await load()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to update'
  } finally {
    busyId.value = null
  }
}
</script>

<style scoped>
.item { padding: 0.65rem 0; border-bottom: 1px solid #f4f4f5; display: flex; flex-direction: column; gap: 0.25rem; align-items: flex-start; }
.muted { color: #71717a; }
.small { font-size: 0.8rem; padding: 0.25rem 0.5rem; }
.link { color: #0f766e; }
.ok { color: #15803d; }
.error { color: #b91c1c; }
</style>
