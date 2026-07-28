<template>
  <div>
    <h1>Dashboard</h1>
    <div class="card">
      <p><strong>{{ user?.name }}</strong></p>
      <p class="muted">Roles: {{ roleNames }}</p>
    </div>
    <div v-if="canAccessAdminPortal" class="card admin-portal-card">
      <h2>Administration portal</h2>
      <p class="muted">Apps, imports, users, academic setup, and more.</p>
      <RouterLink to="/admin" class="portal-cta">Open portal →</RouterLink>
    </div>
    <div v-if="widgets.pending_approvals != null" class="card">
      <h2>Pending notification approvals</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="!widgets.pending_approvals">No pending items.</p>
      <p v-else>{{ widgets.pending_approvals }} pending — <RouterLink to="/approvals">Review</RouterLink></p>
    </div>
    <div v-if="widgets.attendance_pending_verify != null" class="card">
      <h2>Pending attendance</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="!widgets.attendance_pending_verify">No attendance awaiting verification.</p>
      <p v-else>
        {{ widgets.attendance_pending_verify }} pending —
        <RouterLink :to="{ path: '/attendance', query: { tab: 'pending' } }">Review</RouterLink>
      </p>
    </div>
    <div v-if="widgets.homework_pending_approve != null" class="card">
      <h2>Pending homework</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="!widgets.homework_pending_approve">No homework awaiting approval.</p>
      <p v-else>
        {{ widgets.homework_pending_approve }} pending —
        <RouterLink :to="{ path: '/homework', query: { tab: 'pending' } }">Review</RouterLink>
      </p>
    </div>
    <div v-if="widgets.marks_pending_verify != null" class="card">
      <h2>Pending marks</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="!widgets.marks_pending_verify">No mark sheets awaiting verification.</p>
      <p v-else>
        {{ widgets.marks_pending_verify }} pending —
        <RouterLink :to="{ path: '/marks', query: { tab: 'verify' } }">Review</RouterLink>
      </p>
    </div>
    <div v-if="widgets.leave_pending != null" class="card">
      <h2>Pending leave</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="!widgets.leave_pending">No leave requests awaiting decision.</p>
      <p v-else>
        {{ widgets.leave_pending }} pending —
        <RouterLink to="/leave">Review</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useAdminPortal } from '../composables/useAdminPortal'
import api from '../api/client'

const auth = useAuthStore()
const { canAccessAdminPortal } = useAdminPortal()
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
.admin-portal-card {
  border-left: 4px solid #2563eb;
  background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
}
.admin-portal-card h2 {
  margin: 0 0 0.35rem;
  font-size: 1.05rem;
}
.portal-cta {
  display: inline-block;
  margin-top: 0.5rem;
  color: #2563eb;
  font-weight: 600;
  text-decoration: none;
  font-size: 0.9rem;
}
.portal-cta:hover {
  text-decoration: underline;
}
</style>
