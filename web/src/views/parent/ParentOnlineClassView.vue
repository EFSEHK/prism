<template>
  <div>
    <h1>Online classes</h1>
    <p v-if="child" class="muted">For {{ childName(child) }}</p>
    <div class="card">
      <p v-if="loading">Loading…</p>
      <p v-else-if="err" class="error">{{ err }}</p>
      <p v-else-if="items.length === 0" class="muted">No online class links.</p>
      <a v-for="l in items" :key="l.id" class="item link" :href="l.url" target="_blank" rel="noopener">
        <strong>{{ l.label }}</strong>
        <span v-if="l.subject?.name" class="muted"> · {{ l.subject.name }}</span>
      </a>
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
const { items, loading, err } = useParentList('/prism/online-classes')
</script>

<style scoped>
.item { display: block; padding: 0.65rem 0; border-bottom: 1px solid #f4f4f5; text-decoration: none; color: inherit; }
.link:hover { background: #f8fafc; }
.muted { color: #71717a; }
</style>
