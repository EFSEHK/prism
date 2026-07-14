<template>
  <div>
    <h1>Homework</h1>
    <p class="muted">Post diary entries for approval, or review pending submissions.</p>

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
    <p v-else class="empty">You do not have homework permissions.</p>

    <div v-if="msg" class="ok">{{ msg }}</div>
    <div v-if="err" class="error">{{ err }}</div>

    <section v-if="activeTab === 'post'" class="card">
      <h2>New post</h2>
      <label>Study group
        <select v-model="academic.studyGroupId">
          <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
        </select>
      </label>
      <label>Title <input v-model="form.title" /></label>
      <label>Body <textarea v-model="form.body" rows="3" /></label>
      <label>Due date <input v-model="form.due_date" type="date" /></label>
      <label>
        Subject
        <select v-model="form.subject_id">
          <option value="">—</option>
          <option v-for="s in academic.subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </label>
      <button type="button" class="primary" :disabled="saving" @click="create">
        {{ saving ? 'Posting…' : 'Post homework' }}
      </button>
    </section>

    <section v-if="activeTab === 'diary'" class="card">
      <h2>Diary</h2>
      <div class="toolbar">
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
      </div>
      <p v-if="loading">Loading…</p>
      <p v-else-if="!items.length" class="empty">No homework for this filter.</p>
      <div v-for="h in items" :key="h.id" class="item">
        <div class="item-head">
          <strong>{{ h.title }}</strong>
          <span class="badge" :data-status="h.status">{{ statusLabel(h.status) }}</span>
        </div>
        <p class="meta">
          {{ h.subject?.name || 'No subject' }}
          <template v-if="h.created_by?.name"> · {{ h.created_by.name }}</template>
          <template v-if="h.due_date"> · Due {{ formatDate(h.due_date) }}</template>
        </p>
        <p v-if="h.body" class="muted">{{ h.body }}</p>
      </div>
    </section>

    <section v-if="activeTab === 'pending'" class="card">
      <h2>Pending approval</h2>
      <label>Study group (optional)
        <select v-model="pendingGroupId">
          <option value="">All groups</option>
          <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
        </select>
      </label>
      <p v-if="pendingLoading">Loading…</p>
      <p v-else-if="!pendingItems.length" class="empty">No homework awaiting approval.</p>
      <div v-for="h in pendingItems" :key="h.id" class="item pending-item">
        <div class="item-head">
          <strong>{{ h.title }}</strong>
          <span class="badge" data-status="pending_approval">Pending</span>
        </div>
        <p class="meta">
          {{ h.subject?.name || 'No subject' }}
          <template v-if="h.study_group?.name"> · {{ h.study_group.name }}</template>
          <template v-if="h.created_by?.name"> · {{ h.created_by.name }}</template>
          <template v-if="h.due_date"> · Due {{ formatDate(h.due_date) }}</template>
        </p>
        <p v-if="h.body" class="muted">{{ h.body }}</p>
        <div class="row-actions">
          <button
            type="button"
            class="primary"
            :disabled="busyId === h.id"
            @click="approve(h.id)"
          >
            Approve
          </button>
          <button
            type="button"
            class="danger"
            :disabled="busyId === h.id"
            @click="reject(h.id)"
          >
            Reject
          </button>
        </div>
      </div>
    </section>
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

const canPost = computed(() => can('post_homework'))
const canApprove = computed(() => can('approve_homework'))

const tabs = computed(() => {
  const list = []
  if (canPost.value) list.push({ id: 'post', label: 'Post' })
  if (canPost.value || canApprove.value) list.push({ id: 'diary', label: 'Diary' })
  if (canApprove.value) list.push({ id: 'pending', label: 'Pending approval' })
  return list
})

const activeTab = ref('diary')
const form = reactive({ title: '', body: '', due_date: '', subject_id: '' })
const items = ref([])
const pendingItems = ref([])
const statusFilter = ref('')
const pendingGroupId = ref('')
const loading = ref(false)
const pendingLoading = ref(false)
const saving = ref(false)
const busyId = ref(null)
const msg = ref('')
const err = ref('')

function statusLabel(status) {
  if (status === 'pending_approval') return 'Pending'
  if (status === 'approved') return 'Approved'
  if (status === 'rejected') return 'Rejected'
  return status || '—'
}

