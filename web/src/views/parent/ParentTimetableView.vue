<template>
  <div>
    <h1>Timetable</h1>
    <p v-if="child" class="muted">For {{ childName(child) }}</p>
    <div class="card">
      <h2>Weekly slots</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="err" class="error">{{ err }}</p>
      <div v-for="slot in slots" :key="slot.id" class="item">
        <strong>{{ slot.subject?.name || 'Period' }}</strong>
        <span class="muted">
          · {{ dayNames[slot.day_of_week] || slot.day_of_week }}
          · {{ formatTime(slot.start_time) }}–{{ formatTime(slot.end_time) }}
        </span>
        <p v-if="slot.room" class="muted small">Room {{ slot.room }}</p>
      </div>
    </div>
    <div class="card">
      <h2>Exam datesheet</h2>
      <p v-if="datesheet.length === 0" class="muted">No upcoming exams.</p>
      <div v-for="e in datesheet" :key="e.id" class="item">
        <strong>{{ e.title }}</strong>
        <span class="muted"> · {{ formatDate(e.exam_date) }}</span>
        <p v-if="e.notes">{{ e.notes }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../../api/client'
import { useParentStore } from '../../stores/parent'
import { childName, formatDate, formatTime, paginated } from '../../composables/format'

const parent = useParentStore()
const child = computed(() => parent.selectedChild)
const slots = ref([])
const datesheet = ref([])
const loading = ref(true)
const err = ref('')
const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

onMounted(async () => {
  err.value = ''
  try {
    const [s, d] = await Promise.all([
      api.get('/prism/timetable/slots', { params: { per_page: 50 } }),
      api.get('/prism/timetable/datesheet', { params: { per_page: 30 } }),
    ])
    slots.value = paginated(s.data)
    datesheet.value = paginated(d.data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.item { padding: 0.5rem 0; border-bottom: 1px solid #f4f4f5; }
.muted { color: #71717a; font-size: 0.9rem; }
.small { font-size: 0.8rem; }
</style>
