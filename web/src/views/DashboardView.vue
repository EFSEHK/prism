<template>
  <div>
    <h1>Dashboard</h1>
    <div class="card">
      <p><strong>{{ user?.name }}</strong></p>
      <p class="muted">Roles: {{ roleNames }}</p>
    </div>
    <div v-if="widgets.pending_approvals != null" class="card">
      <h2>Pending notification approvals</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="!widgets.pending_approvals">No pending items.</p>
      <p v-else>{{ widgets.pending_approvals }} pending — <RouterLink to="/approvals">Review</RouterLink></p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../api/client'

const auth = useAuthStore()
const user = computed(() => auth.user)
const roleNames = computed(() => (user.value?.roles || []).map((r) => r.name).join(', '))

const loading = ref(true)
const widgets = ref({})

onMounted(async () => {
  try {
    const { data } = await api.get('/efsc/dashboard')
    widgets.value = data.widgets || {}
  } catch {
    widgets.value = {}
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.muted { color: #71717a; font-size: 0.9rem; }
</style>
