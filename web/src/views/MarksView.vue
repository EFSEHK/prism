<template>
  <div>
    <h1>Marks</h1>
    <ClassSectionPicker
      v-model:class-id="academic.classId"
      v-model:section-id="academic.sectionId"
      :classes="academic.classes"
      :sections="academic.sections()"
      @class-change="academic.onClassChange"
    />
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
import { useAcademic, paginated } from '../composables/useAcademic'
import ClassSectionPicker from '../components/ClassSectionPicker.vue'

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
    const { data } = await api.get('/prism/mark-sheets', {
      params: { per_page: 30 },
    })
    sheets.value = paginated(data)
  } finally {
    loading.value = false
  }
}

async function openSheet(id) {
  activeSheet.value = id
  const { data: st } = await api.get('/prism/students', {
    params: { school_class_id: academic.classId, section_id: academic.sectionId },
  })
  roster.value = st.data || st
  for (const s of roster.value) {
    entries[s.id] = { marks: '', max: '', grade: '' }
  }
}

async function saveEntries() {
  await api.post(`/prism/mark-sheets/${activeSheet.value}/entries`, {
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
  await api.post(`/prism/mark-sheets/${id}/notify-parents`)
  msg.value = 'Parent notification queued for approval.'
}

watch([() => academic.classId, () => academic.sectionId], load)
onMounted(load)
</script>

<style scoped>
.item { padding: 0.5rem 0; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
.small { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
.row-student { display: flex; gap: 0.5rem; align-items: center; padding: 0.25rem 0; }
.ok { color: #15803d; }
</style>
