<template>
  <div>
    <h1>Timetable & datesheet</h1>
    <ClassSectionPicker
      v-model:class-id="academic.classId"
      v-model:section-id="academic.sectionId"
      :classes="academic.classes"
      :sections="academic.sections()"
      @class-change="academic.onClassChange"
    />
    <div class="card">
      <h2>Add timetable slot</h2>
      <label>Day (0=Sun) <input v-model.number="slot.day_of_week" type="number" min="0" max="6" /></label>
      <label>Start <input v-model="slot.start_time" type="time" /></label>
      <label>End <input v-model="slot.end_time" type="time" /></label>
      <label>Room <input v-model="slot.room" /></label>
      <button type="button" class="primary" @click="addSlot">Add slot</button>
    </div>
    <div class="card">
      <h2>Add datesheet entry</h2>
      <label>Title <input v-model="exam.title" /></label>
      <label>Exam date <input v-model="exam.exam_date" type="date" /></label>
      <label>Notes <input v-model="exam.notes" /></label>
      <label><input v-model="exam.notify_parents" type="checkbox" /> Notify parents (approval)</label>
      <button type="button" class="primary" @click="addExam">Add exam</button>
    </div>
    <p v-if="msg" class="ok">{{ msg }}</p>
    <p v-if="err" class="error">{{ err }}</p>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import api from '../api/client'
import { useAcademic } from '../composables/useAcademic'
import ClassSectionPicker from '../components/ClassSectionPicker.vue'

const academic = useAcademic()
const slot = reactive({ day_of_week: 1, start_time: '09:00', end_time: '09:45', room: '' })
const exam = reactive({ title: '', exam_date: '', notes: '', notify_parents: false })
const msg = ref('')
const err = ref('')

async function addSlot() {
  try {
    await api.post('/prism/timetable/slots', {
      school_class_id: Number(academic.classId),
      section_id: Number(academic.sectionId),
      day_of_week: slot.day_of_week,
      start_time: slot.start_time,
      end_time: slot.end_time,
      room: slot.room,
    })
    msg.value = 'Slot added.'
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed'
  }
}

async function addExam() {
  try {
    await api.post('/prism/timetable/datesheet', {
      title: exam.title,
      exam_date: exam.exam_date,
      school_class_id: Number(academic.classId),
      notes: exam.notes,
      notify_parents: exam.notify_parents,
    })
    msg.value = exam.notify_parents ? 'Exam added; notify queued.' : 'Exam added.'
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed'
  }
}
</script>

<style scoped>
.ok { color: #15803d; }
</style>
