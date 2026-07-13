import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useParentStore } from '../stores/parent'
import { useRoles } from '../composables/useRoles'
import { usePermissions } from '../composables/usePermissions'
import { useViewAsStore } from '../stores/viewAs'
import { roleView } from '../composables/useRoleView'
import LandingView from '../views/LandingView.vue'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import ParentHomeView from '../views/ParentHomeView.vue'
import ChildDashboardView from '../views/ChildDashboardView.vue'
import ApprovalsView from '../views/ApprovalsView.vue'
import ComingSoonView from '../views/ComingSoonView.vue'
import PermissionsAdminView from '../views/admin/PermissionsAdminView.vue'
import AcademicConfigView from '../views/admin/AcademicConfigView.vue'
import UsersAdminView from '../views/admin/UsersAdminView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: LandingView, meta: { public: true } },
    { path: '/login', component: LoginView, meta: { guest: true } },
    { path: '/home', component: roleView(ParentHomeView, DashboardView), meta: { auth: true } },
    { path: '/dashboard', component: ChildDashboardView, meta: { auth: true, requiresChild: true } },
    { path: '/admin/users', component: UsersAdminView, meta: { auth: true, usersAccess: true } },
    { path: '/admin/permissions', component: PermissionsAdminView, meta: { auth: true, superadminOnly: true } },
    { path: '/admin/academic', component: AcademicConfigView, meta: { auth: true, configAccess: true } },
    { path: '/approvals', component: ApprovalsView, meta: { auth: true, staffOnly: true } },
    {
      path: '/attendance',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Attendance' },
    },
    {
      path: '/marks',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Marks' },
    },
    {
      path: '/homework',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Homework' },
    },
    {
      path: '/timetable',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Timetable' },
    },
    {
      path: '/online-classes',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Online' },
    },
    {
      path: '/fees',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Fee vouchers' },
    },
    {
      path: '/notifications',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Notifications' },
    },
    {
      path: '/leave',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Leave' },
    },
    { path: '/alerts', redirect: '/notifications' },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.auth && !auth.token) return '/login'
  if (to.meta.guest && auth.token) return '/home'
  if (to.meta.public && auth.token && to.path === '/') return '/home'

  const viewAs = useViewAsStore()
  const { isLearner, isParent, isSuperadmin, canManageUsers } = useRoles()

  if (viewAs.isImpersonating && to.path.startsWith('/admin')) return '/home'
  if (to.meta.usersAccess && !canManageUsers.value) return '/home'
  if (to.meta.superadminOnly && !isSuperadmin.value) return '/home'
  if (to.meta.configAccess) {
    const { canConfigure } = usePermissions()
    if (!canConfigure.value) return '/home'
  }
  if (to.meta.staffOnly && isLearner.value) return '/home'
  if (to.meta.parentOnly && !isParent.value) return '/home'

  if (to.meta.requiresChild && isParent.value) {
    const parent = useParentStore()
    if (!parent.selectedChild) return '/home'
  }
})

export default router
