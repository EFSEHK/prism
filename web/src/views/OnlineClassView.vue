<template>
  <div>
    <h1>Online classes</h1>
    <p class="muted">Create meeting links for approval, or review pending submissions.</p>

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
    <p v-else class="empty">You do not have online class permissions.</p>

    <div v-if="msg" class="ok">{{ msg }}</div>
    <div v-if="err" class="error">{{ err }}</div>

    <section v-if="activeTab === 'create'" class="card">
      <h2>New link</h2>
      <label>Study group
        <select v-model="academic.studyGroupId">
          <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
        </select>
      </label>
      <label>Subject
        <select v-model="form.subject_id">
          <option value="">—</option>
          <option v-for="s in academic.subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </label>
      <label>Label <input v-model="form.label" /></label>
      <label>URL <input v-model="form.url" /></label>
      <label>Scheduled date <input v-model="form.scheduled_date" type="date" /></label>
      <label>Start time <input v-model="form.start_time" type="time" /></label>
      <label>End time <input v-model="form.end_time" type="time" /></label>
      <button type="button" class="primary" :disabled="saving" @click="create">
        {{ saving ? 'Saving…' : 'Add link' }}
      </button>
    </section>

    <section v-if="activeTab === 'list'" class="card">
      <h2>Links</h2>
      <label>Study group
        <select v-model="academic.studyGroupId">
          <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
        </select>
      </label>
      <label>Status
        <select v-model="statusFilter">
          <option value="">All</option>
          <option value="pending_approval">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </label>
      <p v-if="loading">Loading…</p>
      <p v-else-if="!items.length" class="empty">No links for this filter.</p>
      <div v-for="l in items" :key="l.id" class="item">
        <div class="item-head">
          <strong>{{ l.label }}</strong>
          <span class="badge" :data-status="l.status">{{ l.status }}</span>
        </div>
        <p class="meta">
          {{ l.subject?.name || 'No subject' }}
          <template v-if="l.study_group?.name"> · {{ l.study_group.name }}</template>
          · {{ formatDate(l.scheduled_date) }} {{ String(l.start_time || '').slice(0, 5) }}
        </p>
        <a v-if="l.url" :href="l.url" target="_blank" rel="noopener" class="link">Open link</a>
      </div>
    </section>

    <section v-if="activeTab === 'pending'" class="card">
      <h2>Pending approval</h2>
      <p v-if="pendingLoading">Loading…</p>
      <p v-else-if="!pendingItems.length" class="empty">No links awaiting approval.</p>
      <div v-for="l in pendingItems" :key="l.id" class="item">
        <div class="item-head">
          <strong>{{ l.label }}</strong>
          <span class="badge" data-status="pending_approval">Pending</span>
        </div>
        <p class="meta">
          {{ l.subject?.name || 'No subject' }}
          <template v-if="l.study_group?.name"> · {{ l.study_group.name }}</template>
          · {{ formatDate(l.scheduled_date) }}
        </p>
        <div class="row-actions">
          <button type="button" class="primary" :disabled="busyId === l.id" @click="approve(l.id)">Approve</button>
          <button type="button" class="danger" :disabled="busyId === l.id" @click="reject(l.id)">Reject</button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import api from '../api/client'
import { useAcademic } from '../composables/useAcademic'
import { usePermissions } from '../composables/usePermissions'
import { formatDate, paginated } from '../composables/format'

const academic = useAcademic()
const { can } = usePermissions()

const canManage = computed(() => can('manage_online_classes'))
const canApprove = computed(() => can('approve_online_classes'))

const tabs = computed(() => {
  const list = []
  if (canManage.value) list.push({ id: 'create', label: 'Create' })
  if (canManage.value || canApprove.value) list.push({ id: 'list', label: 'Links' })
  if (canApprove.value) list.push({ id: 'pending', label: 'Pending approval' })
  return list
})

