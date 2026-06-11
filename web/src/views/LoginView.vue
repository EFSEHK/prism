<template>
  <div class="card login">
    <h1>EFSC-YA</h1>
    <p class="muted">School platform — sign in</p>
    <form @submit.prevent="submit">
      <label>Admission no. / CNIC / Email
        <input v-model="email" type="text" required autocomplete="username" />
      </label>
      <label>Password <input v-model="password" type="password" required /></label>
      <p v-if="err" class="error">{{ err }}</p>
      <button type="submit" class="primary">Login</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const email = ref('incharge@efsc-ya.test')
const password = ref('Test.123')
const err = ref('')

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

<style scoped>
.login { max-width: 360px; margin: 4rem auto; }
.muted { color: #71717a; }
</style>
