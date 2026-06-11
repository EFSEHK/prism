<template>
  <div>
    <h1>Notification approvals</h1>
    <p class="muted">Review automated dispatches and staff broadcast announcements.</p>

    <div v-if="err" class="error">{{ err }}</div>

    <div class="card">
      <h2>Broadcast announcements</h2>
      <p v-if="loadingBroadcasts" class="empty">Loading…</p>
      <p v-else-if="!broadcasts.length" class="empty">No broadcast announcements awaiting approval.</p>
      <div v-else class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Audience</th>
              <th>Author</th>
              <th>Message</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in broadcasts" :key="`b-${b.id}`">
              <td class="cell-name">{{ b.title }}</td>
              <td>{{ audienceLabel(b) }}</td>
              <td>{{ b.author?.name ?? '—' }}</td>
              <td class="cell-body">{{ b.body || '—' }}</td>
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
      <h2>System dispatches</h2>
      <p v-if="loading" class="empty">Loading…</p>
      <p v-else-if="!dispatches.length" class="empty">No system dispatches awaiting approval.</p>
      <div v-else>
        <div v-for="d in dispatches" :key="`d-${d.id}`" class="dispatch-item">
          <p class="dispatch-head">
            <strong>{{ d.feature?.name }}</strong>
            <span class="muted">— {{ d.status }}</span>
            <span v-if="d.school_class?.name || d.section?.name" class="muted">
              · {{ d.school_class?.name }} / {{ d.section?.name }}
            </span>
          </p>
          <pre class="payload">{{ JSON.stringify(d.payload_json, null, 2) }}</pre>
          <div class="dispatch-actions">
            <button type="button" class="primary" @click="approveDispatch(d.id)">Approve</button>
            <button type="button" class="secondary" @click="rejectDispatch(d.id)">Reject</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../api/client'

const dispatches = ref([])
const broadcasts = ref([])
const loading = ref(true)
const loadingBroadcasts = ref(true)
const err = ref('')

function studentDisplayName(st) {
  const name = [st.first_name, st.last_name].filter(Boolean).join(' ')
  const father = st.father_name?.trim()
  return father ? `${name} (${father})` : name
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

async function loadDispatches() {
  loading.value = true
  try {
    const { data } = await api.get('/efsc/notification-dispatches/pending')
    dispatches.value = data.data || data || []
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load dispatches'
  } finally {
    loading.value = false
  }
}

async function loadBroadcasts() {
  loadingBroadcasts.value = true
  try {
    const { data } = await api.get('/efsc/broadcasts/pending')
    broadcasts.value = data ?? []
  } catch (e) {
    if (!err.value) {
      err.value = e.response?.data?.message || 'Failed to load broadcasts'
    }
  } finally {
    loadingBroadcasts.value = false
  }
}

async function approveDispatch(id) {
  await api.post(`/efsc/notification-dispatches/${id}/approve`, {})
  await loadDispatches()
}

async function rejectDispatch(id) {
  await api.post(`/efsc/notification-dispatches/${id}/reject`, {})
  await loadDispatches()
}

async function approveBroadcast(id) {
  await api.post(`/efsc/broadcasts/${id}/approve`)
  await loadBroadcasts()
}

async function rejectBroadcast(id) {
  await api.post(`/efsc/broadcasts/${id}/reject`, {})
  await loadBroadcasts()
}

onMounted(async () => {
  await Promise.all([loadDispatches(), loadBroadcasts()])
})
</script>

<style scoped>
.muted {
  color: #71717a;
}
.error {
  margin-bottom: 1rem;
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
  vertical-align: top;
}
.data-table tbody tr:last-child td {
  border-bottom: none;
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
.cell-body {
  max-width: 240px;
  color: #3f3f46;
  font-size: 0.85rem;
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
.dispatch-item {
  padding: 1rem 0;
  border-bottom: 1px solid #e4e4e7;
}
.dispatch-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
.dispatch-head {
  margin: 0 0 0.5rem;
  font-size: 0.95rem;
}
.payload {
  font-size: 0.75rem;
  background: #f4f4f5;
  padding: 0.5rem;
  overflow: auto;
  max-height: 120px;
  border-radius: 6px;
  margin: 0 0 0.75rem;
}
.dispatch-actions {
  display: flex;
  gap: 0.5rem;
}
.secondary {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: 1px solid #d4d4d8;
  background: #fff;
  cursor: pointer;
}
</style>
