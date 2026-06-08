<template>
  <div>
    <h1>Academic configuration</h1>
    <p class="muted">Set up session years, school structure, subjects, and student enrollment.</p>

    <div class="tabs">
      <button v-for="t in visibleTabs" :key="t.id" type="button" :class="{ active: tab === t.id }" @click="tab = t.id">
        {{ t.label }}
      </button>
    </div>

    <div v-if="err" class="error">{{ err }}</div>
    <div v-if="msg" class="ok">{{ msg }}</div>

    <!-- Session years -->
    <div v-if="tab === 'years'" class="card">
      <h2>Session years</h2>
      <p class="muted small">Define academic sessions and mark which one is active.</p>

      <form class="add-form" @submit.prevent="createYear">
        <div class="form-grid years-grid">
          <div class="field">
            <span class="field-label">Name</span>
            <input v-model="yearForm.name" required placeholder="2025–2026" />
          </div>
          <div class="field">
            <span class="field-label">Starts</span>
            <input v-model="yearForm.starts_on" type="date" required />
          </div>
          <div class="field">
            <span class="field-label">Ends</span>
            <input v-model="yearForm.ends_on" type="date" required />
          </div>
          <div class="field field-checkbox">
            <span class="field-label field-label-spacer" aria-hidden="true">&nbsp;</span>
            <label class="checkbox-field">
              <input v-model="yearForm.is_current" type="checkbox" />
              <span>Current session</span>
            </label>
          </div>
        </div>
        <div class="form-footer">
          <button type="submit" class="primary" :disabled="saving">
            {{ saving ? 'Adding…' : 'Add year' }}
          </button>
        </div>
      </form>

      <div v-if="years.length" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Period</th>
              <th class="col-status">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="y in years" :key="y.id">
              <td class="cell-name">{{ y.name }}</td>
              <td class="cell-period">{{ formatPeriod(y.starts_on, y.ends_on) }}</td>
              <td class="col-status">
                <span v-if="y.is_current" class="badge current">Current</span>
                <span v-else class="muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="empty">No session years yet. Add one above to get started.</p>
    </div>

    <!-- Structure -->
    <div v-if="tab === 'structure'" class="card">
      <h2>School structure</h2>
      <p class="muted small">Year → Area → Class → Section → Study group</p>

      <p v-if="!years.length" class="structure-hint">
        No session years found. Add one on the
        <button type="button" class="linkish" @click="tab = 'years'">Session years</button>
        tab first.
      </p>

      <div class="cascade">
        <div class="field cascade-field">
          <span class="field-label">Session year</span>
          <SearchableSelect
            v-model="structure.yearId"
            :options="yearOptions"
            placeholder="Select year…"
            search-placeholder="Search years…"
            empty-options-text="No session years yet"
            @change="onYearChange"
          />
        </div>
        <div class="field cascade-field">
          <span class="field-label">Area</span>
          <SearchableSelect v-model="structure.areaId" :options="areaOptions" placeholder="Select area…" search-placeholder="Search areas…" :disabled="!structure.yearId" @change="onAreaChange" />
        </div>
        <div class="field cascade-field">
          <span class="field-label">Class</span>
          <SearchableSelect v-model="structure.classId" :options="classOptions" placeholder="Select class…" search-placeholder="Search classes…" :disabled="!structure.areaId" @change="onClassChange" />
        </div>
        <div class="field cascade-field">
          <span class="field-label">Section</span>
          <SearchableSelect v-model="structure.sectionId" :options="sectionOptions" placeholder="Select section…" search-placeholder="Search sections…" :disabled="!structure.classId" @change="onSectionChange" />
        </div>
      </div>

      <div v-if="structure.yearId" class="panel">
        <h3>Areas</h3>
        <form class="add-form compact" @submit.prevent="createArea">
          <div class="form-grid">
            <div class="field grow">
              <span class="field-label">New area</span>
              <input v-model="areaForm.name" required placeholder="Primary" />
            </div>
            <div class="field field-action">
              <button type="submit" class="primary" :disabled="saving">Add area</button>
            </div>
          </div>
        </form>
        <div v-if="areas.length" class="tag-list">
          <span v-for="a in areas" :key="a.id" class="tag">{{ a.name }}</span>
        </div>
        <p v-else class="empty inline">No areas for this year yet.</p>
      </div>

      <div v-if="structure.areaId" class="panel">
        <h3>Classes</h3>
        <form class="add-form compact" @submit.prevent="createClass">
          <div class="form-grid">
            <div class="field grow">
              <span class="field-label">New class</span>
              <input v-model="classForm.name" required placeholder="Grade 5" />
            </div>
            <div class="field field-action">
              <button type="submit" class="primary" :disabled="saving">Add class</button>
            </div>
          </div>
        </form>
        <div v-if="classes.length" class="tag-list">
          <span v-for="c in classes" :key="c.id" class="tag">{{ c.name }}</span>
        </div>
        <p v-else class="empty inline">No classes in this area yet.</p>
      </div>

      <div v-if="structure.classId" class="panel">
        <h3>Sections</h3>
        <form class="add-form compact" @submit.prevent="createSection">
          <div class="form-grid">
            <div class="field grow">
              <span class="field-label">New section</span>
              <input v-model="sectionForm.name" required placeholder="A" />
            </div>
            <div class="field field-action">
              <button type="submit" class="primary" :disabled="saving">Add section</button>
            </div>
          </div>
        </form>
        <div v-if="sections.length" class="tag-list">
          <span v-for="s in sections" :key="s.id" class="tag">{{ s.name }}</span>
        </div>
        <p v-else class="empty inline">No sections in this class yet.</p>
      </div>

      <div v-if="structure.sectionId" class="panel">
        <h3>Study groups</h3>
        <form class="add-form compact" @submit.prevent="createStudyGroup">
          <div class="form-grid">
            <div class="field grow">
              <span class="field-label">New group</span>
              <input v-model="groupForm.name" required placeholder="5-A Morning" />
            </div>
            <div class="field field-action">
              <button type="submit" class="primary" :disabled="saving">Add group</button>
            </div>
          </div>
        </form>
        <div v-if="studyGroups.length" class="tag-list">
          <span v-for="g in studyGroups" :key="g.id" class="tag">{{ g.name }}</span>
        </div>
        <p v-else class="empty inline">No study groups in this section yet.</p>
      </div>
    </div>

    <!-- Subjects -->
    <div v-if="tab === 'subjects'" class="card">
      <h2>Subject catalog</h2>
      <form class="add-form compact" @submit.prevent="createSubject">
        <div class="form-grid">
          <div class="field">
            <span class="field-label">Name</span>
            <input v-model="subjectForm.name" required placeholder="Mathematics" />
          </div>
          <div class="field">
            <span class="field-label">Code</span>
            <input v-model="subjectForm.code" placeholder="MATH" />
          </div>
          <div class="field field-action">
            <button type="submit" class="primary" :disabled="saving">Add subject</button>
          </div>
        </div>
      </form>
      <div v-if="subjects.length" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th class="col-code">Code</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in subjects" :key="s.id">
              <td>{{ s.name }}</td>
              <td class="col-code">
                <span v-if="s.code" class="code-badge">{{ s.code }}</span>
                <span v-else class="muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="empty">No subjects yet.</p>
    </div>

    <!-- Assign subjects to study group -->
    <div v-if="tab === 'assign'" class="card">
      <h2>Assign subjects to study group</h2>
      <p class="muted small">Pick a study group, then check the subjects taught in that group.</p>
      <div class="field picker-field">
        <span class="field-label">Study group</span>
        <SearchableSelect v-model="assignGroupId" :options="allGroupOptions" placeholder="Select study group…" search-placeholder="Search groups…" @change="loadGroupSubjects" />
      </div>
      <div v-if="assignGroupId" class="perm-grid">
        <label v-for="s in subjects" :key="s.id" class="perm-item">
          <input v-model="assignedSubjectIds" type="checkbox" :value="s.id" />
          {{ s.name }} <span v-if="s.code" class="muted">({{ s.code }})</span>
        </label>
      </div>
      <button v-if="assignGroupId" type="button" class="primary" :disabled="saving" @click="saveGroupSubjects">
        {{ saving ? 'Saving…' : 'Save subject assignment' }}
      </button>
    </div>

    <!-- Student enrollment -->
    <div v-if="tab === 'enroll'" class="card">
      <h2>Student enrollment</h2>
      <div class="field picker-field">
        <span class="field-label">Study group</span>
        <SearchableSelect v-model="enrollGroupId" :options="allGroupOptions" placeholder="Select study group…" search-placeholder="Search groups…" @change="loadStudents" />
      </div>

      <form v-if="enrollGroupId" class="add-form compact" @submit.prevent="enrollStudent">
        <div class="form-grid">
          <div class="field">
            <span class="field-label">First name</span>
            <input v-model="studentForm.first_name" required />
          </div>
          <div class="field">
            <span class="field-label">Last name</span>
            <input v-model="studentForm.last_name" required />
          </div>
          <div class="field">
            <span class="field-label">Admission no.</span>
            <input v-model="studentForm.admission_no" />
          </div>
          <div class="field field-action">
            <button type="submit" class="primary" :disabled="saving">Enroll student</button>
          </div>
        </div>
      </form>

      <div v-if="enrollGroupId && students.length" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th class="col-code">Admission no.</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="st in students" :key="st.id">
              <td>{{ st.first_name }} {{ st.last_name }}</td>
              <td class="col-code">{{ st.admission_no || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else-if="enrollGroupId" class="empty">No students in this group yet.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import api from '../../api/client'
import { usePermissions } from '../../composables/usePermissions'
import { formatPeriod } from '../../composables/format'
import SearchableSelect from '../../components/SearchableSelect.vue'

const { canManageAcademic, canManageRoster } = usePermissions()

const tab = ref(canManageAcademic.value ? 'years' : 'enroll')
const saving = ref(false)
const err = ref('')
const msg = ref('')

const visibleTabs = computed(() => {
  const tabs = []
  if (canManageAcademic.value) {
    tabs.push({ id: 'years', label: 'Session years' }, { id: 'structure', label: 'Structure' }, { id: 'subjects', label: 'Subjects' }, { id: 'assign', label: 'Assign subjects' })
  }
  if (canManageRoster.value) {
    tabs.push({ id: 'enroll', label: 'Enroll students' })
  }
  return tabs
})

watch(tab, async (t) => {
  if ((t === 'structure' || t === 'years') && canManageAcademic.value && !years.value.length) {
    try {
      await loadYears()
    } catch (e) {
      flashErr(e, 'Failed to load session years')
    }
  }
})

watch(visibleTabs, (tabs) => {
  if (!tabs.some((t) => t.id === tab.value) && tabs.length) {
    tab.value = tabs[0].id
  }
})

const years = ref([])
const areas = ref([])
const classes = ref([])
const sections = ref([])
const studyGroups = ref([])
const allStudyGroups = ref([])
const subjects = ref([])
const students = ref([])

const structure = reactive({
  yearId: '',
  areaId: '',
  classId: '',
  sectionId: ''
})

const yearForm = reactive({ name: '', starts_on: '', ends_on: '', is_current: false })
const areaForm = reactive({ name: '' })
const classForm = reactive({ name: '' })
const sectionForm = reactive({ name: '' })
const groupForm = reactive({ name: '' })
const subjectForm = reactive({ name: '', code: '' })
const studentForm = reactive({ first_name: '', last_name: '', admission_no: '' })

const assignGroupId = ref('')
const assignedSubjectIds = ref([])
const enrollGroupId = ref('')

const allGroupOptions = computed(() =>
  allStudyGroups.value.map((g) => ({
    value: g.id,
    label: groupLabel(g)
  }))
)

const yearOptions = computed(() => years.value.map((y) => ({ value: String(y.id), label: y.name })))
const areaOptions = computed(() => areas.value.map((a) => ({ value: String(a.id), label: a.name })))
const classOptions = computed(() => classes.value.map((c) => ({ value: String(c.id), label: c.name })))
const sectionOptions = computed(() => sections.value.map((s) => ({ value: String(s.id), label: s.name })))

function groupLabel(g) {
  const sec = g.section?.name
  const parts = [sec, g.name].filter(Boolean)
  return parts.join(' · ') || g.name
}

function flashOk(text) {
  msg.value = text
  err.value = ''
}

function flashErr(e, fallback) {
  err.value = e.response?.data?.message || fallback
  msg.value = ''
}

async function loadYears() {
  const { data } = await api.get('/efsc/academic/years')
  years.value = data?.data ?? data ?? []
}

async function loadAreas() {
  if (!structure.yearId) {
    areas.value = []
    return
  }
  const { data } = await api.get('/efsc/academic/areas', {
    params: { academic_year_id: structure.yearId }
  })
  areas.value = data?.data ?? data ?? []
}

async function loadClasses() {
  if (!structure.areaId) {
    classes.value = []
    return
  }
  const { data } = await api.get('/efsc/academic/classes', { params: { area_id: structure.areaId } })
  classes.value = data?.data ?? data ?? []
}

async function loadSections() {
  if (!structure.classId) {
    sections.value = []
    return
  }
  const { data } = await api.get('/efsc/academic/sections', {
    params: { school_class_id: structure.classId }
  })
  sections.value = data?.data ?? data ?? []
}

async function loadStudyGroups() {
  if (!structure.sectionId) {
    studyGroups.value = []
    return
  }
  const { data } = await api.get('/efsc/academic/study-groups', {
    params: { section_id: structure.sectionId }
  })
  studyGroups.value = data?.data ?? data ?? []
}

async function loadAllStudyGroups() {
  const { data } = await api.get('/efsc/academic/study-groups')
  allStudyGroups.value = data?.data ?? data ?? []
}

async function loadSubjects() {
  const { data } = await api.get('/efsc/academic/subjects')
  subjects.value = data?.data ?? data ?? []
}

function onYearChange() {
  structure.areaId = ''
  structure.classId = ''
  structure.sectionId = ''
  loadAreas()
  classes.value = []
  sections.value = []
  studyGroups.value = []
}

function onAreaChange() {
  structure.classId = ''
  structure.sectionId = ''
  loadClasses()
  sections.value = []
  studyGroups.value = []
}

function onClassChange() {
  structure.sectionId = ''
  loadSections()
  studyGroups.value = []
}

function onSectionChange() {
  loadStudyGroups()
}

async function createYear() {
  saving.value = true
  try {
    await api.post('/efsc/academic/years', { ...yearForm })
    yearForm.name = ''
    yearForm.starts_on = ''
    yearForm.ends_on = ''
    yearForm.is_current = false
    await loadYears()
    flashOk('Session year created.')
  } catch (e) {
    flashErr(e, 'Failed to create year')
  } finally {
    saving.value = false
  }
}

async function createArea() {
  saving.value = true
  try {
    await api.post('/efsc/academic/areas', {
      academic_year_id: Number(structure.yearId),
      name: areaForm.name
    })
    areaForm.name = ''
    await loadAreas()
    flashOk('Area created.')
  } catch (e) {
    flashErr(e, 'Failed to create area')
  } finally {
    saving.value = false
  }
}

async function createClass() {
  saving.value = true
  try {
    await api.post('/efsc/academic/classes', {
      area_id: Number(structure.areaId),
      name: classForm.name,
    })
    classForm.name = ''
    await loadClasses()
    flashOk('Class created.')
  } catch (e) {
    flashErr(e, 'Failed to create class')
  } finally {
    saving.value = false
  }
}

async function createSection() {
  saving.value = true
  try {
    await api.post('/efsc/academic/sections', {
      school_class_id: Number(structure.classId),
      name: sectionForm.name
    })
    sectionForm.name = ''
    await loadSections()
    flashOk('Section created.')
  } catch (e) {
    flashErr(e, 'Failed to create section')
  } finally {
    saving.value = false
  }
}

async function createStudyGroup() {
  saving.value = true
  try {
    await api.post('/efsc/academic/study-groups', {
      section_id: Number(structure.sectionId),
      name: groupForm.name
    })
    groupForm.name = ''
    await loadStudyGroups()
    await loadAllStudyGroups()
    flashOk('Study group created.')
  } catch (e) {
    flashErr(e, 'Failed to create study group')
  } finally {
    saving.value = false
  }
}

async function createSubject() {
  saving.value = true
  try {
    await api.post('/efsc/academic/subjects', {
      name: subjectForm.name,
      code: subjectForm.code || null
    })
    subjectForm.name = ''
    subjectForm.code = ''
    await loadSubjects()
    flashOk('Subject created.')
  } catch (e) {
    flashErr(e, 'Failed to create subject')
  } finally {
    saving.value = false
  }
}

async function loadGroupSubjects() {
  if (!assignGroupId.value) return
  const group = allStudyGroups.value.find((g) => g.id == assignGroupId.value)
  assignedSubjectIds.value = (group?.subjects || []).map((s) => s.id)
  if (group?.subjects?.length) return
  const { data } = await api.get('/efsc/academic/study-groups', {
    params: { section_id: group?.section_id }
  })
  const list = data?.data ?? data ?? []
  const fresh = list.find((g) => g.id == assignGroupId.value)
  assignedSubjectIds.value = (fresh?.subjects || []).map((s) => s.id)
}

async function saveGroupSubjects() {
  saving.value = true
  try {
    await api.put(`/efsc/academic/study-groups/${assignGroupId.value}/subjects`, {
      subject_ids: assignedSubjectIds.value
    })
    await loadAllStudyGroups()
    flashOk('Subjects assigned.')
  } catch (e) {
    flashErr(e, 'Failed to assign subjects')
  } finally {
    saving.value = false
  }
}

async function loadStudents() {
  if (!enrollGroupId.value) return
  const { data } = await api.get('/efsc/students', {
    params: { study_group_id: enrollGroupId.value }
  })
  students.value = data?.data ?? data ?? []
}

async function enrollStudent() {
  saving.value = true
  try {
    await api.post('/efsc/students', {
      study_group_id: Number(enrollGroupId.value),
      first_name: studentForm.first_name,
      last_name: studentForm.last_name,
      admission_no: studentForm.admission_no || null
    })
    studentForm.first_name = ''
    studentForm.last_name = ''
    studentForm.admission_no = ''
    await loadStudents()
    flashOk('Student enrolled.')
  } catch (e) {
    flashErr(e, 'Failed to enroll student')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (canManageAcademic.value) {
    await loadYears().catch((e) => flashErr(e, 'Failed to load session years'))
    await loadSubjects().catch((e) => flashErr(e, 'Failed to load subjects'))
    await loadAllStudyGroups().catch((e) => flashErr(e, 'Failed to load study groups'))
  } else if (canManageRoster.value) {
    await loadAllStudyGroups().catch((e) => flashErr(e, 'Failed to load study groups'))
  }
})
</script>

<style scoped>
.muted {
  color: #71717a;
}
.small {
  font-size: 0.85rem;
  margin-top: -0.25rem;
  margin-bottom: 1rem;
}
.structure-hint {
  margin: 0 0 1rem;
  padding: 0.65rem 0.85rem;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 6px;
  font-size: 0.9rem;
  color: #92400e;
}
.linkish {
  background: none;
  border: none;
  padding: 0;
  color: #1d4ed8;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}
.ok {
  color: #166534;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}
.error {
  margin-bottom: 1rem;
}
.empty {
  margin: 0.5rem 0 0;
  padding: 1rem;
  text-align: center;
  color: #71717a;
  background: #fafafa;
  border: 1px dashed #d4d4d8;
  border-radius: 6px;
  font-size: 0.9rem;
}
.empty.inline {
  padding: 0.65rem 0.75rem;
  text-align: left;
}
.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 1rem 0;
}
.tabs button {
  padding: 0.5rem 1rem;
  border: 1px solid #d4d4d8;
  background: #fff;
  cursor: pointer;
  border-radius: 6px;
  font-size: 0.9rem;
}
.tabs button.active {
  background: #2563eb;
  color: #fff;
  border-color: #2563eb;
}

.add-form {
  background: #fafafa;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1.25rem;
}
.add-form.compact {
  margin-bottom: 0.75rem;
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 1rem 1.25rem;
  align-items: end;
}
.years-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem 1.5rem;
}
@media (max-width: 720px) {
  .years-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 480px) {
  .years-grid {
    grid-template-columns: 1fr;
  }
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  min-width: 0;
}
.field-checkbox {
  min-width: 0;
}
.field-label-spacer {
  visibility: hidden;
  user-select: none;
  line-height: 1.2;
  min-height: 1rem;
}
.field.grow {
  grid-column: span 1;
}
.field-action {
  justify-content: flex-end;
}
.field-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #52525b;
  line-height: 1.2;
  min-height: 1rem;
}
.field input,
.field select {
  display: block;
  width: 100%;
  max-width: none;
  margin: 0;
  padding: 0 0.65rem;
  height: 2.375rem;
  box-sizing: border-box;
  border: 1px solid #d4d4d8;
  border-radius: 6px;
  font-size: 0.9rem;
  background: #fff;
}
.years-grid .field-checkbox .checkbox-field {
  height: 2.375rem;
  box-sizing: border-box;
  margin: 0;
}
.field input:focus,
.field select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.12);
}
.checkbox-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #3f3f46;
  cursor: pointer;
  margin: 0;
}
.checkbox-field input[type='checkbox'] {
  width: 1rem;
  height: 1rem;
  margin: 0;
  flex-shrink: 0;
}
.form-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e4e4e7;
}
.form-footer .primary {
  margin: 0;
}
.field-action .primary {
  margin: 0;
  white-space: nowrap;
}

