<template>
  <div>
    <h1>Leave requests</h1>
    <p v-if="child" class="muted">For {{ childName(child) }}</p>
    <div class="card">
      <h2>New request</h2>
      <label>Start date <input v-model="startDate" type="date" /></label>
      <label>End date <input v-model="endDate" type="date" /></label>
      <label>Reason <textarea v-model="reason" rows="2" /></label>
      <button type="button" class="primary" @click="submit">Submit</button>
      <p v-if="ok" class="ok">{{ ok }}</p>
      <p v-if="submitErr" class="error">{{ submitErr }}</p>
    </div>
    <div class="card">
      <h2>Your requests</h2>
      <p v-if="loading">Loading…</p>
      <p v-else-if="err" class="error">{{ err }}</p>
      <div v-for="l in items" :key="l.id" class="item">
        <strong>{{ childName(l.student) }}</strong>
        <span class="muted">
          · {{ formatDate(l.start_date) }} – {{ formatDate(l.end_date) }} · {{ l.status }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '../../api/client'
import { useParentStore } from '../../stores/parent'
import { useParentList } from '../../composables/useParentList'
import { childName, formatDate } from '../../composables/format'

const parent = useParentStore()
const child = computed(() => parent.selectedChild)
const { items, loading, err, load } = useParentList('/prism/leave-requests')

const startDate = ref('')
const endDate = ref('')
const reason = ref('')
const ok = ref('')
const submitErr = ref('')

async function submit() {
  ok.value = ''
  submitErr.value = ''
  try {
    await api.post('/prism/leave-requests', {
      student_id: child.value?.id,
      start_date: startDate.value,
      end_date: endDate.value,
      reason: reason.value,
    })
    ok.value = 'Leave request submitted.'
    startDate.value = ''
    endDate.value = ''
    reason.value = ''
    await load()
  } catch (e) {
    submitErr.value = e.response?.data?.message || 'Submit failed'
  }
}
</script>

<style scoped>
.item { padding: 0.5rem 0; border-bottom: 1px solid #f4f4f5; }
.muted { color: #71717a; }
.ok { color: #15803d; }
</style>
