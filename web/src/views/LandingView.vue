<template>
  <div class="landing">
    <section class="hero-panel">
      <div class="brand-mark">EF</div>
      <div class="eyebrow">Elementary Foundation School Chakwal — Youth Academy</div>
      <h1 class="headline">EFSC-YA School Portal</h1>
      <p class="subtitle">
        A unified school management platform for staff and families. Parents can follow homework,
        marks, attendance, fees, and announcements. Staff can manage academics, attendance, assessments,
        and school communications from one place.
      </p>

      <div class="links-grid">
        <article class="link-card">
          <strong>Web application</strong>
          <p>Staff dashboard and learner portal in the browser.</p>
          <RouterLink to="/login" class="button button-primary">Sign in</RouterLink>
        </article>

        <article class="link-card">
          <strong>Android mobile app</strong>
          <p>Parent and student mobile app for phones (install APK).</p>
          <a
            v-if="androidApkUrl"
            :href="androidApkUrl"
            class="button button-primary"
            download
          >
            Download Android app
          </a>
          <span v-else class="button button-secondary unavailable">
            {{ loadingRelease ? 'Checking…' : 'APK not uploaded yet' }}
          </span>
        </article>

        <article class="link-card">
          <strong>iOS mobile app</strong>
          <p>iPhone/iPad support is planned. Not available in this release.</p>
          <span class="button button-secondary unavailable">Coming later</span>
        </article>
      </div>

      <p class="muted-note">
        <template v-if="androidApkUrl && release">
          Current Android release: <strong>{{ release.version }}</strong>
          (build {{ release.version_code }}).
        </template>
        <template v-else>
          Sign in to access the school portal, or download the Android app when available.
        </template>
      </p>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../api/client'

const loadingRelease = ref(true)
const release = ref(null)
const androidApkUrl = ref(null)

function resolveApiAssetUrl(url) {
  if (!url) return null
  if (/^https?:\/\//i.test(url)) return url

  const apiBase = import.meta.env.VITE_API_URL || ''
  if (apiBase) {
    const origin = apiBase.replace(/\/api\/?$/, '')
    return `${origin}${url.startsWith('/') ? url : `/${url}`}`
  }

  const proxy = (import.meta.env.VITE_API_PROXY_TARGET || 'http://prism.test').replace(/\/$/, '')
  return `${proxy}${url.startsWith('/') ? url : `/${url}`}`
}

onMounted(async () => {
  try {
    const { data } = await api.get('/mobile/version')
    release.value = data
    androidApkUrl.value = resolveApiAssetUrl(data.apk_url)
  } catch {
    release.value = null
    androidApkUrl.value = null
  } finally {
    loadingRelease.value = false
  }
})
</script>

<style scoped>
.landing {
  min-height: 100vh;
  padding: 2.5rem 1.25rem 3.5rem;
  background:
    radial-gradient(circle at top left, rgba(37, 99, 235, 0.16), transparent 28%),
    radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.18), transparent 24%),
    #f4f7fb;
  box-sizing: border-box;
}

.hero-panel {
  max-width: 960px;
  margin: 0 auto;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 28px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
  padding: 40px 34px;
  backdrop-filter: blur(14px);
}

.brand-mark {
  width: 56px;
  height: 56px;
  display: grid;
  place-items: center;
  border-radius: 18px;
  background: linear-gradient(135deg, #2563eb, #0f766e);
  color: #fff;
  font-weight: 700;
  font-size: 1.1rem;
  margin-bottom: 22px;
}

.eyebrow {
  display: inline-flex;
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 16px;
}

.headline {
  margin: 0;
  font-size: clamp(2rem, 5vw, 3rem);
  line-height: 1.1;
  letter-spacing: -0.03em;
  color: #0f172a;
}

.subtitle {
  margin: 18px 0 0;
  font-size: 1.08rem;
  line-height: 1.75;
  color: #64748b;
  max-width: 680px;
}

.links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
  margin-top: 32px;
}

.link-card {
  padding: 22px;
  border-radius: 20px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: rgba(248, 250, 252, 0.9);
}

.link-card strong {
  display: block;
  margin-bottom: 8px;
  font-size: 1.05rem;
  color: #0f172a;
}

.link-card p {
  margin: 0 0 16px;
  color: #64748b;
  line-height: 1.6;
  font-size: 0.95rem;
}

.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  gap: 8px;
  padding: 12px 18px;
  border-radius: 14px;
  border: 1px solid transparent;
  font-weight: 600;
  font-size: 0.95rem;
  text-decoration: none;
  box-sizing: border-box;
  transition: transform 0.2s ease;
}

.button:hover {
  transform: translateY(-1px);
}

.button-primary {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 16px 30px rgba(37, 99, 235, 0.24);
}

.button-secondary {
  background: rgba(255, 255, 255, 0.78);
  color: #0f172a;
  border-color: rgba(15, 23, 42, 0.08);
}

.unavailable {
  opacity: 0.72;
  pointer-events: none;
}

.muted-note {
  margin-top: 28px;
  color: #64748b;
  font-size: 0.92rem;
  line-height: 1.6;
}

@media (max-width: 640px) {
  .hero-panel {
    padding: 28px 20px;
  }
}
</style>
