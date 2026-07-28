<template>
  <div class="app">
    <header v-if="auth.token" class="top">
      <div class="top-bar">
        <span class="brand">EFSC-YA</span>
        <div class="user-actions">
          <span v-if="displayName" class="user-name">{{ displayName }}</span>
          <template v-if="isImpersonating">
            <span class="user-sep" aria-hidden="true" />
            <button type="button" class="link accent" @click="exitImpersonation">Back to Super Admin</button>
          </template>
          <template v-else-if="canViewAs">
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
          <button type="button" class="link notif-btn" aria-label="Open notifications" @click="openNotifications">
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
              <path d="M9 17a3 3 0 0 0 6 0" />
            </svg>
            <span v-if="unreadNotifications > 0" class="notif-badge">
              {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
            </span>
          </button>
          <span class="user-sep" aria-hidden="true" />
          <button type="button" class="link" @click="logout">Logout</button>
        </div>
      </div>
      <nav class="top-nav">
        <template v-if="isLearner">
          <RouterLink to="/home">Home</RouterLink>
          <template v-if="selectedChild || isStudent">
            <RouterLink
              v-for="mod in learnerNavModules"
              :key="mod.id"
              :to="mod.route_web || learnerFallbackRoute(mod.id)"
              :class="{ 'nav-soon': modules.moduleStatus(mod) === 'coming_soon' }"
            >
              {{ mod.label }}
            </RouterLink>
            <button v-if="isParent" type="button" class="link" @click="switchChild">Switch child</button>
          </template>
        </template>
        <template v-else>
          <RouterLink
            v-for="mod in staffNavModules"
            :key="mod.id"
            :to="mod.route_web || '/home'"
            :class="{ 'nav-soon': modules.moduleStatus(mod) === 'coming_soon' }"
          >
            {{ staffNavLabel(mod) }}
          </RouterLink>
          <RouterLink
            v-if="showAdminNav"
            to="/admin"
            class="nav-admin"
            :class="{ 'router-link-active': isAdminRoute }"
          >
            Portal
          </RouterLink>
        </template>
      </nav>
    </header>
    <main :class="{ 'main-flush': isFlushLayout }">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useParentStore } from './stores/parent'
import { useViewAsStore } from './stores/viewAs'
import { useModulesStore, ADMIN_SHELL_MODULE_IDS } from './stores/modules'
import { useRoles } from './composables/useRoles'
import { useAdminPortal } from './composables/useAdminPortal'

const auth = useAuthStore()
const parent = useParentStore()
const viewAs = useViewAsStore()
const modules = useModulesStore()
const route = useRoute()
const router = useRouter()

const {
  isLearner, isParent, isStudent, canViewAs,
} = useRoles()
const { canAccessAdminPortal } = useAdminPortal()

const displayName = computed(() => {
  if (viewAs.isImpersonating) return viewAs.impersonateUser?.name ?? ''
  return auth.user?.name ?? ''
})
const selectedChild = computed(() => parent.selectedChild)
const viewAsRole = computed(() => viewAs.role)
const viewAsOptions = computed(() => viewAs.options)
const isImpersonating = computed(() => viewAs.isImpersonating)
const isFlushLayout = computed(() => Boolean(route.meta.public || route.meta.guest))
const isAdminRoute = computed(() => route.path.startsWith('/admin'))
const showAdminNav = computed(() => canAccessAdminPortal.value && !isImpersonating.value)
const unreadNotifications = computed(() => parent.dashboard?.unread_notifications ?? 0)

const staffNavModules = computed(() => {
  const filtered = modules.items.filter((m) => {
    if (m.enabled === false) return false
    const platforms = m.platforms || ['web', 'mobile']
    if (!platforms.includes('web')) return false
    if (isImpersonating.value && ADMIN_SHELL_MODULE_IDS.includes(m.id)) return false
    return true
  })

  const isAdminShell = (m) => ADMIN_SHELL_MODULE_IDS.includes(m.id)
  const regular = filtered.filter((m) => !isAdminShell(m))
  const adminShell = filtered.filter((m) => isAdminShell(m))

  // Admins use the Admin portal hub — drop duplicate shell links from the main nav.
  if (canAccessAdminPortal.value && !isImpersonating.value) {
    return regular
  }

  // Non-admins who still have shell modules (e.g. configuration only) keep them at the end.
  return [...regular, ...adminShell]
})

const learnerNavModules = computed(() => {
  const preferredOrder = [
    'dashboard',
    'homework',
    'marks',
    'attendance',
    'timetable',
    'notifications',
    'fees',
    'online',
    'leave',
  ]
  const byId = Object.fromEntries(
    modules.items
      .filter((m) => m.enabled !== false && (m.platforms || ['web']).includes('web'))
      .map((m) => [m.id, m]),
  )
  return preferredOrder
    .map((id) => byId[id])
    .filter(Boolean)
})

function staffNavLabel(mod) {
  if (mod.id === 'dashboard') return 'Dashboard'
  if (mod.id === 'online') return 'Online'
  return mod.label
}

function learnerFallbackRoute(id) {
  const map = {
    dashboard: '/dashboard',
    homework: '/homework',
    marks: '/marks',
    attendance: '/attendance',
    timetable: '/timetable',
    notifications: '/notifications',
    fees: '/fees',
    online: '/online-classes',
    leave: '/leave',
  }
  return map[id] || '/home'
}

async function ensureModules() {
  if (!auth.token) return
  try {
    await modules.fetchModules('web')
  } catch {
    /* ignore — empty nav until retry */
  }
}

onMounted(async () => {
  if (auth.token) {
    await ensureModules()
  }
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

watch(
  () => [viewAs.role, viewAs.impersonateUser?.id, auth.token],
  async ([, , token]) => {
    if (!token) {
      modules.clear()
      return
    }
    await ensureModules()
  },
)

async function onViewAsChange(event) {
  const roleName = event.target.value
  viewAs.setRole(roleName)
  await parent.clearChild()
  await ensureModules()
  if (roleName === 'parent' || roleName === 'student') {
    try {
      await parent.loadDashboard()
    } catch {
      /* learner may have no linked data */
    }
  }
  router.push('/home')
}

async function exitImpersonation() {
  viewAs.stopImpersonation()
  await parent.clearChild()
  await ensureModules()
  router.push('/admin')
}

async function logout() {
  await auth.logout()
  router.push('/login')
}

function openNotifications() {
  router.push('/notifications')
}

async function switchChild() {
  await parent.clearChild()
  router.push('/home')
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
.top a.nav-soon {
  color: #71717a;
  opacity: 0.75;
}
.top a.nav-soon.router-link-active {
  color: #a1a1aa;
}
.top a.nav-admin {
  margin-left: 0.25rem;
  padding-left: 0.85rem;
  border-left: 1px solid #3f3f46;
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
.notif-btn {
  position: relative;
  width: 1.75rem;
  height: 1.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.notif-badge {
  position: absolute;
  top: -0.2rem;
  right: -0.35rem;
  min-width: 0.9rem;
  height: 0.9rem;
  padding: 0 0.2rem;
  border-radius: 999px;
  background: #dc2626;
  color: #fff;
  font-size: 0.62rem;
  line-height: 0.9rem;
  font-weight: 700;
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
.link.accent {
  color: #fbbf24;
  white-space: nowrap;
}
main {
  max-width: 960px;
  margin: 0 auto;
  padding: 1.5rem;
}
main.main-flush {
  max-width: none;
  margin: 0;
  padding: 0;
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
