import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/client'

const DASHBOARD_INCLUDE =
  'homework,timetable,marks,feed,fees,online_classes,leave,datesheet,notifications'

export const useParentStore = defineStore('parent', () => {
  const selectedChild = ref(null)
  const dashboard = ref(null)
  const loading = ref(false)

  async function loadDashboard(studentId = null) {
    loading.value = true
    try {
      const params = { include: DASHBOARD_INCLUDE }
      if (studentId) params.student_id = studentId
      const { data } = await api.get('/prism/parent/dashboard', { params })
      dashboard.value = data
      return data
    } finally {
      loading.value = false
    }
  }

  async function selectChild(child) {
    selectedChild.value = child
    await loadDashboard(child.id)
  }

  async function clearChild() {
    selectedChild.value = null
    await loadDashboard()
  }

  function clearAll() {
    selectedChild.value = null
    dashboard.value = null
    loading.value = false
  }

  return {
    selectedChild,
    dashboard,
    loading,
    loadDashboard,
    selectChild,
    clearChild,
    clearAll,
  }
})
