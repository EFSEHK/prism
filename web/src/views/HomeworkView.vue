<template>
  <div>
    <h1>Homework diary</h1>
    <ClassSectionPicker
      v-model:class-id="academic.classId"
      v-model:section-id="academic.sectionId"
      :classes="academic.classes"
      :sections="academic.sections()"
      @class-change="academic.onClassChange"
    />
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
        <strong>{{ h.title }}</strong> — {{ h.subject?.name }}
        <p class="muted">{{ h.body }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import api from '../api/client'
import { useAcademic, paginated } from '../composables/useAcademic'
import ClassSectionPicker from '../components/ClassSectionPicker.vue'

const academic = useAcademic()
const form = reactive({ title: '', body: '', due_date: '', subject_id: '' })
const items = ref([])
const loading = ref(false)
const msg = ref('')
const err = ref('')

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/prism/homework', {
      params: { school_class_id: academic.classId, section_id: academic.sectionId },
    })
    items.value = paginated(data)
  } finally {
    loading.value = false
  }
}

async function create() {
  err.value = ''
  msg.value = ''
  try {
    await api.post('/prism/homework', {
      school_class_id: Number(academic.classId),
      section_id: Number(academic.sectionId),
      subject_id: form.subject_id || null,
      title: form.title,
      body: form.body,
      due_date: form.due_date || null,
    })
    msg.value = 'Posted. Parent notification pending approval.'
    form.title = ''
    form.body = ''
    await load()
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed'
  }
}

watch([academic.classId, academic.sectionId], load)
onMounted(load)
</script>

<style scoped>
textarea { display: block; width: 100%; max-width: 480px; margin: 0.5rem 0 1rem; }
.item { padding: 0.5rem 0; border-bottom: 1px solid #eee; }
.muted { color: #71717a; font-size: 0.85rem; }
.ok { color: #15803d; }
</style>
