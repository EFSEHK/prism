import { ref, onMounted } from 'vue'
import api from '../api/client'

export function useAcademic() {
  const studyGroupId = ref('')
  const sectionId = ref('')
  const classId = ref('')
  const studyGroups = ref([])
  const sections = ref([])
  const classes = ref([])
  const subjects = ref([])
  const loading = ref(true)

  onMounted(async () => {
    try {
      const [sgRes, secRes, clsRes, subRes] = await Promise.all([
        api.get('/efsc/academic/study-groups').catch(() => ({ data: [] })),
        api.get('/efsc/academic/sections').catch(() => ({ data: [] })),
        api.get('/efsc/academic/classes').catch(() => ({ data: [] })),
        api.get('/efsc/academic/subjects').catch(() => ({ data: [] })),
      ])
      studyGroups.value = sgRes.data?.data ?? sgRes.data ?? []
      sections.value = secRes.data?.data ?? secRes.data ?? []
      classes.value = clsRes.data?.data ?? clsRes.data ?? []
      subjects.value = subRes.data?.data ?? subRes.data ?? []
      if (studyGroups.value.length) studyGroupId.value = String(studyGroups.value[0].id)
    } finally {
      loading.value = false
    }
  })

  return { studyGroupId, sectionId, classId, studyGroups, sections, classes, subjects, loading }
}
