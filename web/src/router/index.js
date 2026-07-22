import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useParentStore } from '../stores/parent'
import { useRoles } from '../composables/useRoles'
import { usePermissions } from '../composables/usePermissions'
import { useViewAsStore } from '../stores/viewAs'
import { roleView } from '../composables/useRoleView'
import { catalogView } from '../composables/useCatalogView'
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
import TimetableView from '../views/TimetableView.vue'
import FeeView from '../views/FeeView.vue'
import PermissionsAdminView from '../views/admin/PermissionsAdminView.vue'
import AcademicConfigView from '../views/admin/AcademicConfigView.vue'
import UsersAdminView from '../views/admin/UsersAdminView.vue'
import AppsAdminView from '../views/admin/AppsAdminView.vue'
import AimsImportView from '../views/admin/AimsImportView.vue'
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
    { path: '/admin/apps', component: AppsAdminView, meta: { auth: true, appsAccess: true } },
    { path: '/admin/academic', component: AcademicConfigView, meta: { auth: true, configAccess: true } },
    { path: '/admin/aims-import', component: AimsImportView, meta: { auth: true, aimsImportAccess: true } },
    { path: '/approvals', component: ApprovalsView, meta: { auth: true, staffOnly: true } },
    {
      path: '/attendance',
      component: catalogView('attendance', roleView(ParentAttendanceView, AttendanceView), { title: 'Attendance' }),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/marks',
      component: catalogView('marks', roleView(ParentMarksView, MarksView), { title: 'Marks' }),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/homework',
      component: catalogView('homework', roleView(ParentHomeworkView, HomeworkView), { title: 'Homework' }),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/timetable',
      component: catalogView('timetable', roleView(ParentTimetableView, TimetableView), { title: 'Timetable' }),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/online-classes',
      component: catalogView('online', roleView(ParentOnlineClassView, OnlineClassView), { title: 'Online' }),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/fees',
      component: catalogView('fees', roleView(ParentFeesView, FeeView), { title: 'Fee vouchers' }),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/notifications',
      component: catalogView('notifications', roleView(ParentNotificationsView, NotificationsView), { title: 'Notifications' }),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/leave',
      component: catalogView('leave', roleView(ParentLeaveView, LeaveView), { title: 'Leave' }),
      meta: { auth: true, requiresChild: true },
    },
    { path: '/alerts', redirect: '/notifications' },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  const isPublic = to.meta.public || to.meta.guest
  if (!isPublic && !auth.token) return '/login'
  if (to.meta.guest && auth.token) return '/home'
  if (to.meta.public && auth.token && to.path === '/') return '/home'

  const viewAs = useViewAsStore()
  const { isLearner, isParent, isSuperadmin, canManageUsers, canManageApps } = useRoles()

  if (viewAs.isImpersonating && to.path.startsWith('/admin')) return '/home'
  if (to.meta.usersAccess && !canManageUsers.value) return '/home'
  if (to.meta.superadminOnly && !isSuperadmin.value) return '/home'
  if (to.meta.appsAccess && !canManageApps.value) return '/home'
  if (to.meta.configAccess) {
    const { canConfigure } = usePermissions()
    if (!canConfigure.value) return '/home'
  }
  if (to.meta.aimsImportAccess) {
    const { can } = usePermissions()
    if (!can('import_aims_data')) return '/home'
  }
  if (to.meta.staffOnly && isLearner.value) return '/home'
  if (to.meta.parentOnly && !isParent.value) return '/home'

  if (to.meta.requiresChild && isParent.value) {
    const parent = useParentStore()
    if (!parent.selectedChild) return '/home'
  }
})

export default router
