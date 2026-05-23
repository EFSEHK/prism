<template>
  <div>
    <h1>Online classes</h1>
    <ClassSectionPicker
      v-model:class-id="academic.classId"
      v-model:section-id="academic.sectionId"
      :classes="academic.classes"
      :sections="academic.sections()"
      @class-change="academic.onClassChange"
    />
    <div class="card">
      <label>Label <input v-model="form.label" /></label>
      <label>URL <input v-model="form.url" /></label>
      <label>Start time <input v-model="form.start_time" type="time" /></label>
      <label>Minutes before reminder <input v-model.number="form.minutes_before" type="number" /></label>
      <label><input v-model="form.schedule_reminder" type="checkbox" /> Schedule reminder (approval)</label>
      <button type="button" class="primary" @click="create">Add link</button>
      <p v-if="msg" class="ok">{{ msg }}</p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import api from '../api/client'
import { useAcademic } from '../composables/useAcademic'
import ClassSectionPicker from '../components/ClassSectionPicker.vue'

const academic = useAcademic()
const form = reactive({
  label: 'Google Classroom',
  url: 'https://classroom.google.com',
  start_time: '10:00',
  minutes_before: 30,
  schedule_reminder: true,
})
const msg = ref('')

async function create() {
  await api.post('/prism/online-classes', {
    school_class_id: Number(academic.classId),
    section_id: Number(academic.sectionId),
    ...form,
  })
  msg.value = 'Link created.'
}
</script>

<style scoped>
.ok { color: #15803d; }
</style>
