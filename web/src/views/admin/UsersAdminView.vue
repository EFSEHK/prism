<template>
  <div>
    <div class="head">
      <div>
        <h1>Users</h1>
        <p class="muted">Manage accounts and role assignments.</p>
        <p v-if="canShowAimsImportLink" class="muted small">
          <RouterLink to="/admin/aims-import">AIMS CSV import</RouterLink>
        </p>
      </div>
      <button type="button" class="primary" @click="openCreate">Add user</button>
    </div>

    <div v-if="err" class="error">{{ err }}</div>
    <div v-if="msg" class="ok">{{ msg }}</div>

    <div class="card table-wrap">
      <div class="user-filters">
        <div class="field picker-field">
          <span class="field-label">Role</span>
          <SearchableSelect
            v-model="roleFilter"
            :options="roleFilterOptions"
            placeholder="All roles"
            search-placeholder="Search roles…"
          />
        </div>
      </div>
      <div class="user-search-row">
        <div class="field user-search">
          <input v-model="search" type="search" placeholder="Search users…" />
        </div>
      </div>
      <table class="users-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Roles</th>
            <th v-if="canEditUsers">Actions</th>
            <th v-if="canImpersonateUsers">View as</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in filteredUsers" :key="u.id">
            <td>{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td>{{ formatRoles(u.roles) }}</td>
            <td v-if="canEditUsers">
              <button type="button" class="linkish" @click="openEdit(u)">Edit</button>
            </td>
            <td v-if="canImpersonateUsers">
              <button
                v-if="canImpersonate(u)"
                type="button"
                class="linkish"
                @click="viewAsUser(u)"
              >
                View as
              </button>
              <span v-else class="muted small">—</span>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!loading && !users.length" class="muted">No users found.</p>
      <p v-else-if="!loading && !filteredUsers.length" class="muted">No users match your filters.</p>
      <p v-if="loading" class="muted">Loading…</p>
    </div>

    <div v-if="modal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal" role="dialog" aria-modal="true">
        <h3>{{ editing ? 'Edit user' : 'Add user' }}</h3>
        <form class="modal-form" @submit.prevent="save">
          <div class="field">
            <span class="field-label">Name</span>
            <input v-model="form.name" required placeholder="Full name" autofocus />
          </div>
          <div class="field">
            <span class="field-label">Email</span>
            <input v-model="form.email" type="email" required placeholder="user@efsc-ya.com" />
          </div>
          <div class="field">
            <span class="field-label">Password</span>
            <input
              v-model="form.password"
              type="password"
              :required="!editing"
              :placeholder="editing ? 'Leave blank to keep current password' : 'Minimum 8 characters'"
            />
          </div>
          <div class="form-section">
            <span class="field-label">Roles</span>
            <div class="role-grid">
              <label v-for="r in assignableRoles" :key="r.id" class="checkbox-field">
                <input
                  v-model="form.roleIds"
                  type="checkbox"
                  :value="r.id"
                  :disabled="editing && !canEditUsers"
                />
                <span>{{ r.name }}</span>
              </label>
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" class="secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/client'
import SearchableSelect from '../../components/SearchableSelect.vue'
import { useRoles } from '../../composables/useRoles'
import { usePermissions } from '../../composables/usePermissions'
import { useViewAsStore } from '../../stores/viewAs'
import { useParentStore } from '../../stores/parent'

const router = useRouter()
const viewAs = useViewAsStore()
const parent = useParentStore()
const { isActuallySuperadmin, canEditUsers, canImpersonateUsers } = useRoles()
const { can, canImportAims } = usePermissions()
const canShowAimsImportLink = computed(() => canImportAims.value)

const users = ref([])
const roles = ref([])
const loading = ref(false)
const saving = ref(false)
const err = ref('')
const msg = ref('')
const modal = ref(false)
const editing = ref(null)
const form = ref({ name: '', email: '', password: '', roleIds: [] })
const search = ref('')
const roleFilter = ref('')

const filteredUsers = computed(() => {
  const q = search.value.trim().toLowerCase()
  return users.value.filter((u) => {
    const names = (u.roles || []).map((r) => r.name)
    if (roleFilter.value && !names.includes(roleFilter.value)) return false
    if (!q) return true
    return u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  })
})

const assignableRoles = computed(() => {
  const list = roles.value
  if (isActuallySuperadmin.value) return list
  return list.filter((r) => !['superadmin', 'developer'].includes(r.name))
})

const roleFilterOptions = computed(() =>
  roles.value.map((r) => ({ value: r.name, label: r.name })),
)

function formatRoles(userRoles = []) {
  return userRoles.map((r) => r.name).join(', ') || '—'
}

function canImpersonate(user) {
  const names = (user.roles || []).map((r) => r.name)
  return !names.some((n) => ['superadmin', 'developer'].includes(n))
}

async function load() {
  loading.value = true
  err.value = ''
  try {
    const [u, r] = await Promise.all([api.get('/users'), api.get('/roles')])
    users.value = u.data ?? []
    roles.value = r.data?.data ?? r.data ?? []
  } catch (e) {
    err.value = e.response?.data?.message || 'Failed to load users'
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  form.value = { name: '', email: '', password: '', roleIds: [] }
  modal.value = true
}

function openEdit(user) {
  editing.value = user
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    roleIds: (user.roles || []).map((r) => r.id),
  }
  modal.value = true
}

function closeModal() {
  modal.value = false
  editing.value = null
}

async function save() {
  saving.value = true
  err.value = ''
  msg.value = ''
  try {
    if (editing.value) {
      const payload = {
        name: form.value.name,
        email: form.value.email,
        role_ids: form.value.roleIds,
      }
      if (form.value.password) payload.password = form.value.password
      await api.put(`/users/${editing.value.id}`, payload)
      msg.value = 'User updated.'
    } else {
      await api.post('/users', {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        role_ids: form.value.roleIds,
      })
      msg.value = 'User created.'
    }
    closeModal()
    await load()
  } catch (e) {
    err.value = e.response?.data?.message || 'Save failed'
  } finally {
    saving.value = false
  }
}

async function viewAsUser(user) {
  try {
    const { data } = await api.get(`/users/${user.id}`)
    viewAs.startImpersonation(data)
    await parent.clearChild()
    if (data.roles?.some((r) => ['parent', 'student'].includes(r.name))) {
      try {
        await parent.loadDashboard()
      } catch {
        /* learner may have no linked data */
      }
    }
    router.push('/home')
  } catch (e) {
    err.value = e.response?.data?.message || 'Could not view as user'
  }
}

onMounted(load)
</script>

<style scoped>
.head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}
.muted { color: #71717a; }
.small { font-size: 0.8rem; }
.ok { color: #15803d; font-size: 0.9rem; margin-bottom: 0.5rem; }
.table-wrap { overflow-x: auto; }
.user-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 1rem;
  margin-bottom: 0.75rem;
}
.user-filters .picker-field {
  flex: 0 0 auto;
  min-width: 200px;
  max-width: 280px;
  margin-bottom: 0;
}
.user-filters .picker-field :deep(.searchable-select) {
  margin: 0;
  max-width: none;
}
.user-search-row {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1rem;
}
.user-search {
  width: 100%;
  max-width: 280px;
  margin: 0;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  min-width: 0;
}
.field-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #52525b;
  line-height: 1.2;
  min-height: 1rem;
}
.user-search input {
  display: block;
  width: 100%;
  margin: 0;
  padding: 0 0.65rem;
  height: 2.375rem;
  box-sizing: border-box;
  border: 1px solid #d4d4d8;
  border-radius: 6px;
  font-size: 0.9rem;
  background: #fff;
}
.user-search input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.12);
}
.users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.users-table th,
.users-table td {
  text-align: left;
  padding: 0.6rem 0.5rem;
  border-bottom: 1px solid #e4e4e7;
  vertical-align: top;
}
.linkish {
  background: none;
  border: none;
  color: #2563eb;
  cursor: pointer;
  font-size: 0.85rem;
  padding: 0;
}
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  background: rgb(0 0 0 / 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.modal {
  width: 100%;
  max-width: 420px;
  max-height: min(90vh, 640px);
  overflow-y: auto;
  background: #fff;
  border-radius: 10px;
  padding: 1.25rem;
  box-shadow: 0 12px 32px rgb(0 0 0 / 0.18);
}
.modal h3 {
  margin: 0 0 1rem;
  font-size: 1.05rem;
}
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.modal-form .field input {
  display: block;
  width: 100%;
  max-width: none;
  margin: 0;
  padding: 0 0.65rem;
  height: 2.375rem;
  box-sizing: border-box;
  border: 1px solid #d4d4d8;
  border-radius: 6px;
  font-size: 0.9rem;
  background: #fff;
}
.modal-form .field input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.12);
}
.form-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.role-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.35rem;
}
.checkbox-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #3f3f46;
  cursor: pointer;
  margin: 0;
}
.checkbox-field input[type='checkbox'] {
  width: 1rem;
  height: 1rem;
  margin: 0;
  flex-shrink: 0;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e4e4e7;
}
.modal-actions .primary,
.modal-actions .secondary {
  margin: 0;
}
</style>
