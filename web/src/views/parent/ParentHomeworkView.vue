<template>
  <div>
    <h1>Homework diary</h1>
    <p v-if="child" class="muted">For {{ childName(child) }}</p>
    <div class="card">
      <p v-if="loading">Loading…</p>
      <p v-else-if="err" class="error">{{ err }}</p>
      <p v-else-if="items.length === 0" class="muted">No homework posts.</p>
      <div v-for="h in items" :key="h.id" class="item">
        <strong>{{ h.title }}</strong>
        <span class="muted"> · {{ itemMeta(h) }}</span>
        <p v-if="h.body">{{ h.body }}</p>
        <p v-if="h.due_date" class="muted small">Due {{ formatDate(h.due_date) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import api from '../../api/client'
import { useParentStore } from '../../stores/parent'
import { childName, formatDate, paginated } from '../../composables/format'

const parent = useParentStore()
const child = computed(() => parent.selectedChild)
const items = ref([])
const loading = ref(true)
const err = ref('')

function itemMeta(h) {
  const parts = [
    h.subject?.name,
    h.study_group?.name,
    h.section?.name ? `Section ${h.section.name}` : null,
  ].filter(Boolean)
  return parts.join(' · ') || '—'
}

function matchesChild(h, c) {
  if (!c) return true
  const groupMatch = c.study_group_id && h.study_group_id === c.study_group_id
  const sectionMatch = c.section_id && h.section_id === c.section_id
  return Boolean(groupMatch || sectionMatch)
}

async function load() {
  loading.value = true
  err.value = ''
  try {
    const { data } = await api.get('/efsc/homework', { params: { per_page: 50 } })
    const all = paginated(data)
    items.value = child.value ? all.filter((h) => matchesChild(h, child.value)) : all
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load'
    items.value = []
  } finally {
    loading.value = false
  }
}

watch(() => child.value?.id, load)
onMounted(load)
</script>

<style scoped>
.item { padding: 0.65rem 0; border-bottom: 1px solid #f4f4f5; }
.muted { color: #71717a; font-size: 0.9rem; }
.small { font-size: 0.8rem; }
.error { color: #b91c1c; }
</style>