const activeTab = ref('list')
const form = reactive({
  label: 'Google Meet',
  url: 'https://meet.google.com/',
  scheduled_date: '',
  start_time: '10:00',
  end_time: '',
  subject_id: '',
})
const items = ref([])
const pendingItems = ref([])
const statusFilter = ref('')
const loading = ref(false)
const pendingLoading = ref(false)
const saving = ref(false)
const busyId = ref(null)
const msg = ref('')
const err = ref('')

async function loadList() {
  loading.value = true
  err.value = ''
  try {
    const params = { per_page: 30 }
    if (academic.studyGroupId) params.study_group_id = academic.studyGroupId
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await api.get('/efsc/online-classes', { params })
    items.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load'
    items.value = []
  } finally {
    loading.value = false
  }
}

async function loadPending() {
  if (!canApprove.value) {
    pendingItems.value = []
    return
  }
  pendingLoading.value = true
  try {
    const { data } = await api.get('/efsc/online-classes', {
      params: { status: 'pending_approval', per_page: 50 },
    })
    pendingItems.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load pending'
    pendingItems.value = []
  } finally {
    pendingLoading.value = false
  }
}

async function create() {
  msg.value = ''
  err.value = ''
  if (!academic.studyGroupId || !form.label || !form.url || !form.scheduled_date) {
    err.value = 'Study group, label, URL, and date are required.'
    return
  }
  saving.value = true
  try {
    await api.post('/efsc/online-classes', {
      study_group_id: Number(academic.studyGroupId),
      subject_id: form.subject_id || null,
      label: form.label,
      url: form.url,
      scheduled_date: form.scheduled_date,
      start_time: form.start_time,
      end_time: form.end_time || null,
    })
    msg.value = 'Link created — awaiting approval.'
    await loadList()
    await loadPending()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to create'
  } finally {
    saving.value = false
  }
}

async function approve(id) {
  busyId.value = id
  try {
    await api.post(`/efsc/online-classes/${id}/approve`)
    msg.value = 'Link approved.'
    await loadPending()
    await loadList()
  } catch (e) {
    err.value = e.response?.data?.message || 'Approve failed'
  } finally {
    busyId.value = null
  }
}

async function reject(id) {
  busyId.value = id
  try {
    await api.post(`/efsc/online-classes/${id}/reject`)
    msg.value = 'Link rejected.'
    await loadPending()
    await loadList()
  } catch (e) {
    err.value = e.response?.data?.message || 'Reject failed'
  } finally {
    busyId.value = null
  }
}

watch([() => academic.studyGroupId, statusFilter], loadList)
watch(activeTab, (t) => {
  if (t === 'pending') loadPending()
  if (t === 'list') loadList()
})

onMounted(() => {
  if (tabs.value.length) activeTab.value = tabs.value[0].id
  loadList()
  loadPending()
})
</script>

<style scoped>
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.tabs button { padding: 0.4rem 0.85rem; border: 1px solid #e4e4e7; background: #fff; border-radius: 6px; cursor: pointer; }
.tabs button.active { background: #0f766e; color: #fff; border-color: #0f766e; }
.item { padding: 0.75rem 0; border-bottom: 1px solid #f4f4f5; }
.item-head { display: flex; gap: 0.5rem; align-items: center; justify-content: space-between; }
.meta { color: #71717a; font-size: 0.9rem; margin: 0.25rem 0; }
.badge { font-size: 0.75rem; padding: 0.15rem 0.45rem; border-radius: 999px; background: #f4f4f5; }
.badge[data-status="pending_approval"] { background: #fef3c7; color: #92400e; }
.badge[data-status="approved"] { background: #dcfce7; color: #166534; }
.badge[data-status="rejected"] { background: #fee2e2; color: #991b1b; }
.row-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.danger { background: #b91c1c; color: #fff; border: none; padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; }
.link { color: #0f766e; }
.ok { color: #15803d; }
.error { color: #b91c1c; }
.empty { color: #a1a1aa; }
.muted { color: #71717a; }
</style>
