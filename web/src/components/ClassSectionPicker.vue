<template>
  <div class="row">
    <label>
      Class
      <select :value="classId" @change="$emit('update:classId', $event.target.value); $emit('classChange')">
        <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
      </select>
    </label>
    <label>
      Section
      <select :value="sectionId" @change="$emit('update:sectionId', $event.target.value)">
        <option v-for="s in sections" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
      </select>
    </label>
  </div>
</template>

<script setup>
defineProps({
  classes: { type: Array, default: () => [] },
  sections: { type: Array, default: () => [] },
  classId: String,
  sectionId: String,
})
defineEmits(['update:classId', 'update:sectionId', 'classChange'])
</script>

<style scoped>
.row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
select {
  display: block;
  margin-top: 0.25rem;
  padding: 0.4rem;
  min-width: 140px;
}
</style>
