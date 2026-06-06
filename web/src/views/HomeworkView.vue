<template>
  <div>
    <h1>Homework diary</h1>
    <label>Study group
      <select v-model="academic.studyGroupId">
        <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
      </select>
    </label>
    <div class="card">
      <h2>New post</h2>
      <label>Title <input v-model="form.title" /></label>
      <label>Body <textarea v-model="form.body" rows="3" /></label>
      <label>Due date <input v-model="form.due_date" type="date" /></label>
      <label>
        Subject
        <select v-model="form.subject_id">
          <option value="">—</option>
          <option v-for="s in academic.subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </label>
      <button type="button" class="primary" @click="create">Post homework</button>
      <p v-if="msg" class="ok">{{ msg }}</p>
      <p v-if="err" class="error">{{ err }}</p>
    </div>
    <div class="card">
      <h2>Recent</h2>
      <p v-if="loading">Loading…</p>
      <div v-for="h in items" :key="h.id" class="item">
        <strong>{{ h.title }}</strong> — {{ h.subject?.name }} ({{ h.status }})
        <p class="muted">{{ h.body }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import api from '../api/client'
import { useAcademic } from '../composables/useAcademic'

const academic = useAcademic()
const form = reactive({ title: '', body: '', due_date: '', subject_id: '' })
const items = ref([])
const loading = ref(false)
const msg = ref('')
const err = ref('')

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/efsc/homework', {
      params: { study_group_id: academic.studyGroupId },
    })
    items.value = data?.data ?? data ?? []
  } finally {
    loading.value = false
  }
}

async function create() {
  err.value = ''
  msg.value = ''
  try {
    await api.post('/efsc/homework', {
      study_group_id: Number(academic.studyGroupId),
      subject_id: form.subject_id || null,
      title: form.title,
      body: form.body,
      due_date: form.due_date || null,
    })
    msg.value = 'Posted — awaiting section head approval.'
    form.title = ''
    form.body = ''
    await load()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed'
  }
}

watch(() => academic.studyGroupId, load)
onMounted(load)
</script>

<style scoped>
.muted { color: #71717a; font-size: 0.9rem; }
.ok { color: #15803d; }
</style>
