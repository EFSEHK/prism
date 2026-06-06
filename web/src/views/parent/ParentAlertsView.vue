<template>
  <div>
    <h1>Notifications</h1>
    <div class="card">
      <p v-if="loading">Loading…</p>
      <p v-else-if="err" class="error">{{ err }}</p>
      <p v-else-if="items.length === 0" class="muted">No notifications.</p>
      <button
        v-for="n in items"
        :key="n.id"
        type="button"
        class="item link"
        :disabled="!!n.read_at"
        @click="markRead(n)"
      >
        <strong>{{ n.title }}</strong>
        <span class="muted"> · {{ formatDate(n.created_at) }}</span>
        <p v-if="n.body">{{ n.body }}</p>
        <p v-if="!n.read_at" class="hint">Click to mark read</p>
      </button>
    </div>
  </div>
</template>

<script setup>
import api from '../../api/client'
import { useParentList } from '../../composables/useParentList'
import { formatDate } from '../../composables/format'

const { items, loading, err, load } = useParentList('/efsc/in-app-notifications')

async function markRead(n) {
  if (n.read_at) return
  await api.post(`/efsc/in-app-notifications/${n.id}/read`)
  await load()
}
</script>

<style scoped>
.item { display: block; width: 100%; text-align: left; padding: 0.65rem 0; border: none; border-bottom: 1px solid #f4f4f5; background: none; cursor: pointer; }
.item:disabled { cursor: default; opacity: 0.85; }
.hint { font-size: 0.8rem; color: #2563eb; margin: 0.25rem 0 0; }
.muted { color: #71717a; font-size: 0.9rem; }
</style>
