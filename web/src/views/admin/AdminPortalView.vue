<template>
  <div class="admin-portal">
    <header class="portal-hero">
      <p class="portal-kicker">EFSC-YA</p>
      <h1>Admin portal</h1>
      <p class="portal-lead">
        Configuration, imports, and system tools — everything that keeps the platform running.
      </p>
    </header>

    <div v-if="!visibleLinks.length" class="card empty-state">
      <p>You don’t have access to any admin tools.</p>
      <RouterLink to="/home" class="back-link">← Back to home</RouterLink>
    </div>

    <div v-else class="portal-grid">
      <RouterLink
        v-for="link in visibleLinks"
        :key="link.id"
        :to="link.path"
        class="portal-card"
        :style="{ '--accent': link.accent }"
      >
        <span class="portal-icon" aria-hidden="true">{{ link.icon }}</span>
        <div class="portal-card-body">
          <h2>{{ link.title }}</h2>
          <p>{{ link.description }}</p>
        </div>
        <span class="portal-arrow" aria-hidden="true">→</span>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { useAdminPortal } from '../../composables/useAdminPortal'

const { visibleLinks } = useAdminPortal()
</script>

<style scoped>
.admin-portal {
  margin: -0.25rem 0 2rem;
}

.portal-hero {
  background: linear-gradient(135deg, #18181b 0%, #27272a 55%, #1e3a5f 100%);
  color: #fafafa;
  border-radius: 12px;
  padding: 1.75rem 1.5rem 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 8px 24px rgb(0 0 0 / 0.12);
}

.portal-kicker {
  margin: 0 0 0.35rem;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #a1a1aa;
}

.portal-hero h1 {
  margin: 0 0 0.5rem;
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.portal-lead {
  margin: 0;
  max-width: 36rem;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #d4d4d8;
}

.portal-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1rem;
}

.portal-card {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  background: #fff;
  border: 1px solid #e4e4e7;
  border-left: 4px solid var(--accent, #2563eb);
  border-radius: 10px;
  padding: 1.1rem 1rem 1.1rem 0.9rem;
  text-decoration: none;
  color: inherit;
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.portal-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgb(0 0 0 / 0.08);
  border-color: #d4d4d8;
}

.portal-card:focus-visible {
  outline: 2px solid var(--accent, #2563eb);
  outline-offset: 2px;
}

.portal-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  background: color-mix(in srgb, var(--accent, #2563eb) 12%, white);
  color: var(--accent, #2563eb);
  font-size: 1.15rem;
  font-weight: 700;
}

.portal-card-body {
  flex: 1;
  min-width: 0;
}

.portal-card-body h2 {
  margin: 0 0 0.35rem;
  font-size: 1.05rem;
  font-weight: 600;
  color: #18181b;
}

.portal-card-body p {
  margin: 0;
  font-size: 0.85rem;
  line-height: 1.45;
  color: #71717a;
}

.portal-arrow {
  flex-shrink: 0;
  align-self: center;
  font-size: 1.1rem;
  color: #a1a1aa;
  transition: transform 0.15s ease, color 0.15s ease;
}

.portal-card:hover .portal-arrow {
  transform: translateX(3px);
  color: var(--accent, #2563eb);
}

.empty-state {
  text-align: center;
  padding: 2rem 1.5rem;
}

.empty-state p {
  margin: 0 0 1rem;
  color: #71717a;
}

.back-link {
  color: #2563eb;
  text-decoration: none;
  font-size: 0.9rem;
}

.back-link:hover {
  text-decoration: underline;
}
</style>
