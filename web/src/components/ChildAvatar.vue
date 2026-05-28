<template>
  <button
    type="button"
    class="child-avatar"
    :class="{ selected, button: Boolean(onSelect) }"
    :disabled="!onSelect"
    @click="onSelect?.(student)"
  >
    <span class="circle" :style="{ backgroundColor: color }">{{ initials }}</span>
    <span class="name">{{ childName(student) }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { childName } from '../composables/format'

const props = defineProps({
  student: { type: Object, required: true },
  selected: { type: Boolean, default: false },
  onSelect: { type: Function, default: null },
})

const colors = ['#2563eb', '#7c3aed', '#db2777', '#059669', '#d97706']

const color = computed(() => colors[(Number(props.student?.id) || 0) % colors.length])

const initials = computed(() => {
  const first = props.student?.first_name?.[0] ?? ''
  const last = props.student?.last_name?.[0] ?? ''
  return (first + last).toUpperCase() || '?'
})
</script>

<style scoped>
.child-avatar {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 6.5rem;
  background: none;
  border: none;
  padding: 0;
  margin: 0 0.75rem 0.5rem 0;
  text-align: center;
}
.child-avatar.button {
  cursor: pointer;
}
.child-avatar.button:hover .circle {
  transform: scale(1.03);
}
.child-avatar.button:disabled {
  cursor: default;
}
.circle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 4.5rem;
  height: 4.5rem;
  border-radius: 50%;
  color: #fff;
  font-size: 1.35rem;
  font-weight: 700;
  border: 3px solid transparent;
  transition: transform 0.15s ease;
}
.child-avatar.selected .circle {
  border-color: #1e40af;
}
.name {
  margin-top: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  line-height: 1.25;
}
.child-avatar.selected .name {
  color: #1e40af;
}
</style>
