<template>
  <div class="app">
    <header v-if="auth.token" class="top">
      <span class="brand">EFSC-YA</span>
      <nav>
        <template v-if="isLearner">
          <RouterLink to="/">Home</RouterLink>
          <template v-if="selectedChild || isStudent">
            <RouterLink to="/dashboard">Dashboard</RouterLink>
            <RouterLink to="/homework">Homework</RouterLink>
            <RouterLink to="/marks">Marks</RouterLink>
            <RouterLink to="/attendance">Attendance</RouterLink>
            <RouterLink to="/timetable">Timetable</RouterLink>
            <RouterLink to="/notifications">Notifications</RouterLink>
            <RouterLink to="/fees">Fees</RouterLink>
            <RouterLink to="/online-classes">Online</RouterLink>
            <RouterLink to="/leave">Leave</RouterLink>
            <RouterLink v-if="isParent" to="/alerts">Alerts</RouterLink>
            <button v-if="isParent" type="button" class="link" @click="switchChild">Switch child</button>
          </template>
        </template>
        <template v-else>
          <RouterLink to="/">Dashboard</RouterLink>
          <RouterLink v-if="canConfigure" to="/admin/academic">Configuration</RouterLink>
          <RouterLink v-if="isSuperadmin" to="/admin/permissions">Permissions</RouterLink>
          <RouterLink v-if="canApprove" to="/approvals">Approvals</RouterLink>
          <RouterLink v-if="canStaff" to="/attendance">Attendance</RouterLink>
          <RouterLink v-if="canStaff" to="/marks">Marks</RouterLink>
          <RouterLink v-if="canStaff" to="/homework">Homework</RouterLink>
          <RouterLink v-if="canTimetable" to="/timetable">Timetable</RouterLink>
          <RouterLink v-if="canStaff" to="/online-classes">Online</RouterLink>
          <RouterLink v-if="canFees" to="/fees">Fees</RouterLink>
          <RouterLink v-if="canBroadcasts" to="/notifications">Notifications</RouterLink>
          <RouterLink v-if="canLeave" to="/leave">Leave</RouterLink>
        </template>
        <button type="button" class="link" @click="logout">Logout</button>
      </nav>
    </header>
    <main>
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useParentStore } from './stores/parent'
import { useRoles } from './composables/useRoles'
import { usePermissions } from './composables/usePermissions'

const auth = useAuthStore()
const parent = useParentStore()
const router = useRouter()

const {
  isLearner, isParent, isStudent, isSuperadmin,
  canApprove, canStaff, canTimetable, canFees, canBroadcasts, canLeave,
} = useRoles()
const { canConfigure } = usePermissions()

const selectedChild = computed(() => parent.selectedChild)

async function logout() {
  await auth.logout()
  router.push('/login')
}

async function switchChild() {
  await parent.clearChild()
  router.push('/')
}
</script>

<style>
:root {
  font-family: system-ui, sans-serif;
  color: #1a1a1a;
  background: #f4f4f5;
}
.app {
  min-height: 100vh;
}
.top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1.25rem;
  background: #18181b;
  color: #fafafa;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.top nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
}
.top a,
.link {
  color: #a1a1aa;
  margin-left: 0.75rem;
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.9rem;
}
.top a.router-link-active {
  color: #fff;
}
.brand {
  font-weight: 700;
}
main {
  max-width: 960px;
  margin: 0 auto;
  padding: 1.5rem;
}
.card {
  background: #fff;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.08);
}
.error {
  color: #b91c1c;
  font-size: 0.9rem;
}
button.primary {
  background: #2563eb;
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  margin-top: 0.5rem;
}
button.secondary {
  background: #fff;
  border: 1px solid #d4d4d8;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
}
input,
textarea,
select {
  display: block;
  width: 100%;
  max-width: 320px;
  margin: 0.5rem 0 1rem;
  padding: 0.5rem;
}
label {
  font-size: 0.85rem;
  font-weight: 600;
}
</style>
