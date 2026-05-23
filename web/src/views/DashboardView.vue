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
    <div class="card grid">
      <h2>School modules</h2>
      <RouterLink v-if="canStaff" to="/attendance" class="tile">Attendance</RouterLink>
      <RouterLink v-if="canStaff" to="/marks" class="tile">Marks</RouterLink>
      <RouterLink v-if="canStaff" to="/homework" class="tile">Homework</RouterLink>
      <RouterLink v-if="canTimetable" to="/timetable" class="tile">Timetable</RouterLink>
      <RouterLink v-if="canStaff" to="/online-classes" class="tile">Online class</RouterLink>
      <RouterLink v-if="canFees" to="/fees" class="tile">Fee vouchers</RouterLink>
      <RouterLink v-if="canFeed" to="/feed" class="tile">Feed</RouterLink>
      <RouterLink v-if="canLeave" to="/leave" class="tile">Leave</RouterLink>
      <RouterLink v-if="canApprove" to="/approvals" class="tile">Approvals</RouterLink>
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
const roles = computed(() => (user.value?.roles || []).map((r) => r.name))

const canApprove = computed(() =>
  roles.value.some((n) =>
    ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge'].includes(n)
  )
)
const canStaff = computed(() =>
  roles.value.some((n) => ['superadmin', 'admin', 'teacher', 'section_head'].includes(n))
)
const canTimetable = computed(() =>
  roles.value.some((n) => ['superadmin', 'admin', 'computer_operator', 'teacher'].includes(n))
)
const canFees = computed(() =>
  roles.value.some((n) => ['superadmin', 'admin', 'accountant', 'computer_operator'].includes(n))
)
const canFeed = computed(() =>
  roles.value.some((n) => ['superadmin', 'admin', 'principal', 'vice_principal'].includes(n))
)
const canLeave = computed(() =>
  roles.value.some((n) => ['superadmin', 'admin', 'teacher', 'principal', 'vice_principal', 'section_head'].includes(n))
)

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
.grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}
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
