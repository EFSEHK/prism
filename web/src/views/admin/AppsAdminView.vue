<template>
  <div>
    <h1>Apps</h1>
    <p class="muted">
      Choose which apps each role can see, and whether they are accessible or greyed out as “Coming soon”.
    </p>

    <div v-if="err" class="error">{{ err }}</div>
    <div v-if="msg" class="ok">{{ msg }}</div>

    <div class="toolbar">
      <button type="button" class="primary" :disabled="saving || loading" @click="save">
        {{ saving ? 'Saving…' : 'Save changes' }}
      </button>
      <button type="button" :disabled="loading || saving" @click="load">Refresh</button>
    </div>

    <div v-if="loading && !rows.length" class="muted">Loading…</div>

    <div v-for="row in rows" :key="row.id" class="card app-card" :class="{ locked: !row.editable }">
      <div class="app-head">
        <div>
          <h2>{{ row.label }}</h2>
          <p class="muted small">{{ row.id }} · {{ (row.platforms || []).join(', ') }}</p>
        </div>
        <div class="status-field">
          <label class="field-label">Access</label>
          <select v-model="row.status" :disabled="!row.editable || saving">
            <option value="live">Accessible</option>
            <option value="coming_soon">Coming soon (greyed out)</option>
            <option value="disabled">Hidden</option>
          </select>
        </div>
      </div>

      <p v-if="!row.editable" class="muted small">Built-in admin app — visibility is fixed.</p>

      <template v-else>
        <label class="field-label">Visible to roles</label>
        <div class="perm-grid">
          <label v-for="role in roles" :key="`${row.id}-${role}`" class="perm-item">
            <input
              type="checkbox"
              :value="role"
              :checked="row.visible_roles.includes(role)"
              :disabled="saving || (row.locked_roles || []).includes(role)"
              @change="toggleRole(row, role, $event.target.checked)"
            />
            {{ role }}
          </label>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../../api/client'
import { useModulesStore } from '../../stores/modules'

const modulesStore = useModulesStore()
const rows = ref([])
const roles = ref([])
const loading = ref(false)
const saving = ref(false)
const err = ref('')
const msg = ref('')

onMounted(load)

async function load() {
  loading.value = true
  err.value = ''
  msg.value = ''
  try {
    const { data } = await api.get('/efsc/apps')
    rows.value = (data?.data ?? []).map((m) => ({
      ...m,
      visible_roles: [...(m.visible_roles || [])],
      locked_roles: [...(m.locked_roles || [])],
    }))
    roles.value = data?.roles ?? []
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load apps'
  } finally {
    loading.value = false
  }
}

function toggleRole(row, role, checked) {
  if ((row.locked_roles || []).includes(role)) return
  const set = new Set(row.visible_roles)
  if (checked) set.add(role)
  else set.delete(role)
  row.visible_roles = [...set]
}

async function save() {
  saving.value = true
  err.value = ''
  msg.value = ''
  try {
    const payload = {
      modules: rows.value
        .filter((r) => r.editable)
        .map((r) => ({
          id: r.id,
          status: r.status,
          visible_roles: r.visible_roles,
        })),
    }
    const { data } = await api.put('/efsc/apps', payload)
    rows.value = (data?.data ?? []).map((m) => ({
      ...m,
      visible_roles: [...(m.visible_roles || [])],
      locked_roles: [...(m.locked_roles || [])],
    }))
    if (data?.roles) roles.value = data.roles
    msg.value = data?.message || 'App visibility saved.'
    try {
      await modulesStore.fetchModules('web')
    } catch {
      /* nav refresh best-effort */
    }
  } catch (e) {
    err.value = e.response?.data?.message || 'Save failed'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.muted { color: #71717a; }
.small { font-size: 0.85rem; }
.ok { color: #15803d; font-size: 0.9rem; }
.error { color: #b91c1c; margin: 0.5rem 0; }
.toolbar { display: flex; gap: 0.5rem; margin: 1rem 0; flex-wrap: wrap; }
.app-card { margin-bottom: 1rem; }
.app-card.locked { opacity: 0.92; }
.app-head {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  flex-wrap: wrap;
  margin-bottom: 0.75rem;
}
.app-head h2 { margin: 0; font-size: 1.1rem; }
.status-field { min-width: 220px; }
.field-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
}
.perm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.35rem;
  margin: 0.5rem 0 0;
}
.perm-item {
  font-size: 0.85rem;
  display: flex;
  gap: 0.35rem;
  align-items: flex-start;
  justify-content: flex-start;
  text-align: left;
  width: 100%;
  cursor: pointer;
}
.perm-item input[type="checkbox"] {
  display: inline-block;
  width: auto;
  margin: 0.15rem 0 0;
  flex-shrink: 0;
}
</style>
