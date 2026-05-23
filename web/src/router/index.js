import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import ApprovalsView from '../views/ApprovalsView.vue'
import AttendanceView from '../views/AttendanceView.vue'
import MarksView from '../views/MarksView.vue'
import HomeworkView from '../views/HomeworkView.vue'
import TimetableView from '../views/TimetableView.vue'
import OnlineClassView from '../views/OnlineClassView.vue'
import FeeView from '../views/FeeView.vue'
import FeedView from '../views/FeedView.vue'
import LeaveView from '../views/LeaveView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: LoginView, meta: { guest: true } },
    { path: '/', component: DashboardView, meta: { auth: true } },
    { path: '/approvals', component: ApprovalsView, meta: { auth: true } },
    { path: '/attendance', component: AttendanceView, meta: { auth: true } },
    { path: '/marks', component: MarksView, meta: { auth: true } },
    { path: '/homework', component: HomeworkView, meta: { auth: true } },
    { path: '/timetable', component: TimetableView, meta: { auth: true } },
    { path: '/online-classes', component: OnlineClassView, meta: { auth: true } },
    { path: '/fees', component: FeeView, meta: { auth: true } },
    { path: '/feed', component: FeedView, meta: { auth: true } },
    { path: '/leave', component: LeaveView, meta: { auth: true } },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.auth && !auth.token) return '/login'
  if (to.meta.guest && auth.token) return '/'
})

export default router
