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
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useParentStore } from '../../stores/parent'
import { useParentList } from '../../composables/useParentList'
import { childName } from '../../composables/format'

const parent = useParentStore()
const child = computed(() => parent.selectedChild)
const { items, loading, err } = useParentList('/efsc/fee-vouchers')
</script>

<style scoped>
.item { padding: 0.65rem 0; border-bottom: 1px solid #f4f4f5; }
.muted { color: #71717a; }
.small { font-size: 0.8rem; }
</style>
