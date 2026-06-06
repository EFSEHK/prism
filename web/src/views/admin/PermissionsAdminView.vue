<template>
  <div>
    <h1>Permissions</h1>
    <p class="muted">Superadmin only — configure role defaults and per-user extras.</p>

    <div class="tabs">
      <button type="button" :class="{ active: tab === 'role' }" @click="tab = 'role'">By role</button>
      <button type="button" :class="{ active: tab === 'user' }" @click="tab = 'user'">By user</button>
    </div>

    <div v-if="err" class="error">{{ err }}</div>
    <div v-if="msg" class="ok">{{ msg }}</div>

    <div v-if="tab === 'role'" class="card">
      <label class="field-label">Role</label>
      <SearchableSelect
        v-model="selectedRoleId"
        :options="roleOptions"
        placeholder="Select…"
        search-placeholder="Search roles…"
        @change="loadRolePerms"
      />
      <div v-if="selectedRoleId" class="perm-grid">
        <label v-for="p in permissions" :key="p.id" class="perm-item">
          <input type="checkbox" :value="p.id" v-model="rolePermIds" />
          {{ p.name }}
        </label>
      </div>
      <button v-if="selectedRoleId" type="button" class="primary" :disabled="saving" @click="saveRolePerms">
        {{ saving ? 'Saving…' : 'Save role permissions' }}
      </button>
    </div>

    <div v-else class="card">
      <label class="field-label">User</label>
      <SearchableSelect
        v-model="selectedUserId"
        :options="userOptions"
        placeholder="Select…"
        search-placeholder="Search users…"
        @change="loadUserPerms"
      />
      <p v-if="userRoles.length"><strong>Roles:</strong> {{ userRoles.map((r) => r.name).join(', ') }}</p>
      <p class="muted small">Check permissions granted directly to this user (in addition to role permissions).</p>
      <div v-if="selectedUserId" class="perm-grid">
        <label v-for="p in permissions" :key="p.id" class="perm-item">
          <input type="checkbox" :value="p.id" v-model="userDirectPermIds" />
          {{ p.name }}
        </label>
      </div>
      <button v-if="selectedUserId" type="button" class="primary" :disabled="saving" @click="saveUserPerms">
        {{ saving ? 'Saving…' : 'Save direct permissions' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../../api/client'
import SearchableSelect from '../../components/SearchableSelect.vue'

const tab = ref('role')
const roles = ref([])
const permissions = ref([])
const users = ref([])
const selectedRoleId = ref('')
const rolePermIds = ref([])
const selectedUserId = ref('')
const userRoles = ref([])
const userDirectPermIds = ref([])
const saving = ref(false)
const err = ref('')
const msg = ref('')

const roleOptions = computed(() =>
  roles.value.map((r) => ({ value: r.id, label: r.name })),
)
const userOptions = computed(() =>
  users.value.map((u) => ({ value: u.id, label: `${u.name} (${u.email})` })),
)

onMounted(async () => {
  try {
    const [r, p, u] = await Promise.all([
      api.get('/roles'),
      api.get('/permissions'),
      api.get('/users'),
    ])
    roles.value = r.data?.data ?? r.data ?? []
    permissions.value = p.data?.data ?? p.data ?? []
    users.value = u.data ?? []
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load'
  }
})

async function loadRolePerms() {
  if (!selectedRoleId.value) return
  const { data } = await api.get(`/roles/${selectedRoleId.value}/permissions`)
  const list = data?.data ?? data ?? []
  rolePermIds.value = list.map((x) => x.id)
}

async function saveRolePerms() {
  saving.value = true
  err.value = ''
  msg.value = ''
  try {
    await api.post(`/roles/${selectedRoleId.value}/permissions`, { permission_ids: rolePermIds.value })
    msg.value = 'Role permissions saved.'
  } catch (e) {
    err.value = e.response?.data?.message || 'Save failed'
  } finally {
    saving.value = false
  }
}

async function loadUserPerms() {
  if (!selectedUserId.value) return
  const { data: r } = await api.get(`/users/${selectedUserId.value}/roles`)
  userRoles.value = r?.data ?? r ?? []
  const { data: perms } = await api.get(`/users/${selectedUserId.value}/permissions`)
  const direct = perms?.direct_permissions ?? perms?.direct ?? perms ?? []
  userDirectPermIds.value = (Array.isArray(direct) ? direct : []).map((x) => x.id)
}

async function saveUserPerms() {
  saving.value = true
  err.value = ''
  msg.value = ''
  try {
    const { data: perms } = await api.get(`/users/${selectedUserId.value}/permissions`)
    const direct = perms?.direct_permissions ?? perms?.direct ?? perms ?? []
    const existing = (Array.isArray(direct) ? direct : []).map((x) => x.id)
    for (const pid of existing) {
      if (!userDirectPermIds.value.includes(pid)) {
        await api.post('/permissions/remove-from-user', { user_id: selectedUserId.value, permission_id: pid })
      }
    }
    for (const pid of userDirectPermIds.value) {
      if (!existing.includes(pid)) {
        await api.post('/permissions/assign-to-user', { user_id: selectedUserId.value, permission_id: pid })
      }
    }
    msg.value = 'Direct permissions saved.'
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
.tabs { display: flex; gap: 0.5rem; margin: 1rem 0; }
.tabs button { padding: 0.5rem 1rem; border: 1px solid #d4d4d8; background: #fff; cursor: pointer; border-radius: 6px; }
.tabs button.active { background: #2563eb; color: #fff; border-color: #2563eb; }
.perm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.35rem; margin: 1rem 0; max-height: 360px; overflow: auto; }
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
.field-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
}
</style>
