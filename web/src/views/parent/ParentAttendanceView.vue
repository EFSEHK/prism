<template>
  <div>
    <h1>Attendance</h1>
    <p v-if="child" class="muted">For {{ childName(child) }}</p>
    <div class="card">
      <label>Month (YYYY-MM)
        <input v-model="month" type="month" @change="load" />
      </label>
      <p v-if="loading">Loading…</p>
      <p v-else-if="err" class="error">{{ err }}</p>
      <p v-else-if="days.length === 0" class="muted">No records for this month.</p>
      <div v-for="(d, i) in days" :key="i" class="item">
        <strong>{{ formatDate(d.date) }}</strong>
        <span class="muted"> · {{ d.status }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import api from '../../api/client'
import { useParentStore } from '../../stores/parent'
import { childName, formatDate } from '../../composables/format'

const parent = useParentStore()
const child = computed(() => parent.selectedChild)
const month = ref(new Date().toISOString().slice(0, 7))
const days = ref([])
const loading = ref(false)
const err = ref('')

async function load() {
  if (!child.value?.id) return
  loading.value = true
  err.value = ''
  try {
    const { data } = await api.get('/efsc/attendance/reports/monthly', {
      params: { student_id: child.value.id, month: month.value },
    })
    days.value = data.days || []
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load'
    days.value = []
  } finally {
    loading.value = false
  }
}

watch(child, load, { immediate: true })
watch(month, load)
</script>

<style scoped>
.item { padding: 0.5rem 0; border-bottom: 1px solid #f4f4f5; }
.muted { color: #71717a; }
</style>
