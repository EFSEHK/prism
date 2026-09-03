<template>
  <div class="login-page">
    <div class="login-card">
      <header class="login-header">
        <h1>Change password</h1>
        <p class="subtitle">{{ auth.token ? 'Update your account password' : 'Update your password before signing in' }}</p>
      </header>

      <p class="policy-hint">
        Password must include upper and lower case letters, a number, and a special character (min. 8 characters). It must not contain 3 consecutive characters from your username.
      </p>

      <form class="login-form" @submit.prevent="submit">
        <div class="field">
          <label for="change-email">Admission no. / CNIC / Email</label>
          <input
            id="change-email"
            v-model="email"
            type="text"
            required
            autocomplete="username"
          />
        </div>

        <div class="field">
          <label for="current-password">Current password</label>
          <div class="password-wrap">
            <input
              id="current-password"
              v-model="currentPassword"
              :type="showCurrent ? 'text' : 'password'"
              required
              autocomplete="current-password"
            />
            <button
              type="button"
              class="password-toggle"
              :aria-label="showCurrent ? 'Hide password' : 'Show password'"
              @click="showCurrent = !showCurrent"
            >
              <svg v-if="showCurrent" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                <line x1="1" y1="1" x2="23" y2="23" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
        </div>

        <div class="field">
          <label for="new-password">New password</label>
          <div class="password-wrap">
            <input
              id="new-password"
              v-model="newPassword"
              :type="showNew ? 'text' : 'password'"
              required
              autocomplete="new-password"
            />
            <button
              type="button"
              class="password-toggle"
              :aria-label="showNew ? 'Hide password' : 'Show password'"
              @click="showNew = !showNew"
            >
              <svg v-if="showNew" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                <line x1="1" y1="1" x2="23" y2="23" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
        </div>

        <div class="field">
          <label for="confirm-password">Confirm new password</label>
          <div class="password-wrap">
            <input
              id="confirm-password"
              v-model="confirmPassword"
              :type="showConfirm ? 'text' : 'password'"
              required
              autocomplete="new-password"
            />
            <button
              type="button"
              class="password-toggle"
              :aria-label="showConfirm ? 'Hide password' : 'Show password'"
              @click="showConfirm = !showConfirm"
            >
              <svg v-if="showConfirm" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                <line x1="1" y1="1" x2="23" y2="23" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
        </div>

        <p v-if="success" class="success-banner" role="status">{{ success }}</p>
        <p v-if="err" class="error-banner" role="alert">{{ err }}</p>

        <button type="submit" class="submit-btn" :disabled="submitting">
          {{ submitting ? 'Updating…' : 'Update password' }}
        </button>
      </form>

      <p class="auth-link">
        <RouterLink :to="backTo">{{ backLabel }}</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api/client'
import { useAuthStore } from '../stores/auth'
import { validatePassword } from '../utils/passwordPolicy'

const auth = useAuthStore()
const email = ref('')
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)
const err = ref('')
const success = ref('')
const submitting = ref(false)

const backTo = computed(() => (auth.token ? '/home' : '/login'))
const backLabel = computed(() => (auth.token ? 'Back' : 'Back to sign in'))

onMounted(() => {
  if (auth.user?.email) {
    email.value = auth.user.email
  }
})

async function submit() {
  err.value = ''
  success.value = ''

  if (newPassword.value !== confirmPassword.value) {
    err.value = 'New passwords do not match.'
    return
  }

  const policyError = validatePassword(newPassword.value, email.value.trim())
  if (policyError) {
    err.value = policyError
    return
  }

  submitting.value = true
  try {
    const { data } = await api.post('/change-password', {
      email: email.value.trim(),
      current_password: currentPassword.value.trim(),
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    success.value = data?.message || 'Password updated successfully.'
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (e) {
    err.value = e.response?.data?.message
      || e.response?.data?.errors?.password?.[0]
      || 'Could not update password.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.login-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 2rem 1.5rem;
  background: #f4f4f5;
}

.login-card {
  width: 100%;
  max-width: 400px;
  background: #fff;
  border-radius: 12px;
  padding: 2rem 2rem 1.75rem;
  box-shadow:
    0 1px 3px rgb(0 0 0 / 0.06),
    0 8px 24px rgb(0 0 0 / 0.08);
  border: 1px solid #e4e4e7;
}

.login-header {
  margin-bottom: 1rem;
  text-align: center;
}

.login-header h1 {
  margin: 0 0 0.35rem;
  font-size: 1.625rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: #18181b;
}

.subtitle {
  margin: 0;
  font-size: 0.9rem;
  color: #71717a;
  font-weight: 400;
}

.policy-hint {
  margin: 0 0 1.25rem;
  font-size: 0.8125rem;
  line-height: 1.45;
  color: #71717a;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.field label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #3f3f46;
  letter-spacing: 0.01em;
}

.login-form input {
  display: block;
  width: 100%;
  max-width: none;
  margin: 0;
  padding: 0.65rem 0.75rem;
  font-size: 0.9375rem;
  line-height: 1.4;
  color: #18181b;
  background: #fafafa;
  border: 1px solid #d4d4d8;
  border-radius: 8px;
  box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.login-form input:focus {
  outline: none;
  background: #fff;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgb(37 99 235 / 0.15);
}

.password-wrap {
  position: relative;
}

.password-wrap input {
  padding-right: 2.75rem;
}

.password-toggle {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.75rem;
  padding: 0;
  margin: 0;
  border: none;
  border-radius: 0 8px 8px 0;
  background: transparent;
  color: #71717a;
  cursor: pointer;
}

.success-banner {
  margin: 0;
  padding: 0.6rem 0.75rem;
  font-size: 0.875rem;
  color: #15803d;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
}

.error-banner {
  margin: 0;
  padding: 0.6rem 0.75rem;
  font-size: 0.875rem;
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
}

.submit-btn {
  width: 100%;
  margin: 0.25rem 0 0;
  padding: 0.7rem 1rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  background: #2563eb;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.submit-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.auth-link {
  margin: 1.25rem 0 0;
  text-align: center;
  font-size: 0.875rem;
}

.auth-link a {
  color: #2563eb;
  font-weight: 600;
  text-decoration: none;
}

.auth-link a:hover {
  text-decoration: underline;
}
</style>
