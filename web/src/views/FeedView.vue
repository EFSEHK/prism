<template>
  <div>
    <h1>Events & announcements</h1>
    <div class="card">
      <label>Type
        <select v-model="form.type">
          <option value="announcement">announcement</option>
          <option value="event">event</option>
          <option value="achievement">achievement</option>
        </select>
      </label>
      <label>Title <input v-model="form.title" /></label>
      <label>Body <textarea v-model="form.body" rows="3" /></label>
      <label>Scope
        <select v-model="form.scope">
          <option value="school">school</option>
          <option value="class">class</option>
        </select>
      </label>
      <label v-if="form.scope === 'class'">Class ID <input v-model="form.scope_school_class_id" type="number" /></label>
      <label><input v-model="form.publish" type="checkbox" /> Publish now (notify after approval)</label>
      <button type="button" class="primary" @click="create">Post</button>
      <p v-if="msg" class="ok">{{ msg }}</p>
    </div>
    <div class="card">
      <div v-for="f in items" :key="f.id" class="item">
        <strong>{{ f.title }}</strong> ({{ f.type }}) — {{ f.scope }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import api from '../api/client'
import { paginated } from '../composables/format'

const form = reactive({
  type: 'announcement',
  title: '',
  body: '',
  scope: 'school',
  scope_school_class_id: '1',
  publish: true,
})
const items = ref([])
const msg = ref('')

async function load() {
  const { data } = await api.get('/efsc/feed')
  items.value = paginated(data)
}

async function create() {
  await api.post('/efsc/feed', {
    ...form,
    scope_school_class_id: form.scope === 'class' ? Number(form.scope_school_class_id) : null,
    publish: form.publish,
  })
  msg.value = 'Posted.'
  await load()
}

onMounted(load)
</script>

<style scoped>
textarea { display: block; width: 100%; max-width: 480px; }
.item { padding: 0.35rem 0; }
.ok { color: #15803d; }
</style>
