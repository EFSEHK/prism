<template>
  <div>
    <h1>Dashboard</h1>
    <div class="card">
      <p><strong>{{ user?.name }}</strong></p>
      <p class="muted">Roles: {{ roleNames }}</p>
    </div>
    <div class="card">
      <h2>Pending notification approvals</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="pendingCount === 0">No pending items.</p>
      <p v-else>{{ pendingCount }} pending — <RouterLink to="/approvals">Review</RouterLink></p>
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
const pendingCount = ref(0)

onMounted(async () => {
  try {
    const { data } = await api.get('/prism/notification-dispatches/pending')
    pendingCount.value = (data.data || []).length
  } catch {
    pendingCount.value = 0
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.muted {
  color: #71717a;
  font-size: 0.9rem;
}
</style>
