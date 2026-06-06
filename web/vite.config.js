import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  // Target is the Laravel host only (no /api suffix). Vite forwards /api/* as-is.
  const rawTarget = env.VITE_API_PROXY_TARGET || 'http://127.0.0.1:8000'
  const proxyTarget = rawTarget.replace(/\/api\/?$/, '')

  return {
    plugins: [vue()],
    server: {
      port: 5173,
      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
        },
      },
    },
  }
})
