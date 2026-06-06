<template>
  <div>
    <h1>Attendance</h1>
    <div class="card">
      <label>Study group
        <select v-model="academic.studyGroupId">
          <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
        </select>
      </label>
      <label>Date <input v-model="date" type="date" /></label>
      <button type="button" class="primary" :disabled="loading" @click="loadStudents">Load students</button>
    </div>
    <div v-if="students.length" class="card">
      <div v-for="s in students" :key="s.id" class="row-student">
        <span>{{ s.first_name }} {{ s.last_name }}</span>
        <select v-model="statuses[s.id]">
          <option value="present">Present</option>
          <option value="absent">Absent</option>
          <option value="late">Late</option>
          <option value="excused">Excused</option>
        </select>
      </div>
      <button type="button" class="primary" @click="save">Save attendance</button>
      <p v-if="msg" class="ok">{{ msg }}</p>
      <p v-if="err" class="error">{{ err }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import api from '../api/client'
import { useAcademic } from '../composables/useAcademic'

import { todayInputDate } from '../composables/format'

const academic = useAcademic()
const date = ref(todayInputDate())
const students = ref([])
const statuses = reactive({})
const loading = ref(false)
const msg = ref('')
const err = ref('')

async function loadStudents() {
  loading.value = true
  err.value = ''
  try {
    const { data } = await api.get('/efsc/students', {
      params: { study_group_id: academic.studyGroupId },
    })
    students.value = data
    for (const s of data) statuses[s.id] = 'present'
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load students'
  } finally {
    loading.value = false
  }
}

async function save() {
  err.value = ''
  msg.value = ''
  try {
    await api.post('/efsc/attendance/batches', {
      study_group_id: Number(academic.studyGroupId),
      date: date.value,
      records: students.value.map((s) => ({ student_id: s.id, status: statuses[s.id] })),
    })
    msg.value = 'Saved — awaiting section head verification.'
  } catch (e) {
    err.value = e.response?.data?.message || 'Save failed'
  }
}
</script>

<style scoped>
.row-student {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.35rem 0;
  border-bottom: 1px solid #eee;
}
.ok { color: #15803d; }
</style>
