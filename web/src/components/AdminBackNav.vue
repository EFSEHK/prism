<template>
  <nav class="admin-back" aria-label="Admin navigation">
    <RouterLink to="/admin">← Admin portal</RouterLink>
  </nav>
</template>

<style scoped>
.admin-back {
  margin-bottom: 1rem;
}
.admin-back a {
  color: #2563eb;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
}
.admin-back a:hover {
  text-decoration: underline;
}
</style>
