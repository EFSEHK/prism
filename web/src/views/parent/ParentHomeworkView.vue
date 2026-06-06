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
        <span class="muted"> · {{ h.subject?.name }} · {{ h.school_class?.name }}</span>
        <p v-if="h.body">{{ h.body }}</p>
        <p v-if="h.due_date" class="muted small">Due {{ formatDate(h.due_date) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useParentStore } from '../../stores/parent'
import { useParentList } from '../../composables/useParentList'
import { childName, formatDate } from '../../composables/format'

const parent = useParentStore()
const child = computed(() => parent.selectedChild)
const { items, loading, err } = useParentList('/efsc/homework', { per_page: 30 })
</script>

<style scoped>
.item { padding: 0.65rem 0; border-bottom: 1px solid #f4f4f5; }
.muted { color: #71717a; font-size: 0.9rem; }
.small { font-size: 0.8rem; }
</style>
