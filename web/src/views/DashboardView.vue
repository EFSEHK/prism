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
    <div class="card grid">
      <h2>School modules</h2>
      <RouterLink v-if="canStaff" to="/attendance" class="tile">Attendance</RouterLink>
      <RouterLink v-if="canStaff" to="/marks" class="tile">Marks</RouterLink>
      <RouterLink v-if="canStaff" to="/homework" class="tile">Homework</RouterLink>
      <RouterLink v-if="canTimetable" to="/timetable" class="tile">Timetable</RouterLink>
      <RouterLink v-if="canStaff" to="/online-classes" class="tile">Online class</RouterLink>
      <RouterLink v-if="canFees" to="/fees" class="tile">Fee vouchers</RouterLink>
      <RouterLink v-if="canBroadcasts" to="/notifications" class="tile">Notifications</RouterLink>
      <RouterLink v-if="canLeave" to="/leave" class="tile">Leave</RouterLink>
      <RouterLink v-if="canApprove" to="/approvals" class="tile">Approvals</RouterLink>
      <RouterLink v-if="isSuperadmin" to="/admin/permissions" class="tile">Permissions</RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRoles } from '../composables/useRoles'
import api from '../api/client'

const auth = useAuthStore()
const user = computed(() => auth.user)
const roleNames = computed(() => (user.value?.roles || []).map((r) => r.name).join(', '))

const { isSuperadmin, canApprove, canStaff, canTimetable, canFees, canBroadcasts, canLeave } = useRoles()

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
.grid { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.tile {
  display: inline-block;
  padding: 0.75rem 1rem;
  background: #eff6ff;
  color: #1d4ed8;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
}
</style>
