import { ref, onMounted } from 'vue'
import api from '../api/client'

export function useAcademic() {
  const classes = ref([])
  const subjects = ref([])
  const loading = ref(true)
  const error = ref('')

  const classId = ref('')
  const sectionId = ref('')

  async function load() {
    loading.value = true
    error.value = ''
    try {
      const [c, s] = await Promise.all([
        api.get('/prism/academic/classes'),
        api.get('/prism/academic/subjects').catch(() => ({ data: [] })),
      ])
      classes.value = c.data || []
      subjects.value = s.data || []
      if (classes.value.length && !classId.value) {
        classId.value = String(classes.value[0].id)
        const sec = classes.value[0].sections?.[0]
        if (sec) sectionId.value = String(sec.id)
      }
    } catch (e) {
      error.value = e.response?.data?.message || 'Failed to load classes'
    } finally {
      loading.value = false
    }
  }

  function onClassChange() {
    const cls = classes.value.find((c) => String(c.id) === classId.value)
    const sec = cls?.sections?.[0]
    sectionId.value = sec ? String(sec.id) : ''
  }

  const sections = () => {
    const cls = classes.value.find((c) => String(c.id) === classId.value)
    return cls?.sections || []
  }

  onMounted(load)

  return {
    classes,
    subjects,
    loading,
    error,
    classId,
    sectionId,
    onClassChange,
    sections,
    load,
  }
}

export function paginated(data) {
  return data?.data ?? data ?? []
}
