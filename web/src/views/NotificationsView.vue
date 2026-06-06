<template>
  <div>
    <h1>Notifications</h1>
    <div class="card">
      <h2>Create broadcast</h2>
      <label>Audience
        <select v-model="form.audience_type">
          <option value="general">General (all parents & students)</option>
          <option value="scoped">Scoped (area/class/section/study group)</option>
          <option value="individual">Individual student</option>
        </select>
      </label>
      <label v-if="form.audience_type === 'individual'">Student ID
        <input v-model="form.student_id" type="number" />
      </label>
      <label v-if="form.audience_type === 'individual'">
        <input v-model="form.visible_to_student" type="checkbox" /> Show student
      </label>
      <label>Title <input v-model="form.title" required /></label>
      <label>Body <textarea v-model="form.body" rows="3" /></label>
      <button type="button" class="primary" @click="publish">Publish</button>
      <p v-if="err" class="error">{{ err }}</p>
    </div>
    <div class="card">
      <h2>Recent</h2>
      <ul>
        <li v-for="b in items" :key="b.id">{{ b.title }} — {{ b.audience_type }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../api/client'

const items = ref([])
const err = ref('')
const form = reactive({
  audience_type: 'general',
  student_id: '',
  visible_to_student: false,
  title: '',
  body: '',
})

onMounted(load)

async function load() {
  const { data } = await api.get('/efsc/broadcasts')
  items.value = data?.data ?? data ?? []
}

async function publish() {
  err.value = ''
  try {
    await api.post('/efsc/broadcasts', {
      ...form,
      student_id: form.student_id ? Number(form.student_id) : null,
      publish: true,
    })
    form.title = ''
    form.body = ''
    await load()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed'
  }
}
</script>
