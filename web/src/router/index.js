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
import AttendanceView from '../views/AttendanceView.vue'
import MarksView from '../views/MarksView.vue'
import HomeworkView from '../views/HomeworkView.vue'
import OnlineClassView from '../views/OnlineClassView.vue'
import LeaveView from '../views/LeaveView.vue'
import NotificationsView from '../views/NotificationsView.vue'
import ComingSoonView from '../views/ComingSoonView.vue'
import PermissionsAdminView from '../views/admin/PermissionsAdminView.vue'
import AcademicConfigView from '../views/admin/AcademicConfigView.vue'
import UsersAdminView from '../views/admin/UsersAdminView.vue'
import ParentHomeworkView from '../views/parent/ParentHomeworkView.vue'
import ParentMarksView from '../views/parent/ParentMarksView.vue'
import ParentAttendanceView from '../views/parent/ParentAttendanceView.vue'
import ParentTimetableView from '../views/parent/ParentTimetableView.vue'
import ParentNotificationsView from '../views/parent/ParentNotificationsView.vue'
import ParentFeesView from '../views/parent/ParentFeesView.vue'
import ParentOnlineClassView from '../views/parent/ParentOnlineClassView.vue'
import ParentLeaveView from '../views/parent/ParentLeaveView.vue'

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
      component: roleView(ParentAttendanceView, AttendanceView),
      meta: { auth: true, requiresChild: true },
    },
    { path: '/marks', component: roleView(ParentMarksView, MarksView), meta: { auth: true, requiresChild: true } },
    {
      path: '/homework',
      component: roleView(ParentHomeworkView, HomeworkView),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/timetable',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Timetable' },
    },
    {
      path: '/online-classes',
      component: roleView(ParentOnlineClassView, OnlineClassView),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/fees',
      component: ComingSoonView,
      meta: { auth: true, requiresChild: true },
      props: { title: 'Fee vouchers' },
    },
    {
      path: '/notifications',
      component: roleView(ParentNotificationsView, NotificationsView),
      meta: { auth: true, requiresChild: true },
    },
    { path: '/leave', component: roleView(ParentLeaveView, LeaveView), meta: { auth: true, requiresChild: true } },
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