.cascade {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.75rem 1rem;
  margin-bottom: 1.25rem;
  padding: 1rem;
  background: #fafafa;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
}
.cascade-field :deep(.searchable-select) {
  margin: 0;
  max-width: none;
}
.cascade-field :deep(.trigger) {
  height: 2.375rem;
  box-sizing: border-box;
}

.panel {
  border-top: 1px solid #e4e4e7;
  padding-top: 1.25rem;
  margin-top: 1rem;
}
.panel h3 {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: #27272a;
}

.tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: 0.5rem;
}
.tag {
  display: inline-block;
  padding: 0.25rem 0.65rem;
  background: #eff6ff;
  color: #1d4ed8;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 500;
}

.table-wrap {
  overflow-x: auto;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.data-table th,
.data-table td {
  text-align: left;
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #e4e4e7;
}
.data-table tbody tr:last-child td {
  border-bottom: none;
}
.data-table tbody tr:hover {
  background: #fafafa;
}
.data-table th {
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  color: #71717a;
  background: #fafafa;
}
.cell-name {
  font-weight: 600;
  color: #18181b;
}
.cell-period {
  color: #3f3f46;
  white-space: nowrap;
}
.col-status,
.col-code {
  width: 1%;
  white-space: nowrap;
}
.badge {
  display: inline-block;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}
.badge.current {
  background: #dcfce7;
  color: #166534;
}
.code-badge {
  display: inline-block;
  padding: 0.1rem 0.45rem;
  background: #f4f4f5;
  border-radius: 4px;
  font-family: ui-monospace, monospace;
  font-size: 0.8rem;
  color: #52525b;
}

.picker-field {
  max-width: 420px;
  margin-bottom: 1rem;
}
.picker-field :deep(.searchable-select) {
  margin: 0;
}
.perm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.35rem;
  margin: 1rem 0;
  padding: 0.75rem;
  background: #fafafa;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
  max-height: 320px;
  overflow-y: auto;
}
.perm-item {
  font-size: 0.85rem;
  display: flex;
  gap: 0.35rem;
  align-items: flex-start;
  cursor: pointer;
}
.perm-item input[type='checkbox'] {
  width: auto;
  margin: 0.15rem 0 0;
  flex-shrink: 0;
}
</style>
