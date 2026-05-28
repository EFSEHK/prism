<template>
  <div>
    <div v-if="child" class="card child-banner">
      <ChildAvatar :student="child" selected />
      <div>
        <h1 class="child-name">{{ childName(child) }}</h1>
        <p class="muted">
          {{ child.school_class?.name || '' }}
          <template v-if="child.section?.name"> · Section {{ child.section.name }}</template>
        </p>
        <p v-if="child.admission_no" class="muted small">Admission {{ child.admission_no }}</p>
      </div>
    </div>

    <div class="hero card">
      <p class="hero-greeting">Dashboard</p>
      <p class="hero-name">{{ user?.name || 'Parent' }}</p>
      <p class="hero-meta">
        {{
          unread > 0
            ? `${unread} unread notification${unread === 1 ? '' : 's'}`
            : 'All caught up'
        }}
      </p>
    </div>

    <div class="card">
      <h2>Today</h2>
      <p v-if="timetable.length === 0" class="muted">No classes today.</p>
      <div v-for="slot in timetable" :key="slot.id" class="item">
        <strong>{{ slot.subject?.name || 'Period' }}</strong>
        <span class="muted">
          {{ formatTime(slot.start_time) }}
          <template v-if="slot.end_time"> – {{ formatTime(slot.end_time) }}</template>
        </span>
      </div>
    </div>

    <div class="card">
      <h2>Recent homework</h2>
      <p v-if="homework.length === 0" class="muted">No homework.</p>
      <div v-for="h in homework.slice(0, 3)" :key="h.id" class="item">
        <strong>{{ h.title }}</strong>
        <span v-if="h.subject?.name" class="muted"> · {{ h.subject.name }}</span>
        <p v-if="h.due_date" class="muted small">Due {{ formatDate(h.due_date) }}</p>
      </div>
      <RouterLink v-if="homework.length" to="/homework" class="link-more">View all homework</RouterLink>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useParentStore } from '../stores/parent'
import ChildAvatar from '../components/ChildAvatar.vue'
import { childName, formatDate, formatTime } from '../composables/format'

const auth = useAuthStore()
const parent = useParentStore()

const user = computed(() => auth.user)
const child = computed(() => parent.selectedChild)
const unread = computed(() => parent.dashboard?.unread_notifications ?? 0)
const timetable = computed(() => parent.dashboard?.timetable_today ?? [])
const homework = computed(() => parent.dashboard?.homework ?? [])
</script>

<style scoped>
.child-banner {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.child-name {
  margin: 0;
  font-size: 1.25rem;
}
.hero {
  background: linear-gradient(135deg, #1e40af, #2563eb);
  color: #fff;
  border: none;
}
.hero-greeting {
  margin: 0;
  font-size: 0.85rem;
  color: #bfdbfe;
}
.hero-name {
  margin: 0.25rem 0 0;
  font-size: 1.35rem;
  font-weight: 700;
}
.hero-meta {
  margin: 0.5rem 0 0;
  font-size: 0.9rem;
  color: #dbeafe;
}
.item {
  padding: 0.5rem 0;
  border-bottom: 1px solid #f4f4f5;
}
.item:last-child {
  border-bottom: none;
}
.muted {
  color: #71717a;
  font-size: 0.9rem;
}
.small {
  font-size: 0.8rem;
  margin: 0.15rem 0 0;
}
.link-more {
  display: inline-block;
  margin-top: 0.75rem;
  color: #2563eb;
  font-weight: 600;
  text-decoration: none;
}
</style>
