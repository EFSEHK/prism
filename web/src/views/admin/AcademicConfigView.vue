<template>
  <div>
    <AdminBackNav />
    <h1>Academic configuration</h1>
    <p class="muted">Set up school structure, subjects, and student enrollment.</p>

    <div class="tabs">
      <button v-for="t in visibleTabs" :key="t.id" type="button" :class="{ active: tab === t.id }" @click="tab = t.id">
        {{ t.label }}
      </button>
    </div>

    <div v-if="err" class="error">{{ err }}</div>
    <div v-if="msg" class="ok">{{ msg }}</div>

    <!-- Structure -->
    <div v-if="tab === 'structure'" class="card">
      <h2>School structure</h2>
      <p class="muted small">Click a row to drill down. Year → Area → Class → Section.</p>

      <nav v-if="breadcrumb.length" class="structure-breadcrumb" aria-label="Structure path">
        <button type="button" class="crumb" @click="navigateStructure(-1)">Session years</button>
        <template v-for="(crumb, i) in breadcrumb" :key="`${crumb.level}-${crumb.id}`">
          <span class="crumb-sep" aria-hidden="true">›</span>
          <button
            type="button"
            class="crumb"
            :class="{ current: i === breadcrumb.length - 1 }"
            @click="navigateStructure(i)"
          >
            {{ crumb.label }}
          </button>
        </template>
      </nav>

      <div class="structure-toolbar">
        <h3 class="structure-level-title">{{ structureLevelTitle }}</h3>
        <button type="button" class="primary" :disabled="!canAddAtLevel" @click="openStructureModal(structureLevel)">
          Add {{ structureLevelSingular }}
        </button>
      </div>

      <div v-if="structureLevel === 'year'" class="table-wrap">
        <table v-if="years.length" class="data-table structure-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Starts</th>
              <th>Ends</th>
              <th>Current</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="y in years" :key="y.id" class="row-clickable" @click="selectYear(y)">
              <td>{{ y.name }}</td>
              <td>{{ y.starts_on }}</td>
              <td>{{ y.ends_on }}</td>
              <td>{{ y.is_current ? 'Yes' : '—' }}</td>
              <td class="col-actions" @click.stop>
                <button type="button" class="row-action" @click="openEditYear(y)">Edit</button>
                <button type="button" class="row-action danger" @click="deleteYear(y)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty">No session years yet. Click Add session year to create one.</p>
      </div>

      <div v-else-if="structureLevel === 'area'" class="table-wrap">
        <table v-if="areas.length" class="data-table structure-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Section head</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in areas" :key="a.id" class="row-clickable" @click="selectArea(a)">
              <td>{{ a.name }}</td>
              <td>{{ a.section_head?.name ?? a.sectionHead?.name ?? '—' }}</td>
              <td class="col-actions" @click.stop>
                <button type="button" class="row-action" @click="openEditArea(a)">Edit</button>
                <button type="button" class="row-action danger" @click="deleteArea(a)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty">No areas in this session year yet.</p>
      </div>

      <div v-else-if="structureLevel === 'class'" class="table-wrap">
        <table v-if="classes.length" class="data-table structure-table">
          <thead>
            <tr>
              <th class="col-sequence">Seq</th>
              <th>Name</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in classes" :key="c.id" class="row-clickable" @click="selectClass(c)">
              <td class="col-sequence">{{ c.sequence }}</td>
              <td>{{ c.name }}</td>
              <td class="col-actions" @click.stop>
                <button type="button" class="row-action" @click="openEditClass(c)">Edit</button>
                <button type="button" class="row-action danger" @click="deleteClass(c)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty">No classes in this area yet.</p>
      </div>

      <div v-else class="table-wrap">
        <table v-if="sections.length" class="data-table structure-table">
          <thead>
            <tr>
              <th class="col-sequence">Seq</th>
              <th>Name</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sections" :key="s.id">
              <td class="col-sequence">{{ s.sequence }}</td>
              <td>{{ s.name }}</td>
              <td class="col-actions">
                <button type="button" class="row-action" @click="openEditSection(s)">Edit</button>
                <button type="button" class="row-action danger" @click="deleteSection(s)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty">No sections in this class yet.</p>
      </div>

      <div v-if="structureModal" class="modal-backdrop" @click.self="closeStructureModal">
        <div class="modal" role="dialog" aria-modal="true">
          <h3>{{ structureModalTitle }}</h3>

          <form v-if="structureModal === 'year'" class="modal-form" @submit.prevent="submitStructureModal">
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
            <label class="checkbox-field">
              <input v-model="yearForm.is_current" type="checkbox" />
              <span>Current session</span>
            </label>
            <div class="modal-actions">
              <button type="button" class="secondary" @click="closeStructureModal">Cancel</button>
              <button type="submit" class="primary" :disabled="saving">{{ structureModalSubmitLabel }}</button>
            </div>
          </form>

          <form v-else-if="structureModal === 'area'" class="modal-form" @submit.prevent="submitStructureModal">
            <div class="field">
              <span class="field-label">Area name</span>
              <input v-model="areaForm.name" required placeholder="Primary" autofocus />
            </div>
            <div class="field">
              <span class="field-label">Section head</span>
              <SearchableSelect
                v-model="areaForm.sectionHeadUserId"
                :options="sectionHeadOptions"
                placeholder="None"
                search-placeholder="Search section heads…"
                empty-options-text="No section heads found"
              />
            </div>
            <div class="modal-actions">
              <button type="button" class="secondary" @click="closeStructureModal">Cancel</button>
              <button type="submit" class="primary" :disabled="saving">{{ structureModalSubmitLabel }}</button>
            </div>
          </form>

          <form v-else-if="structureModal === 'class'" class="modal-form" @submit.prevent="submitStructureModal">
            <div class="field">
              <span class="field-label">Sequence</span>
              <input v-model.number="classForm.sequence" type="number" min="0" step="1" placeholder="1" />
            </div>
            <div class="field">
              <span class="field-label">Class name</span>
              <input v-model="classForm.name" required placeholder="10th" autofocus />
            </div>
            <div class="modal-actions">
              <button type="button" class="secondary" @click="closeStructureModal">Cancel</button>
              <button type="submit" class="primary" :disabled="saving">{{ structureModalSubmitLabel }}</button>
            </div>
          </form>

          <form v-else-if="structureModal === 'section'" class="modal-form" @submit.prevent="submitStructureModal">
            <div class="field">
              <span class="field-label">Sequence</span>
              <input v-model.number="sectionForm.sequence" type="number" min="0" step="1" placeholder="1" />
            </div>
            <div class="field">
              <span class="field-label">Section name</span>
              <input v-model="sectionForm.name" required placeholder="White" autofocus />
            </div>
            <div class="modal-actions">
              <button type="button" class="secondary" @click="closeStructureModal">Cancel</button>
              <button type="submit" class="primary" :disabled="saving">{{ structureModalSubmitLabel }}</button>
            </div>
          </form>
        </div>
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
      <h2>Study groups & subject assignment</h2>
      <p class="muted small">Create study groups and assign subjects to each group.</p>
      <div class="field picker-field">
        <span class="field-label">Study group</span>
        <div class="select-with-add">
          <SearchableSelect
            v-model="assignGroupId"
            :options="allGroupOptions"
            placeholder="Select study group…"
            search-placeholder="Search groups…"
            @change="loadGroupSubjects"
          />
          <button type="button" class="add-circle" title="Add study group" @click="openGroupModal">+</button>
        </div>
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

      <div v-if="groupModalOpen" class="modal-backdrop" @click.self="closeGroupModal">
        <div class="modal" role="dialog" aria-modal="true">
          <h3>Add study group</h3>
          <form class="modal-form" @submit.prevent="createStudyGroup">
            <div class="field">
              <span class="field-label">Study group name</span>
              <input v-model="groupForm.name" required placeholder="Pre-Engineering" autofocus />
            </div>
            <div class="modal-actions">
              <button type="button" class="secondary" @click="closeGroupModal">Cancel</button>
              <button type="submit" class="primary" :disabled="saving">{{ saving ? 'Adding…' : 'Add' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Student enrollment -->
    <div v-if="tab === 'enroll'" class="card">
      <div class="enroll-header">
        <h2>Student enrollment</h2>
        <button type="button" class="primary" @click="openEnrollModal">Add new</button>
      </div>
      <div class="enroll-filters">
        <div class="field picker-field">
          <span class="field-label">Class</span>
          <SearchableSelect
            v-model="enrollFilterClassId"
            :options="enrollClassOptions"
            placeholder="Select class…"
            search-placeholder="Search classes…"
            @change="onEnrollFilterClassChange"
          />
        </div>
        <div class="field picker-field">
          <span class="field-label">Section</span>
          <SearchableSelect
            v-model="enrollFilterSectionId"
            :options="enrollFilterSectionOptions"
            placeholder="All sections"
            search-placeholder="Search sections…"
            :disabled="!enrollFilterClassId"
            @change="onEnrollFilterSectionChange"
          />
        </div>
        <div class="field picker-field">
          <span class="field-label">Study group</span>
          <SearchableSelect
            v-model="enrollFilterGroupId"
            :options="enrollFilterGroupOptions"
            placeholder="All study groups"
            search-placeholder="Search groups…"
            @change="onEnrollFilterGroupChange"
          />
        </div>
      </div>
      <div class="enroll-search-row">
        <div class="field enroll-search">
          <input v-model="enrollSearchQuery" type="search" placeholder="Search students…" />
        </div>
      </div>

      <div v-if="hasEnrollScope && filteredStudents.length" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Class</th>
              <th>Section</th>
              <th>Study group</th>
              <th class="col-code">Admission no.</th>
              <th class="col-code">Roll no.</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="st in filteredStudents" :key="st.id">
              <td>{{ st.first_name }} {{ st.last_name }}</td>
              <td>{{ studentClassName(st) }}</td>
              <td>{{ studentSectionName(st) }}</td>
              <td>{{ studentGroupName(st) }}</td>
              <td class="col-code">{{ st.admission_no || '—' }}</td>
              <td class="col-code">{{ st.roll_no || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else-if="hasEnrollScope && students.length" class="empty">No students match your search or filters.</p>
      <p v-else-if="hasEnrollScope" class="empty">No students enrolled in this class and section yet.</p>
      <p v-else class="empty">Select a class and section to view enrolled students, or click Add new to enroll.</p>

      <div v-if="enrollModalOpen" class="modal-backdrop" @click.self="closeEnrollModal">
        <div class="modal modal-enroll" role="dialog" aria-modal="true">
          <h3>Enroll student</h3>
          <form class="modal-form" @submit.prevent="enrollStudent">
            <div class="form-section">
              <h4>Student</h4>
              <div class="modal-form-grid">
                <div class="field span-2">
                  <span class="field-label">Student name</span>
                  <input v-model="studentForm.name" required placeholder="Full name" autofocus />
                </div>
                <div class="field">
                  <span class="field-label">CNIC</span>
                  <input v-model="studentForm.cnic" placeholder="xxxxx-xxxxxxx-x" />
                </div>
                <div class="field">
                  <span class="field-label">Admission no.</span>
                  <input v-model="studentForm.admission_no" required placeholder="A-25-0016" />
                </div>
                <div class="field">
                  <span class="field-label">Class</span>
                  <SearchableSelect
                    v-model="studentForm.classId"
                    :options="enrollClassOptions"
                    placeholder="Select class…"
                    search-placeholder="Search classes…"
                    @change="onEnrollClassChange"
                  />
                </div>
                <div class="field">
                  <span class="field-label">Section</span>
                  <SearchableSelect
                    v-model="studentForm.sectionId"
                    :options="enrollSectionOptions"
                    placeholder="Select section…"
                    search-placeholder="Search sections…"
                    :disabled="!studentForm.classId"
                  />
                </div>
                <div class="field">
                  <span class="field-label">Roll no.</span>
                  <input v-model="studentForm.roll_no" />
                </div>
                <div class="field">
                  <span class="field-label">Study group</span>
                  <SearchableSelect
                    v-model="studentForm.studyGroupId"
                    :options="enrollGroupFormOptions"
                    placeholder="Select study group…"
                    search-placeholder="Search groups…"
                    :allow-empty="false"
                  />
                </div>
              </div>
            </div>

            <div class="form-section">
              <h4>Father</h4>
              <div class="modal-form-grid">
                <div class="field">
                  <span class="field-label">Father name</span>
                  <input v-model="studentForm.father_name" />
                </div>
                <div class="field">
                  <span class="field-label">Father CNIC</span>
                  <input v-model="studentForm.father_cnic" placeholder="xxxxx-xxxxxxx-x" />
                </div>
              </div>
            </div>

            <div class="form-section">
              <h4>Guardian</h4>
              <label class="checkbox-field">
                <input v-model="studentForm.father_is_guardian" type="checkbox" />
                <span>Father is guardian</span>
              </label>
              <div class="modal-form-grid">
                <div class="field">
                  <span class="field-label">Guardian name</span>
                  <input v-model="studentForm.guardian_name" :disabled="studentForm.father_is_guardian" />
                </div>
                <div class="field">
                  <span class="field-label">Guardian CNIC</span>
                  <input v-model="studentForm.guardian_cnic" :disabled="studentForm.father_is_guardian" placeholder="xxxxx-xxxxxxx-x" />
                </div>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="secondary" @click="closeEnrollModal">Cancel</button>
              <button type="submit" class="primary" :disabled="saving || !studentForm.studyGroupId">{{ saving ? 'Enrolling…' : 'Enroll student' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import api from '../../api/client'
import { usePermissions } from '../../composables/usePermissions'
import SearchableSelect from '../../components/SearchableSelect.vue'
import AdminBackNav from '../../components/AdminBackNav.vue'

const { canManageAcademic, canManageRoster } = usePermissions()

const tab = ref(canManageAcademic.value ? 'structure' : 'enroll')
const saving = ref(false)
const err = ref('')
const msg = ref('')

const visibleTabs = computed(() => {
  const tabs = []
  if (canManageAcademic.value) {
    tabs.push({ id: 'structure', label: 'Structure' }, { id: 'subjects', label: 'Subjects' }, { id: 'assign', label: 'Assign subjects' })
  }
  if (canManageRoster.value) {
    tabs.push({ id: 'enroll', label: 'Enroll students' })
  }
  return tabs
})

watch(tab, async (t) => {
  if (t === 'structure' && canManageAcademic.value && !years.value.length) {
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
const allStudyGroups = ref([])
const subjects = ref([])
const students = ref([])

const structure = reactive({
  yearId: '',
  areaId: '',
  classId: '',
  sectionId: '',
})

const structureModal = ref(null)
const editingYearId = ref(null)
const editingAreaId = ref(null)
const editingClassId = ref(null)
const editingSectionId = ref(null)
const groupModalOpen = ref(false)
const sectionHeads = ref([])

const structureLevel = computed(() => {
  if (!structure.yearId) return 'year'
  if (!structure.areaId) return 'area'
  if (!structure.classId) return 'class'
  return 'section'
})

const structureLevelSingular = computed(() => {
  const labels = { year: 'session year', area: 'area', class: 'class', section: 'section' }
  return labels[structureLevel.value]
})

const structureLevelTitle = computed(() => {
  const titles = {
    year: 'Session years',
    area: 'Areas',
    class: 'Classes',
    section: 'Sections',
  }
  return titles[structureLevel.value]
})

const canAddAtLevel = computed(() => {
  if (structureLevel.value === 'year') return true
  if (structureLevel.value === 'area') return !!structure.yearId
  if (structureLevel.value === 'class') return !!structure.areaId
  return !!structure.classId
})

const breadcrumb = computed(() => {
  const crumbs = []
  const year = years.value.find((y) => String(y.id) === String(structure.yearId))
  if (year) crumbs.push({ level: 'year', id: year.id, label: year.name })
  const area = areas.value.find((a) => String(a.id) === String(structure.areaId))
  if (area) crumbs.push({ level: 'area', id: area.id, label: area.name })
  const cls = classes.value.find((c) => String(c.id) === String(structure.classId))
  if (cls) crumbs.push({ level: 'class', id: cls.id, label: cls.name })
  return crumbs
})

const structureIsEditing = computed(() => {
  const map = {
    year: editingYearId.value,
    area: editingAreaId.value,
    class: editingClassId.value,
    section: editingSectionId.value,
  }
  return !!map[structureModal.value]
})

const structureModalTitle = computed(() => {
  const labels = { year: 'session year', area: 'area', class: 'class', section: 'section' }
  const type = structureModal.value
  if (!type) return ''
  const action = structureIsEditing.value ? 'Edit' : 'Add'
  return `${action} ${labels[type]}`
})

const structureModalSubmitLabel = computed(() => {
  if (saving.value) return structureIsEditing.value ? 'Saving…' : 'Adding…'
  return structureIsEditing.value ? 'Save' : 'Add'
})

const yearForm = reactive({ name: '', starts_on: '', ends_on: '', is_current: false })
const areaForm = reactive({ name: '', sectionHeadUserId: '' })
const classForm = reactive({ name: '', sequence: null })
const sectionForm = reactive({ name: '', sequence: null })
const groupForm = reactive({ name: '' })
const subjectForm = reactive({ name: '', code: '' })
const studentForm = reactive({
  name: '',
  admission_no: '',
  classId: '',
  sectionId: '',
  studyGroupId: '',
  roll_no: '',
  cnic: '',
  father_name: '',
  father_cnic: '',
  guardian_name: '',
  guardian_cnic: '',
  father_is_guardian: false,
})

watch(
  () => [studentForm.father_name, studentForm.father_cnic, studentForm.father_is_guardian],
  () => {
    if (studentForm.father_is_guardian) {
      studentForm.guardian_name = studentForm.father_name
      studentForm.guardian_cnic = studentForm.father_cnic
    }
  }
)

const assignGroupId = ref('')
const assignedSubjectIds = ref([])
const enrollFilterClassId = ref('')
const enrollFilterSectionId = ref('')
const enrollFilterGroupId = ref('')
const enrollSearchQuery = ref('')
const enrollModalOpen = ref(false)
const enrollClasses = ref([])
const enrollSections = ref([])
const enrollFilterSections = ref([])
const enrollSectionLookup = ref([])

const allGroupOptions = computed(() =>
  allStudyGroups.value.map((g) => ({
    value: g.id,
    label: groupLabel(g)
  }))
)

const sectionHeadOptions = computed(() =>
  sectionHeads.value.map((u) => ({
    value: String(u.id),
    label: u.email ? `${u.name} (${u.email})` : u.name,
  }))
)
const enrollClassOptions = computed(() =>
  enrollClasses.value.map((c) => ({ value: String(c.id), label: classLabel(c) }))
)
const enrollSectionOptions = computed(() =>
  enrollSections.value.map((s) => ({ value: String(s.id), label: s.name }))
)
const enrollGroupFormOptions = computed(() =>
  allStudyGroups.value.map((g) => ({ value: String(g.id), label: g.name }))
)
const enrollFilterSectionOptions = computed(() =>
  enrollFilterSections.value.map((s) => ({ value: String(s.id), label: s.name }))
)
const enrollFilterGroupOptions = computed(() =>
  allStudyGroups.value.map((g) => ({ value: String(g.id), label: g.name }))
)
const hasEnrollScope = computed(
  () => !!enrollFilterClassId.value && !!enrollFilterSectionId.value
)
const filteredStudents = computed(() => {
  const q = enrollSearchQuery.value.trim().toLowerCase()
  if (!q) return students.value
  return students.value.filter((st) => {
    const haystack = [
      st.first_name,
      st.last_name,
      st.admission_no,
      st.roll_no,
      st.cnic,
      studentClassName(st),
      studentSectionName(st),
      studentGroupName(st),
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return haystack.includes(q)
  })
})

function classLabel(c) {
  const area = c.area?.name
  return area ? `${c.name} (${area})` : c.name
}

function groupLabel(g) {
  return g.name
}

function studentStudyGroup(st) {
  return (
    st.study_group ??
    st.studyGroup ??
    allStudyGroups.value.find((g) => String(g.id) === String(st.study_group_id))
  )
}

function studentClassName(st) {
  const schoolClass = st.section?.school_class ?? st.section?.schoolClass
  if (schoolClass) return classLabel(schoolClass)
  const section = enrollSectionLookup.value.find((s) => String(s.id) === String(st.section_id))
  const cls = section?.school_class ?? section?.schoolClass
  return cls ? classLabel(cls) : '—'
}

function studentSectionName(st) {
  if (st.section?.name) return st.section.name
  const section = enrollSectionLookup.value.find((s) => String(s.id) === String(st.section_id))
  return section?.name ?? '—'
}

function studentGroupName(st) {
  return studentStudyGroup(st)?.name ?? '—'
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

async function loadAllStudyGroups() {
  const { data } = await api.get('/efsc/academic/study-groups')
  allStudyGroups.value = data?.data ?? data ?? []
}

async function loadSubjects() {
  const { data } = await api.get('/efsc/academic/subjects')
  subjects.value = data?.data ?? data ?? []
}

function selectYear(y) {
  structure.yearId = String(y.id)
  structure.areaId = ''
  structure.classId = ''
  structure.sectionId = ''
  classes.value = []
  sections.value = []
  loadAreas()
}

function selectArea(a) {
  structure.areaId = String(a.id)
  structure.classId = ''
  structure.sectionId = ''
  sections.value = []
  loadClasses()
}

function selectClass(c) {
  structure.classId = String(c.id)
  structure.sectionId = ''
  loadSections()
}

function navigateStructure(index) {
  if (index < 0) {
    structure.yearId = ''
    structure.areaId = ''
    structure.classId = ''
    structure.sectionId = ''
    areas.value = []
    classes.value = []
    sections.value = []
    return
  }
  const crumb = breadcrumb.value[index]
  if (crumb.level === 'year') {
    structure.areaId = ''
    structure.classId = ''
    structure.sectionId = ''
    classes.value = []
    sections.value = []
    loadAreas()
  } else if (crumb.level === 'area') {
    structure.classId = ''
    structure.sectionId = ''
    sections.value = []
    loadClasses()
  } else if (crumb.level === 'class') {
    structure.sectionId = ''
    loadSections()
  }
}

function resetStructureForms() {
  yearForm.name = ''
  yearForm.starts_on = ''
  yearForm.ends_on = ''
  yearForm.is_current = false
  areaForm.name = ''
  areaForm.sectionHeadUserId = ''
  classForm.name = ''
  classForm.sequence = null
  sectionForm.name = ''
  sectionForm.sequence = null
  editingYearId.value = null
  editingAreaId.value = null
  editingClassId.value = null
  editingSectionId.value = null
}

function openStructureModal(type) {
  resetStructureForms()
  structureModal.value = type
  if (type === 'area') loadSectionHeads().catch(() => {})
}

function openEditYear(y) {
  resetStructureForms()
  editingYearId.value = y.id
  yearForm.name = y.name
  yearForm.starts_on = y.starts_on
  yearForm.ends_on = y.ends_on
  yearForm.is_current = !!y.is_current
  structureModal.value = 'year'
}

function openEditArea(a) {
  resetStructureForms()
  editingAreaId.value = a.id
  areaForm.name = a.name
  areaForm.sectionHeadUserId = a.section_head_user_id ? String(a.section_head_user_id) : ''
  structureModal.value = 'area'
  loadSectionHeads().catch(() => {})
}

function openEditClass(c) {
  resetStructureForms()
  editingClassId.value = c.id
  classForm.name = c.name
  classForm.sequence = c.sequence ?? null
  structureModal.value = 'class'
}

function openEditSection(s) {
  resetStructureForms()
  editingSectionId.value = s.id
  sectionForm.name = s.name
  sectionForm.sequence = s.sequence ?? null
  structureModal.value = 'section'
}

function closeStructureModal() {
  structureModal.value = null
  resetStructureForms()
}

async function submitStructureModal() {
  const handlers = {
    year: editingYearId.value ? updateYear : createYear,
    area: editingAreaId.value ? updateArea : createArea,
    class: editingClassId.value ? updateClass : createClass,
    section: editingSectionId.value ? updateSection : createSection,
  }
  const fn = handlers[structureModal.value]
  if (fn) await fn()
}

async function loadSectionHeads() {
  const { data } = await api.get('/efsc/academic/section-heads')
  sectionHeads.value = data?.data ?? data ?? []
}

function openGroupModal() {
  groupModalOpen.value = true
}

function closeGroupModal() {
  groupModalOpen.value = false
}

async function createYear() {
  saving.value = true
  try {
    await api.post('/efsc/academic/years', { ...yearForm })
    await loadYears()
    closeStructureModal()
    flashOk('Session year created.')
  } catch (e) {
    flashErr(e, 'Failed to create year')
  } finally {
    saving.value = false
  }
}

async function updateYear() {
  saving.value = true
  try {
    await api.put(`/efsc/academic/years/${editingYearId.value}`, { ...yearForm })
    await loadYears()
    closeStructureModal()
    flashOk('Session year updated.')
  } catch (e) {
    flashErr(e, 'Failed to update session year')
  } finally {
    saving.value = false
  }
}

async function deleteYear(y) {
  if (!window.confirm(`Delete session year "${y.name}"? All areas, classes, and sections under it will be removed.`)) return
  try {
    await api.delete(`/efsc/academic/years/${y.id}`)
    if (String(structure.yearId) === String(y.id)) {
      navigateStructure(-1)
    }
    await loadYears()
    flashOk('Session year deleted.')
  } catch (e) {
    flashErr(e, 'Failed to delete session year')
  }
}

function areaPayload() {
  return {
    name: areaForm.name,
    section_head_user_id: areaForm.sectionHeadUserId ? Number(areaForm.sectionHeadUserId) : null,
  }
}

async function createArea() {
  saving.value = true
  try {
    await api.post('/efsc/academic/areas', {
      academic_year_id: Number(structure.yearId),
      ...areaPayload(),
    })
    await loadAreas()
    closeStructureModal()
    flashOk('Area created.')
  } catch (e) {
    flashErr(e, 'Failed to create area')
  } finally {
    saving.value = false
  }
}

async function updateArea() {
  saving.value = true
  try {
    await api.put(`/efsc/academic/areas/${editingAreaId.value}`, areaPayload())
    await loadAreas()
    closeStructureModal()
    flashOk('Area updated.')
  } catch (e) {
    flashErr(e, 'Failed to update area')
  } finally {
    saving.value = false
  }
}

async function deleteArea(a) {
  if (!window.confirm(`Delete area "${a.name}"? All classes and sections under it will be removed.`)) return
  try {
    await api.delete(`/efsc/academic/areas/${a.id}`)
    if (String(structure.areaId) === String(a.id)) {
      structure.areaId = ''
      structure.classId = ''
      structure.sectionId = ''
      classes.value = []
      sections.value = []
    }
    await loadAreas()
    flashOk('Area deleted.')
  } catch (e) {
    flashErr(e, 'Failed to delete area')
  }
}

async function createClass() {
  saving.value = true
  try {
    await api.post('/efsc/academic/classes', {
      area_id: Number(structure.areaId),
      name: classForm.name,
      ...(classForm.sequence != null && classForm.sequence !== '' ? { sequence: Number(classForm.sequence) } : {}),
    })
    await loadClasses()
    closeStructureModal()
    flashOk('Class created.')
  } catch (e) {
    flashErr(e, 'Failed to create class')
  } finally {
    saving.value = false
  }
}

async function updateClass() {
  saving.value = true
  try {
    await api.put(`/efsc/academic/classes/${editingClassId.value}`, {
      name: classForm.name,
      ...(classForm.sequence != null && classForm.sequence !== '' ? { sequence: Number(classForm.sequence) } : {}),
    })
    await loadClasses()
    closeStructureModal()
    flashOk('Class updated.')
  } catch (e) {
    flashErr(e, 'Failed to update class')
  } finally {
    saving.value = false
  }
}

async function deleteClass(c) {
  if (!window.confirm(`Delete class "${c.name}"? All sections under it will be removed.`)) return
  try {
    await api.delete(`/efsc/academic/classes/${c.id}`)
    if (String(structure.classId) === String(c.id)) {
      structure.classId = ''
      structure.sectionId = ''
      sections.value = []
    }
    await loadClasses()
    flashOk('Class deleted.')
  } catch (e) {
    flashErr(e, 'Failed to delete class')
  }
}

async function createSection() {
  saving.value = true
  try {
    await api.post('/efsc/academic/sections', {
      school_class_id: Number(structure.classId),
      name: sectionForm.name,
      ...(sectionForm.sequence != null && sectionForm.sequence !== '' ? { sequence: Number(sectionForm.sequence) } : {}),
    })
    await loadSections()
    closeStructureModal()
    flashOk('Section created.')
  } catch (e) {
    flashErr(e, 'Failed to create section')
  } finally {
    saving.value = false
  }
}

async function updateSection() {
  saving.value = true
  try {
    await api.put(`/efsc/academic/sections/${editingSectionId.value}`, {
      name: sectionForm.name,
      ...(sectionForm.sequence != null && sectionForm.sequence !== '' ? { sequence: Number(sectionForm.sequence) } : {}),
    })
    await loadSections()
    closeStructureModal()
    flashOk('Section updated.')
  } catch (e) {
    flashErr(e, 'Failed to update section')
  } finally {
    saving.value = false
  }
}

async function deleteSection(s) {
  if (!window.confirm(`Delete section "${s.name}"?`)) return
  try {
    await api.delete(`/efsc/academic/sections/${s.id}`)
    if (String(structure.sectionId) === String(s.id)) {
      structure.sectionId = ''
    }
    await loadSections()
    flashOk('Section deleted.')
  } catch (e) {
    flashErr(e, 'Failed to delete section')
  }
}

async function createStudyGroup() {
  saving.value = true
  try {
    const { data } = await api.post('/efsc/academic/study-groups', {
      name: groupForm.name,
    })
    groupForm.name = ''
    await loadAllStudyGroups()
    if (data?.id) assignGroupId.value = data.id
    closeGroupModal()
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
  await loadAllStudyGroups()
  const fresh = allStudyGroups.value.find((g) => g.id == assignGroupId.value)
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

async function loadEnrollFilterSections() {
  if (!enrollFilterClassId.value) {
    enrollFilterSections.value = []
    enrollSectionLookup.value = []
    return
  }
  const { data } = await api.get('/efsc/academic/sections', {
    params: { school_class_id: enrollFilterClassId.value }
  })
  enrollFilterSections.value = data?.data ?? data ?? []
  enrollSectionLookup.value = enrollFilterSections.value
}

async function onEnrollFilterClassChange() {
  enrollFilterSectionId.value = ''
  enrollFilterGroupId.value = ''
  enrollSearchQuery.value = ''
  students.value = []
  await loadEnrollFilterSections()
}

async function onEnrollFilterSectionChange() {
  enrollSearchQuery.value = ''
  await loadStudents()
}

async function onEnrollFilterGroupChange() {
  enrollSearchQuery.value = ''
  await loadStudents()
}

async function loadStudents() {
  if (!hasEnrollScope.value) {
    students.value = []
    return
  }
  if (!enrollFilterSections.value.length) {
    await loadEnrollFilterSections()
  }
  const params = { section_id: enrollFilterSectionId.value }
  if (enrollFilterGroupId.value) {
    params.study_group_id = enrollFilterGroupId.value
  }
  const { data } = await api.get('/efsc/students', { params })
  students.value = data?.data ?? data ?? []
}

async function loadEnrollClasses() {
  const { data } = await api.get('/efsc/academic/classes')
  enrollClasses.value = data?.data ?? data ?? []
}

async function loadEnrollSections() {
  if (!studentForm.classId) {
    enrollSections.value = []
    return
  }
  const { data } = await api.get('/efsc/academic/sections', {
    params: { school_class_id: studentForm.classId }
  })
  enrollSections.value = data?.data ?? data ?? []
}

function resetStudentForm() {
  studentForm.name = ''
  studentForm.admission_no = ''
  studentForm.classId = ''
  studentForm.sectionId = ''
  studentForm.studyGroupId = ''
  studentForm.roll_no = ''
  studentForm.cnic = ''
  studentForm.father_name = ''
  studentForm.father_cnic = ''
  studentForm.guardian_name = ''
  studentForm.guardian_cnic = ''
  studentForm.father_is_guardian = false
  enrollSections.value = []
}

function prefillStudentFormFromGroup(groupId) {
  studentForm.studyGroupId = String(groupId)
}

async function onEnrollClassChange() {
  studentForm.sectionId = ''
  await loadEnrollSections()
}

async function openEnrollModal() {
  resetStudentForm()
  if (!enrollClasses.value.length) {
    await loadEnrollClasses().catch((e) => flashErr(e, 'Failed to load classes'))
  }
  if (enrollFilterClassId.value) {
    studentForm.classId = enrollFilterClassId.value
    studentForm.sectionId = enrollFilterSectionId.value
    studentForm.studyGroupId = enrollFilterGroupId.value
    await loadEnrollSections()
  } else if (enrollFilterGroupId.value) {
    prefillStudentFormFromGroup(enrollFilterGroupId.value)
  }
  enrollModalOpen.value = true
}

function closeEnrollModal() {
  enrollModalOpen.value = false
}

async function enrollStudent() {
  saving.value = true
  try {
    const { data } = await api.post('/efsc/students', {
      study_group_id: Number(studentForm.studyGroupId),
      name: studentForm.name,
      admission_no: studentForm.admission_no,
      section_id: studentForm.sectionId ? Number(studentForm.sectionId) : null,
      roll_no: studentForm.roll_no || null,
      cnic: studentForm.cnic || null,
      father_name: studentForm.father_name || null,
      father_cnic: studentForm.father_cnic || null,
      guardian_name: studentForm.guardian_name || null,
      guardian_cnic: studentForm.guardian_cnic || null,
      father_is_guardian: studentForm.father_is_guardian,
    })
    closeEnrollModal()
    if (studentForm.classId) {
      enrollFilterClassId.value = studentForm.classId
      await loadEnrollFilterSections()
    }
    enrollFilterSectionId.value = studentForm.sectionId || ''
    enrollFilterGroupId.value = String(studentForm.studyGroupId)
    await loadStudents()
    const accountLines = []
    const accounts = data?.accounts
    if (accounts?.student?.email) {
      const local = accounts.student.email.split('@')[0]
      accountLines.push(`Student: ${local}`)
    }
    for (const p of accounts?.parents ?? []) {
      if (p.email) {
        accountLines.push(`${p.name}: ${p.email.split('@')[0]}`)
      }
    }
    const suffix = accountLines.length ? ` Accounts — ${accountLines.join('; ')}.` : ''
    flashOk(`Student enrolled.${suffix}`)
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
  if (canManageRoster.value) {
    await loadEnrollClasses().catch((e) => flashErr(e, 'Failed to load classes'))
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
.structure-breadcrumb {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.35rem 0.5rem;
  margin: 0 0 1rem;
  padding: 0.65rem 0.85rem;
  background: #f4f4f5;
  border: 1px solid #e4e4e7;
  border-radius: 6px;
  font-size: 0.9rem;
}
.crumb {
  background: none;
  border: none;
  padding: 0;
  color: #2563eb;
  font-weight: 600;
  cursor: pointer;
}
.crumb:hover {
  text-decoration: underline;
}
.crumb.current {
  color: #27272a;
  cursor: default;
  text-decoration: none;
}
.crumb-sep {
  color: #a1a1aa;
}
.structure-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
}
.structure-level-title {
  margin: 0;
  font-size: 1.05rem;
}
.structure-table .row-clickable {
  cursor: pointer;
}
.col-sequence {
  width: 1%;
  white-space: nowrap;
  text-align: center;
  color: #64748b;
  font-variant-numeric: tabular-nums;
}
.col-actions {
  width: 1%;
  white-space: nowrap;
  text-align: right;
}
.row-action {
  background: none;
  border: none;
  padding: 0.15rem 0.4rem;
  color: #2563eb;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}
.row-action:hover {
  text-decoration: underline;
}
.row-action.danger {
  color: #dc2626;
}
.row-action + .row-action {
  margin-left: 0.35rem;
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
.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  min-width: 0;
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
.field-action .primary {
  margin: 0;
  white-space: nowrap;
}

.cascade {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
  padding: 1rem;
  background: #fafafa;
  border: 1px solid #e4e4e7;
  border-radius: 8px;
}
.cascade-row {
  display: grid;
  gap: 0.75rem 1rem;
}
.cascade-row-2 {
  grid-template-columns: 1fr 1fr;
}
.cascade-row-3 {
  grid-template-columns: 1fr 1fr 1fr;
}
.structure-path {
  margin: 0;
  padding: 0.65rem 0.85rem;
  background: #f4f4f5;
  border: 1px solid #e4e4e7;
  border-radius: 6px;
  font-size: 0.95rem;
  font-weight: 500;
  color: #27272a;
}
.cascade-field :deep(.searchable-select) {
  margin: 0;
  max-width: none;
}
.cascade-field :deep(.trigger) {
  height: 2.375rem;
  box-sizing: border-box;
}
.select-with-add {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.select-with-add :deep(.searchable-select) {
  flex: 1;
  min-width: 0;
}
.add-circle {
  flex-shrink: 0;
  width: 2.375rem;
  height: 2.375rem;
  border-radius: 999px;
  border: 1px solid #d4d4d8;
  background: #fff;
  color: #2563eb;
  font-size: 1.25rem;
  font-weight: 600;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}
.add-circle:hover:not(:disabled) {
  background: #eff6ff;
  border-color: #2563eb;
}
.add-circle:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  color: #a1a1aa;
}
.edit-circle {
  flex-shrink: 0;
  width: 2.375rem;
  height: 2.375rem;
  border-radius: 999px;
  border: 1px solid #d4d4d8;
  background: #fff;
  color: #52525b;
  font-size: 1rem;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}
.edit-circle:hover:not(:disabled) {
  background: #f4f4f5;
  border-color: #a1a1aa;
  color: #18181b;
}
.edit-circle:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  color: #a1a1aa;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  background: rgb(0 0 0 / 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.modal {
  width: 100%;
  max-width: 380px;
  background: #fff;
  border-radius: 10px;
  padding: 1.25rem;
  box-shadow: 0 12px 32px rgb(0 0 0 / 0.18);
}
.modal-enroll {
  max-width: 560px;
  max-height: min(90vh, 720px);
  overflow-y: auto;
}
.form-section {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}
.form-section h4 {
  margin: 0;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #71717a;
}
.form-section + .form-section {
  padding-top: 0.75rem;
  border-top: 1px solid #e4e4e7;
}
.modal-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.modal-form-grid .span-2 {
  grid-column: span 2;
}
.modal-form-grid .field :deep(.searchable-select) {
  margin: 0;
  max-width: none;
}
.modal-form-grid .field :deep(.trigger) {
  height: 2.375rem;
  box-sizing: border-box;
}
.enroll-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}
.enroll-header h2 {
  margin: 0;
}
.enroll-header .primary {
  margin: 0;
  white-space: nowrap;
  flex-shrink: 0;
}
.enroll-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 1rem;
  margin-bottom: 0.75rem;
}
.enroll-filters .picker-field {
  flex: 1;
  min-width: 160px;
  max-width: none;
  margin-bottom: 0;
}
.enroll-filters .picker-field :deep(.searchable-select) {
  margin: 0;
  max-width: none;
}
.enroll-search-row {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1rem;
}
.enroll-search {
  width: 100%;
  max-width: 280px;
  margin: 0;
}
.enroll-search input {
  display: block;
  width: 100%;
  margin: 0;
  padding: 0 0.65rem;
  height: 2.375rem;
  box-sizing: border-box;
  border: 1px solid #d4d4d8;
  border-radius: 6px;
  font-size: 0.9rem;
  background: #fff;
}
.enroll-search input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.12);
}
.modal h3 {
  margin: 0 0 1rem;
  font-size: 1.05rem;
}
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e4e4e7;
}
.modal-actions .primary,
.modal-actions .secondary {
  margin: 0;
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
