<template>
  <div class="card" style="max-width: 400px; margin: 3rem auto">
    <h1>PRISM Web</h1>
    <p>Sign in with your school account.</p>
    <form @submit.prevent="submit">
      <label>Email</label>
      <input v-model="email" type="email" autocomplete="username" required />
      <label>Password</label>
      <input v-model="password" type="password" autocomplete="current-password" required />
      <p v-if="err" class="error">{{ err }}</p>
      <button type="submit" class="primary">Login</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('parent@school.test')
const password = ref('Parent.123')
const err = ref('')
const auth = useAuthStore()
const router = useRouter()

async function submit() {
  err.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push('/')
  } catch (e) {
    err.value = e.response?.data?.message || 'Login failed'
  }
}
</script>
