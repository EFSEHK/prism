<template>
  <div ref="root" class="searchable-select">
    <button type="button" class="trigger" :class="{ open, disabled }" :aria-expanded="open" :disabled="disabled" @click="toggle">
      <span :class="{ placeholder: !selectedLabel }">{{ selectedLabel || placeholder }}</span>
      <span class="caret" aria-hidden="true">▾</span>
    </button>
    <div v-if="open" class="dropdown">
      <input ref="searchInput" v-model="query" type="text" class="search" :placeholder="searchPlaceholder" @keydown.esc="close" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="selectHighlighted" />
      <ul class="options" role="listbox">
        <li v-if="allowEmpty" role="option" class="option" :class="{ active: highlightIndex === -1, selected: modelValue === '' || modelValue == null }" @click="select(null)">
          {{ placeholder }}
        </li>
        <li v-for="(opt, i) in filtered" :key="opt.value" role="option" class="option" :class="{ active: i === highlightIndex, selected: opt.value == modelValue }" @click="select(opt)">
          {{ opt.label }}
        </li>
        <li v-if="!filtered.length" class="empty">
          {{ query.trim() ? 'No results' : (options.length ? 'No results' : emptyOptionsText) }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select…' },
  searchPlaceholder: { type: String, default: 'Search…' },
  allowEmpty: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  emptyOptionsText: { type: String, default: 'Nothing available' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const root = ref(null)
const searchInput = ref(null)
const open = ref(false)
const query = ref('')
const highlightIndex = ref(0)

const selectedLabel = computed(() => {
  if (props.modelValue === '' || props.modelValue == null) return ''
  const match = props.options.find((o) => o.value == props.modelValue)
  return match?.label ?? ''
})

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return props.options
  return props.options.filter((o) => o.label.toLowerCase().includes(q))
})

watch(open, async (isOpen) => {
  if (isOpen) {
    query.value = ''
    highlightIndex.value = props.allowEmpty ? -1 : 0
    await nextTick()
    searchInput.value?.focus()
  }
})

watch(filtered, () => {
  if (highlightIndex.value >= filtered.value.length) {
    highlightIndex.value = Math.max(props.allowEmpty ? -1 : 0, filtered.value.length - 1)
  }
})

function toggle() {
  if (props.disabled) return
  open.value = !open.value
}

function close() {
  open.value = false
}

function select(opt) {
  const value = opt?.value ?? ''
  emit('update:modelValue', value)
  emit('change', value)
  close()
}

function move(delta) {
  const min = props.allowEmpty ? -1 : 0
  const max = filtered.value.length - 1
  highlightIndex.value = Math.min(max, Math.max(min, highlightIndex.value + delta))
}

function selectHighlighted() {
  if (highlightIndex.value === -1) {
    select(null)
    return
  }
  const opt = filtered.value[highlightIndex.value]
  if (opt) select(opt)
}

function onClickOutside(e) {
  if (root.value && !root.value.contains(e.target)) close()
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<style scoped>
.searchable-select {
  position: relative;
  width: 100%;
  max-width: 420px;
  margin: 0.5rem 0 1rem;
}

.trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.65rem;
  border: 1px solid #d4d4d8;
  border-radius: 6px;
  background: #fff;
  font-size: 0.9rem;
  text-align: left;
  cursor: pointer;
}

.trigger.open,
.trigger:focus-visible {
  border-color: #2563eb;
  outline: none;
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.15);
}

.trigger.disabled,
.trigger:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  background: #f4f4f5;
}

.placeholder {
  color: #71717a;
}

.caret {
  color: #71717a;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.dropdown {
  position: absolute;
  z-index: 20;
  top: calc(100% + 0.25rem);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #d4d4d8;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
  overflow: hidden;
}

.search {
  display: block;
  width: 100%;
  max-width: none;
  margin: 0;
  padding: 0.5rem 0.65rem;
  border: none;
  border-bottom: 1px solid #e4e4e7;
  border-radius: 0;
  font-size: 0.9rem;
}

.search:focus {
  outline: none;
}

.options {
  list-style: none;
  margin: 0;
  padding: 0.25rem 0;
  max-height: 240px;
  overflow-y: auto;
}

.option {
  padding: 0.45rem 0.65rem;
  font-size: 0.9rem;
  cursor: pointer;
}

.option:hover,
.option.active {
  background: #eff6ff;
}

.option.selected {
  font-weight: 600;
  color: #1d4ed8;
}

.empty {
  padding: 0.65rem;
  color: #71717a;
  font-size: 0.85rem;
}
</style>
