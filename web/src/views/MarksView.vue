<template>
  <div>
    <h1>Marks</h1>
    <label>Study group
      <select v-model="academic.studyGroupId">
        <option v-for="g in academic.studyGroups" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
      </select>
    </label>
    <div class="card">
      <h2>Mark sheets</h2>
      <p v-if="loading">Loading…</p>
      <div v-for="m in sheets" :key="m.id" class="item">
        <strong>{{ m.subject?.name }}</strong> — {{ m.assessment?.name }}
        <button type="button" class="primary small" @click="notify(m.id)">Notify parents</button>
        <button type="button" class="secondary small" @click="openSheet(m.id)">Entries</button>
      </div>
    </div>
    <div v-if="activeSheet" class="card">
      <h2>Entries — sheet #{{ activeSheet }}</h2>
      <div v-for="s in roster" :key="s.id" class="row-student">
        <span>{{ s.first_name }} {{ s.last_name }}</span>
        <input v-model="entries[s.id].marks" placeholder="Marks" style="width: 60px" />
        <input v-model="entries[s.id].max" placeholder="Max" style="width: 60px" />
        <input v-model="entries[s.id].grade" placeholder="Grade" style="width: 50px" />
      </div>
      <button type="button" class="primary" @click="saveEntries">Save entries</button>
    </div>
    <p v-if="msg" class="ok">{{ msg }}</p>
    <p v-if="err" class="error">{{ err }}</p>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import api from '../api/client'
import { useAcademic } from '../composables/useAcademic'

const academic = useAcademic()
const sheets = ref([])
const loading = ref(false)
const activeSheet = ref(null)
const roster = ref([])
const entries = reactive({})
const msg = ref('')
const err = ref('')

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/efsc/mark-sheets', {
      params: { per_page: 30 },
    })
    sheets.value = data?.data ?? data ?? []
  } finally {
    loading.value = false
  }
}

async function openSheet(id) {
  activeSheet.value = id
  const { data: st } = await api.get('/efsc/students', {
    params: { study_group_id: academic.studyGroupId },
  })
  roster.value = st.data || st
  for (const s of roster.value) {
    entries[s.id] = { marks: '', max: '', grade: '' }
  }
}

async function saveEntries() {
  await api.post(`/efsc/mark-sheets/${activeSheet.value}/entries`, {
    entries: roster.value.map((s) => ({
      student_id: s.id,
      marks_obtained: entries[s.id].marks || null,
      max_marks: entries[s.id].max || null,
      grade: entries[s.id].grade || null,
    })),
  })
  msg.value = 'Entries saved.'
}

async function notify(id) {
  await api.post(`/efsc/mark-sheets/${id}/notify-parents`)
  msg.value = 'Parent notification queued for approval.'
}

watch(() => academic.studyGroupId, load)
onMounted(load)
</script>

<style scoped>
.item { padding: 0.5rem 0; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
.small { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
.row-student { display: flex; gap: 0.5rem; align-items: center; padding: 0.25rem 0; }
.ok { color: #15803d; }
</style>
