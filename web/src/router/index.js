import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useParentStore } from '../stores/parent'
import { useRoles } from '../composables/useRoles'
import { roleView } from '../composables/useRoleView'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import ParentHomeView from '../views/ParentHomeView.vue'
import ChildDashboardView from '../views/ChildDashboardView.vue'
import ApprovalsView from '../views/ApprovalsView.vue'
import AttendanceView from '../views/AttendanceView.vue'
import MarksView from '../views/MarksView.vue'
import HomeworkView from '../views/HomeworkView.vue'
import TimetableView from '../views/TimetableView.vue'
import OnlineClassView from '../views/OnlineClassView.vue'
import FeeView from '../views/FeeView.vue'
import FeedView from '../views/FeedView.vue'
import LeaveView from '../views/LeaveView.vue'
import ParentHomeworkView from '../views/parent/ParentHomeworkView.vue'
import ParentMarksView from '../views/parent/ParentMarksView.vue'
import ParentAttendanceView from '../views/parent/ParentAttendanceView.vue'
import ParentTimetableView from '../views/parent/ParentTimetableView.vue'
import ParentFeedView from '../views/parent/ParentFeedView.vue'
import ParentFeesView from '../views/parent/ParentFeesView.vue'
import ParentOnlineClassView from '../views/parent/ParentOnlineClassView.vue'
import ParentLeaveView from '../views/parent/ParentLeaveView.vue'
import ParentAlertsView from '../views/parent/ParentAlertsView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: LoginView, meta: { guest: true } },
    { path: '/', component: roleView(ParentHomeView, DashboardView), meta: { auth: true } },
    { path: '/dashboard', component: ChildDashboardView, meta: { auth: true, requiresChild: true } },
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
      component: roleView(ParentTimetableView, TimetableView),
      meta: { auth: true, requiresChild: true },
    },
    {
      path: '/online-classes',
      component: roleView(ParentOnlineClassView, OnlineClassView),
      meta: { auth: true, requiresChild: true },
    },
    { path: '/fees', component: roleView(ParentFeesView, FeeView), meta: { auth: true, requiresChild: true } },
    { path: '/feed', component: roleView(ParentFeedView, FeedView), meta: { auth: true, requiresChild: true } },
    { path: '/leave', component: roleView(ParentLeaveView, LeaveView), meta: { auth: true, requiresChild: true } },
    { path: '/alerts', component: ParentAlertsView, meta: { auth: true, parentOnly: true, requiresChild: true } },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.auth && !auth.token) return '/login'
  if (to.meta.guest && auth.token) return '/'

  const { isParent } = useRoles()

  if (to.meta.staffOnly && isParent.value) return '/'
  if (to.meta.parentOnly && !isParent.value) return '/'

  if (to.meta.requiresChild && isParent.value) {
    const parent = useParentStore()
    if (!parent.selectedChild) return '/'
  }
})

export default router
