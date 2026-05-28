<template>
  <div>
    <div class="hero card">
      <p class="hero-greeting">Welcome back</p>
      <h1>{{ user?.name || 'Parent' }}</h1>
      <p class="hero-meta">
        {{
          unread > 0
            ? `${unread} unread notification${unread === 1 ? '' : 's'}`
            : 'Select a child to view their dashboard'
        }}
      </p>
    </div>

    <div class="card">
      <h2>Select a child</h2>
      <p v-if="loading" class="muted">Loading…</p>
      <p v-else-if="children.length === 0" class="muted">No linked students.</p>
      <div v-else class="child-row">
        <ChildAvatar
          v-for="child in children"
          :key="child.id"
          :student="child"
          :on-select="onSelectChild"
        />
      </div>
    </div>

    <div class="card">
      <h2>General announcements</h2>
      <p class="muted section-hint">Institute-wide updates for all families.</p>
      <p v-if="announcements.length === 0" class="muted">No institute announcements.</p>
      <article v-for="a in announcements" :key="a.id" class="announcement">
        <strong>{{ a.title }}</strong>
        <span v-if="a.published_at" class="muted"> · {{ formatDate(a.published_at) }}</span>
        <p v-if="a.body" class="body">{{ a.body }}</p>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useParentStore } from '../stores/parent'
import ChildAvatar from '../components/ChildAvatar.vue'
import { formatDate } from '../composables/format'

const auth = useAuthStore()
const parent = useParentStore()
const router = useRouter()

const user = computed(() => auth.user)
const loading = computed(() => parent.loading)
const children = computed(() => parent.dashboard?.children ?? [])
const announcements = computed(() => parent.dashboard?.school_announcements ?? [])
const unread = computed(() => parent.dashboard?.unread_notifications ?? 0)

onMounted(async () => {
  if (!parent.dashboard) {
    await parent.loadDashboard()
  }
})

async function onSelectChild(child) {
  await parent.selectChild(child)
  router.push('/dashboard')
}
</script>

<style scoped>
.hero {
  background: linear-gradient(135deg, #1e40af, #2563eb);
  color: #fff;
  border: none;
}
.hero h1 {
  margin: 0.25rem 0 0;
  font-size: 1.5rem;
}
.hero-greeting {
  margin: 0;
  font-size: 0.85rem;
  color: #bfdbfe;
}
.hero-meta {
  margin: 0.5rem 0 0;
  font-size: 0.9rem;
  color: #dbeafe;
}
.child-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  padding: 0.5rem 0;
}
.section-hint {
  margin-top: 0;
}
.announcement {
  padding: 0.75rem 0;
  border-bottom: 1px solid #e4e4e7;
}
.announcement:last-child {
  border-bottom: none;
}
.body {
  margin: 0.35rem 0 0;
  color: #3f3f46;
  line-height: 1.45;
}
.muted {
  color: #71717a;
  font-size: 0.9rem;
}
</style>
