<template>
  <div>
    <h1>Attendance</h1>
    <p v-if="academic.error" class="error">{{ academic.error }}</p>
    <ClassSectionPicker
      v-model:class-id="academic.classId"
      v-model:section-id="academic.sectionId"
      :classes="academic.classes"
      :sections="academic.sections()"
      @class-change="academic.onClassChange"
    />
    <div class="card">
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
import ClassSectionPicker from '../components/ClassSectionPicker.vue'

const academic = useAcademic()
const date = ref(new Date().toISOString().slice(0, 10))
const students = ref([])
const statuses = reactive({})
const loading = ref(false)
const msg = ref('')
const err = ref('')

async function loadStudents() {
  loading.value = true
  err.value = ''
  try {
    const { data } = await api.get('/prism/students', {
      params: { school_class_id: academic.classId, section_id: academic.sectionId },
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
    await api.post('/prism/attendance/batches', {
      school_class_id: Number(academic.classId),
      section_id: Number(academic.sectionId),
      date: date.value,
      records: students.value.map((s) => ({ student_id: s.id, status: statuses[s.id] })),
    })
    msg.value = 'Saved. Absent alerts queued for approval if applicable.'
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
