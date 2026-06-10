<template>
  <div class="app">
    <header v-if="auth.token" class="top">
      <div class="top-bar">
        <span class="brand">EFSC-YA</span>
        <div class="user-actions">
          <span v-if="userName" class="user-name">{{ userName }}</span>
          <template v-if="canViewAs">
            <span class="user-sep" aria-hidden="true" />
            <label class="view-as-control">
              <span class="view-as-label">{{ viewAsRole ? 'Viewing as' : 'View as' }}</span>
              <select
                class="view-as-select"
                :value="viewAsRole"
                aria-label="View as role"
                @change="onViewAsChange"
              >
                <option v-if="!viewAsRole" disabled value="">Select role…</option>
                <option v-if="viewAsRole" value="">Your account</option>
                <option v-for="r in viewAsOptions" :key="r.name" :value="r.name">{{ r.label }}</option>
              </select>
            </label>
          </template>
          <span class="user-sep" aria-hidden="true" />
          <button type="button" class="link" @click="logout">Logout</button>
        </div>
      </div>
      <nav class="top-nav">
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
      </nav>
    </header>
    <main>
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useParentStore } from './stores/parent'
import { useViewAsStore } from './stores/viewAs'
import { useRoles } from './composables/useRoles'
import { usePermissions } from './composables/usePermissions'

const auth = useAuthStore()
const parent = useParentStore()
const viewAs = useViewAsStore()
const router = useRouter()

const {
  isLearner, isParent, isStudent, isSuperadmin, canViewAs,
  canApprove, canStaff, canTimetable, canFees, canBroadcasts, canLeave,
} = useRoles()
const { canConfigure } = usePermissions()

const userName = computed(() => auth.user?.name ?? '')
const selectedChild = computed(() => parent.selectedChild)
const viewAsRole = computed(() => viewAs.role)
const viewAsOptions = computed(() => viewAs.options)

onMounted(async () => {
  if (auth.token && canViewAs.value) {
    try {
      await viewAs.loadOptions()
    } catch {
      /* ignore */
    }
  }
})

watch(canViewAs, async (allowed) => {
  if (allowed && auth.token) {
    try {
      await viewAs.loadOptions()
    } catch {
      /* ignore */
    }
  }
})

async function onViewAsChange(event) {
  viewAs.setRole(event.target.value)
  await parent.clearChild()
  router.push('/')
}

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
  background: #18181b;
  color: #fafafa;
}
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.6rem 1.25rem;
}
.top-nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.5rem 1.25rem;
  border-top: 1px solid #27272a;
}
.top a,
.link {
  color: #a1a1aa;
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
.user-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.user-actions .link {
  margin-left: 0;
}
.user-name {
  font-size: 0.85rem;
  font-weight: 500;
  color: #d4d4d8;
}
.user-sep {
  width: 1px;
  height: 1.1rem;
  background: #52525b;
}
.view-as-control {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}
.view-as-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: #a1a1aa;
  white-space: nowrap;
}
.view-as-select {
  display: inline-block;
  width: auto;
  max-width: none;
  margin: 0;
  background: #27272a;
  color: #d4d4d8;
  border: 1px solid #3f3f46;
  border-radius: 4px;
  font-size: 0.8rem;
  padding: 0.2rem 0.4rem;
  cursor: pointer;
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
