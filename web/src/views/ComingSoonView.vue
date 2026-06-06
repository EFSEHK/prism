<template>
  <div class="card">
    <h1>{{ title }}</h1>
    <p class="muted">Coming soon</p>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: 'Module' },
})
</script>

<style scoped>
.muted {
  color: #71717a;
  font-size: 1.1rem;
}
</style>
