<template>
  <div>
    <h1>Timetable & datesheet</h1>
    <p class="muted">Manage weekly slots and exam datesheet entries.</p>

    <div class="tabs">
      <button type="button" :class="{ active: tab === 'slots' }" @click="tab = 'slots'">Weekly slots</button>
      <button type="button" :class="{ active: tab === 'datesheet' }" @click="tab = 'datesheet'">Datesheet</button>
    </div>

    <div v-if="msg" class="ok">{{ msg }}</div>
    <div v-if="err" class="error">{{ err }}</div>

    <section v-if="tab === 'slots'" class="card">
      <h2>Add timetable slot</h2>
      <label>Study group
        <select v-model="academic.studyGroupId">
          <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
        </select>
      </label>
      <label>Subject
        <select v-model="slot.subject_id">
          <option value="">—</option>
          <option v-for="s in academic.subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </label>
      <label>Day
        <select v-model.number="slot.day_of_week">
          <option v-for="(d, i) in days" :key="i" :value="i">{{ d }}</option>
        </select>
      </label>
      <label>Start <input v-model="slot.start_time" type="time" /></label>
      <label>End <input v-model="slot.end_time" type="time" /></label>
      <label>Room <input v-model="slot.room" /></label>
      <button type="button" class="primary" :disabled="saving" @click="addSlot">Add slot</button>

      <h2 class="mt">Existing slots</h2>
      <p v-if="slotsLoading">Loading…</p>
      <p v-else-if="!slots.length" class="empty">No slots for this study group.</p>
      <div v-for="s in slots" :key="s.id" class="item">
        <strong>{{ days[s.day_of_week] || s.day_of_week }}</strong>
        {{ formatTime(s.start_time) }}–{{ formatTime(s.end_time) }}
        — {{ s.subject?.name || 'Period' }}
        <span v-if="s.room" class="muted"> · Room {{ s.room }}</span>
      </div>
    </section>

    <section v-if="tab === 'datesheet'" class="card">
      <h2>Add datesheet entry</h2>
      <label>Title <input v-model="exam.title" /></label>
      <label>Exam date <input v-model="exam.exam_date" type="date" /></label>
      <label>Class
        <select v-model="exam.school_class_id">
          <option value="">All / none</option>
          <option v-for="c in academic.classes" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </label>
      <label>Subject
        <select v-model="exam.subject_id">
          <option value="">—</option>
          <option v-for="s in academic.subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </label>
      <label>Notes <input v-model="exam.notes" /></label>
      <label><input v-model="exam.notify_parents" type="checkbox" /> Notify parents (approval)</label>
      <button type="button" class="primary" :disabled="saving" @click="addExam">Add exam</button>

      <h2 class="mt">Upcoming datesheet</h2>
      <p v-if="examLoading">Loading…</p>
      <p v-else-if="!exams.length" class="empty">No datesheet entries.</p>
      <div v-for="e in exams" :key="e.id" class="item">
        <strong>{{ e.title }}</strong> — {{ formatDate(e.exam_date) }}
        <span v-if="e.school_class?.name" class="muted"> · {{ e.school_class.name }}</span>
        <span v-if="e.subject?.name" class="muted"> · {{ e.subject.name }}</span>
        <p v-if="e.notes" class="muted">{{ e.notes }}</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue'
import api from '../api/client'
import { useAcademic } from '../composables/useAcademic'
import { formatDate, paginated } from '../composables/format'

const academic = useAcademic()
const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const tab = ref('slots')
const slot = reactive({ day_of_week: 1, start_time: '09:00', end_time: '09:45', room: '', subject_id: '' })
const exam = reactive({ title: '', exam_date: '', notes: '', notify_parents: false, school_class_id: '', subject_id: '' })
const slots = ref([])
const exams = ref([])
const slotsLoading = ref(false)
const examLoading = ref(false)
const saving = ref(false)
const msg = ref('')
const err = ref('')

function formatTime(t) {
  if (!t) return ''
  return String(t).slice(0, 5)
}

async function loadSlots() {
  slotsLoading.value = true
  err.value = ''
  try {
    const params = { per_page: 50 }
    if (academic.studyGroupId) params.study_group_id = academic.studyGroupId
    const { data } = await api.get('/efsc/timetable/slots', { params })
    slots.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load slots'
    slots.value = []
  } finally {
    slotsLoading.value = false
  }
}

async function loadExams() {
  examLoading.value = true
  err.value = ''
  try {
    const { data } = await api.get('/efsc/timetable/datesheet', { params: { per_page: 50 } })
    exams.value = paginated(data)
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load datesheet'
    exams.value = []
  } finally {
    examLoading.value = false
  }
}

async function addSlot() {
  msg.value = ''
  err.value = ''
  if (!academic.studyGroupId) {
    err.value = 'Select a study group.'
    return
  }
  saving.value = true
  try {
    await api.post('/efsc/timetable/slots', {
      study_group_id: Number(academic.studyGroupId),
      subject_id: slot.subject_id || null,
      day_of_week: slot.day_of_week,
      start_time: slot.start_time,
      end_time: slot.end_time,
      room: slot.room || null,
    })
    msg.value = 'Slot added.'
    await loadSlots()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to add slot'
  } finally {
    saving.value = false
  }
}

async function addExam() {
  msg.value = ''
  err.value = ''
  if (!exam.title || !exam.exam_date) {
    err.value = 'Title and exam date are required.'
    return
  }
  saving.value = true
  try {
    await api.post('/efsc/timetable/datesheet', {
      title: exam.title,
      exam_date: exam.exam_date,
      school_class_id: exam.school_class_id || null,
      subject_id: exam.subject_id || null,
      notes: exam.notes || null,
      notify_parents: exam.notify_parents,
    })
    msg.value = exam.notify_parents ? 'Exam added; parent notify queued.' : 'Exam added.'
    exam.title = ''
    exam.exam_date = ''
    exam.notes = ''
    exam.notify_parents = false
    await loadExams()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to add exam'
  } finally {
    saving.value = false
  }
}

watch(() => academic.studyGroupId, loadSlots)
watch(tab, (t) => {
  if (t === 'slots') loadSlots()
  else loadExams()
})
onMounted(() => {
  loadSlots()
  loadExams()
})
</script>

<style scoped>
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.tabs button { padding: 0.4rem 0.85rem; border: 1px solid #e4e4e7; background: #fff; border-radius: 6px; cursor: pointer; }
.tabs button.active { background: #0f766e; color: #fff; border-color: #0f766e; }
.item { padding: 0.5rem 0; border-bottom: 1px solid #f4f4f5; }
.mt { margin-top: 1.25rem; }
.muted { color: #71717a; }
.empty { color: #a1a1aa; }
.ok { color: #15803d; }
.error { color: #b91c1c; }
</style>