function setDefaultTab() {
  const ids = tabs.value.map((t) => t.id)
  const fromQuery = route.query.tab
  if (fromQuery && ids.includes(String(fromQuery))) {
    activeTab.value = String(fromQuery)
    return
  }
  if (canApprove.value && ids.includes('pending')) activeTab.value = 'pending'
  else if (canPost.value && ids.includes('post')) activeTab.value = 'post'
  else if (ids.length) activeTab.value = ids[0]
}

async function loadDiary() {
  if (!canPost.value && !canApprove.value) return
  loading.value = true
  err.value = ''
  try {
    const params = { per_page: 50 }
    if (academic.studyGroupId) params.study_group_id = academic.studyGroupId
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await api.get('/efsc/homework', { params })
    items.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load homework'
    items.value = []
  } finally {
    loading.value = false
  }
}

async function loadPending() {
  if (!canApprove.value) return
  pendingLoading.value = true
  err.value = ''
  try {
    const params = { status: 'pending_approval', per_page: 50 }
    if (pendingGroupId.value) params.study_group_id = pendingGroupId.value
    const { data } = await api.get('/efsc/homework', { params })
    pendingItems.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load pending homework'
    pendingItems.value = []
  } finally {
    pendingLoading.value = false
  }
}

async function create() {
  err.value = ''
  msg.value = ''
  if (!academic.studyGroupId || !form.title.trim()) {
    err.value = 'Study group and title are required.'
    return
  }
  saving.value = true
  try {
    await api.post('/efsc/homework', {
      study_group_id: Number(academic.studyGroupId),
      subject_id: form.subject_id || null,
      title: form.title.trim(),
      body: form.body,
      due_date: form.due_date || null,
    })
    msg.value = 'Posted — awaiting section head approval.'
    form.title = ''
    form.body = ''
    form.due_date = ''
    form.subject_id = ''
    if (activeTab.value === 'diary') await loadDiary()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to post homework'
  } finally {
    saving.value = false
  }
}

async function approve(id) {
  busyId.value = id
  err.value = ''
  msg.value = ''
  try {
    await api.post(`/efsc/homework/${id}/approve`)
    msg.value = 'Homework approved. Parent notification queued.'
    await loadPending()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to approve'
  } finally {
    busyId.value = null
  }
}

async function reject(id) {
  busyId.value = id
  err.value = ''
  msg.value = ''
  try {
    await api.post(`/efsc/homework/${id}/reject`)
    msg.value = 'Homework rejected.'
    await loadPending()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to reject'
  } finally {
    busyId.value = null
  }
}

watch(activeTab, (tab) => {
  msg.value = ''
  err.value = ''
  if (tab === 'diary') loadDiary()
  if (tab === 'pending') loadPending()
})

watch(() => academic.studyGroupId, () => {
  if (activeTab.value === 'diary') loadDiary()
})

watch(statusFilter, () => {
  if (activeTab.value === 'diary') loadDiary()
})

watch(pendingGroupId, () => {
  if (activeTab.value === 'pending') loadPending()
})

watch(tabs, () => {
  if (!tabs.value.some((t) => t.id === activeTab.value)) setDefaultTab()
})

onMounted(() => {
  setDefaultTab()
  if (activeTab.value === 'diary') loadDiary()
  if (activeTab.value === 'pending') loadPending()
})
</script>

<style scoped>
.muted { color: #71717a; font-size: 0.9rem; }
.small { font-size: 0.85rem; }
.ok { color: #15803d; margin-bottom: 0.75rem; }
.error { color: #b91c1c; margin-bottom: 0.75rem; }
.empty { color: #71717a; }
.tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin: 1rem 0;
}
.tabs button {
  border: 1px solid #d4d4d8;
  background: #fff;
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  cursor: pointer;
}
.tabs button.active {
  background: #0f766e;
  border-color: #0f766e;
  color: #fff;
}
.toolbar {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
.item {
  padding: 0.75rem 0;
  border-bottom: 1px solid #eee;
}
.item-head {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
}
.meta { font-size: 0.9rem; color: #52525b; margin: 0.25rem 0; }
.badge {
  font-size: 0.75rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  background: #f4f4f5;
  color: #3f3f46;
}
.badge[data-status="pending_approval"] { background: #fffbeb; color: #b45309; }
.badge[data-status="approved"] { background: #ecfdf5; color: #047857; }
.badge[data-status="rejected"] { background: #fef2f2; color: #b91c1c; }
.row-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.danger {
  background: #b91c1c;
  color: #fff;
  border: none;
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  cursor: pointer;
}
.danger:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
