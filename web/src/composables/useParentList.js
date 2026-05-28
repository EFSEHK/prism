import { ref, onMounted } from 'vue'
import api from '../api/client'
import { paginated } from './format'

export function useParentList(endpoint, params = {}) {
  const items = ref([])
  const loading = ref(true)
  const err = ref('')

  async function load() {
    loading.value = true
    err.value = ''
    try {
      const { data } = await api.get(endpoint, { params })
      items.value = paginated(data)
    } catch (e) {
      err.value = e.response?.data?.message || 'Failed to load'
      items.value = []
    } finally {
      loading.value = false
    }
  }

  onMounted(load)

  return { items, loading, err, load }
}
