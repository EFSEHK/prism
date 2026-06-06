import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api/client'

export const useParentStore = defineStore('parent', () => {
  const selectedChild = ref(JSON.parse(localStorage.getItem('efsc_child') || 'null'))
  const dashboard = ref(null)

  async function loadDashboard(studentId = null) {
    const params = {
      include: 'homework,timetable,marks,broadcasts,fees,online_classes,leave,datesheet,notifications',
    }
    if (studentId) params.student_id = studentId
    const { data } = await api.get('/efsc/learner/dashboard', { params })
    dashboard.value = data
    return data
  }

  function selectChild(child) {
    selectedChild.value = child
    localStorage.setItem('efsc_child', JSON.stringify(child))
  }

  async function clearChild() {
    selectedChild.value = null
    localStorage.removeItem('efsc_child')
    dashboard.value = null
  }

  return { selectedChild, dashboard, loadDashboard, selectChild, clearChild }
})
