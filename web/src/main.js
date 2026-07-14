import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { installAuthInterceptor } from './api/client'
import { useAuthStore } from './stores/auth'

const pinia = createPinia()
const app = createApp(App)

app.use(pinia)
app.use(router)

installAuthInterceptor({
  getAuthStore: () => useAuthStore(pinia),
  router,
})

app.mount('#app')
