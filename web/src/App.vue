<template>
  <div class="app">
    <header v-if="auth.token" class="top">
      <span class="brand">PRISM</span>
      <nav>
        <RouterLink to="/">Dashboard</RouterLink>
        <RouterLink v-if="canApprove" to="/approvals">Approvals</RouterLink>
        <button type="button" class="link" @click="logout">Logout</button>
      </nav>
    </header>
    <main>
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const auth = useAuthStore()
const router = useRouter()

const canApprove = computed(() => {
  const names = (auth.user?.roles || []).map((r) => r.name)
  return names.some((n) =>
    ['superadmin', 'admin', 'principal', 'vice_principal', 'section_head', 'class_incharge'].includes(n)
  )
})

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style>
:root {
  font-family: system-ui, sans-serif;
  color: #1a1a1a;
  background: #f4f4f5;
}
.app {
  min-height: 100vh;
}
.top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1.25rem;
  background: #18181b;
  color: #fafafa;
}
.top a,
.link {
  color: #a1a1aa;
  margin-left: 1rem;
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
}
.top a.router-link-active {
  color: #fff;
}
.brand {
  font-weight: 700;
}
main {
  max-width: 960px;
  margin: 0 auto;
  padding: 1.5rem;
}
.card {
  background: #fff;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.08);
}
.error {
  color: #b91c1c;
  font-size: 0.9rem;
}
button.primary {
  background: #2563eb;
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
}
input {
  display: block;
  width: 100%;
  max-width: 320px;
  margin: 0.5rem 0 1rem;
  padding: 0.5rem;
}
label {
  font-size: 0.85rem;
  font-weight: 600;
}
</style>
