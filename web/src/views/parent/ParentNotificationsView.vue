<template>
  <div>
    <h1>Notifications</h1>
    <p v-if="loading">Loading…</p>
    <p v-else-if="err" class="error">{{ err }}</p>
    <ul v-else>
      <li v-for="b in items" :key="b.id" class="card">
        <strong>{{ b.title }}</strong>
        <p>{{ b.body }}</p>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { useParentList } from '../../composables/useParentList'

const { items, loading, err } = useParentList('/efsc/broadcasts')
</script>
