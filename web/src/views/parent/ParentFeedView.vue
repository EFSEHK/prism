<template>
  <div>
    <h1>Events &amp; announcements</h1>
    <div class="card">
      <p v-if="loading">Loading…</p>
      <p v-else-if="err" class="error">{{ err }}</p>
      <p v-else-if="items.length === 0" class="muted">No feed posts.</p>
      <article v-for="f in items" :key="f.id" class="item">
        <strong>{{ f.title }}</strong>
        <span class="muted">
          · {{ f.type }} · {{ f.scope }}
          <template v-if="f.published_at"> · {{ formatDate(f.published_at) }}</template>
        </span>
        <p v-if="f.body">{{ f.body }}</p>
      </article>
    </div>
  </div>
</template>

<script setup>
import { useParentList } from '../../composables/useParentList'
import { formatDate } from '../../composables/format'

const { items, loading, err } = useParentList('/prism/feed')
</script>

<style scoped>
.item { padding: 0.75rem 0; border-bottom: 1px solid #f4f4f5; }
.muted { color: #71717a; font-size: 0.9rem; }
</style>
