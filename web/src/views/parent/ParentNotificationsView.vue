<template>
  <div>
    <h1>Notifications</h1>
    <p class="muted">Alerts about your children and school announcements.</p>

    <p v-if="loading" class="empty">Loading…</p>
    <p v-else-if="err" class="error">{{ err }}</p>

    <template v-else>
      <div class="card">
        <h2>Alerts</h2>
        <p class="muted small">Attendance, marks, fees, and other updates about your children.</p>
        <p v-if="!alerts.length" class="empty inline">No alerts yet.</p>
        <button
          v-for="n in alerts"
          :key="`alert-${n.id}`"
          type="button"
          class="item link"
          :class="{ unread: !n.read_at }"
          @click="markRead(n)"
        >
          <strong>{{ n.title }}</strong>
          <span class="muted"> · {{ formatDateTime(n.created_at) }}</span>
          <p v-if="n.body" class="body">{{ n.body }}</p>
          <p v-if="!n.read_at" class="hint">Tap to mark read</p>
        </button>
      </div>

      <div class="card">
        <h2>Announcements</h2>
        <p class="muted small">General school broadcasts.</p>
        <p v-if="!broadcasts.length" class="empty inline">No announcements yet.</p>
        <article v-for="b in broadcasts" :key="`broadcast-${b.id}`" class="announcement">
          <strong>{{ b.title }}</strong>
          <span v-if="b.published_at" class="muted"> · {{ formatDateTime(b.published_at) }}</span>
          <p v-if="b.body" class="body">{{ b.body }}</p>
        </article>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../../api/client'
import { paginated } from '../../composables/format'
import { formatDateTime } from '../../composables/format'

const alerts = ref([])
const broadcasts = ref([])
const loading = ref(true)
const err = ref('')

async function load() {
  loading.value = true
  err.value = ''
  try {
    const [alertsRes, broadcastsRes] = await Promise.all([
      api.get('/efsc/in-app-notifications'),
      api.get('/efsc/broadcasts'),
    ])
    alerts.value = paginated(alertsRes.data)
    broadcasts.value = paginated(broadcastsRes.data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load notifications'
    alerts.value = []
    broadcasts.value = []
  } finally {
    loading.value = false
  }
}

async function markRead(n) {
  if (n.read_at) return
  try {
    await api.post(`/efsc/in-app-notifications/${n.id}/read`)
    n.read_at = new Date().toISOString()
  } catch {
    await load()
  }
}

onMounted(load)
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
.empty.inline {
  padding: 0.65rem 0.75rem;
  text-align: left;
}
.item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 0.75rem 0;
  border: none;
  border-bottom: 1px solid #f4f4f5;
  background: none;
  cursor: pointer;
}
.item.unread {
  background: #eff6ff;
  margin: 0 -1rem;
  padding-left: 1rem;
  padding-right: 1rem;
}
.hint {
  font-size: 0.8rem;
  color: #2563eb;
  margin: 0.25rem 0 0;
}
.announcement {
  padding: 0.75rem 0;
  border-bottom: 1px solid #f4f4f5;
}
.announcement:last-child {
  border-bottom: none;
}
.body {
  margin: 0.35rem 0 0;
  color: #3f3f46;
  font-size: 0.9rem;
}
</style>
