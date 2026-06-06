<template>
  <div>
    <template v-if="detail">
      <button type="button" class="back" @click="detail = null">← Back</button>
      <h1>{{ detail.subject?.name }} — {{ detail.assessment?.name }}</h1>
      <div class="card">
        <div v-for="e in detail.entries || []" :key="e.id" class="item">
          <strong>{{ childName(e.student) }}</strong>
          <span class="muted">
            {{ e.marks_obtained ?? '—' }} / {{ e.max_marks ?? '—' }}
            <template v-if="e.grade"> · {{ e.grade }}</template>
          </span>
        </div>
      </div>
    </template>
    <template v-else>
      <h1>Marks</h1>
      <p v-if="child" class="muted">For {{ childName(child) }}</p>
      <div class="card">
        <p v-if="loading">Loading…</p>
        <p v-else-if="err" class="error">{{ err }}</p>
        <button
          v-for="m in items"
          :key="m.id"
          type="button"
          class="item link"
          @click="open(m.id)"
        >
          <strong>{{ m.subject?.name }} — {{ m.assessment?.name || m.assessment?.type }}</strong>
          <span class="muted"> · {{ m.school_class?.name }} · Section {{ m.section?.name }}</span>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '../../api/client'
import { useParentStore } from '../../stores/parent'
import { useParentList } from '../../composables/useParentList'
import { childName } from '../../composables/format'

const parent = useParentStore()
const child = computed(() => parent.selectedChild)
const { items, loading, err } = useParentList('/efsc/mark-sheets', { per_page: 30 })
const detail = ref(null)
const detailErr = ref('')

async function open(id) {
  detailErr.value = ''
  try {
    const { data } = await api.get(`/efsc/mark-sheets/${id}`)
    detail.value = data
  } catch (e) {
    detailErr.value = e.response?.data?.message || 'Failed to load'
  }
}
</script>

<style scoped>
.item { display: block; width: 100%; text-align: left; padding: 0.65rem 0; border: none; border-bottom: 1px solid #f4f4f5; background: none; cursor: pointer; }
.link:hover { background: #f8fafc; }
.back { background: none; border: none; color: #2563eb; cursor: pointer; margin-bottom: 0.75rem; }
.muted { color: #71717a; font-size: 0.9rem; }
</style>
