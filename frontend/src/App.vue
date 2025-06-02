<script setup>
import { onMounted, watch } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const route = useRoute();

// Следим за изменениями параметров запроса в URL
watch(
  () => route.query,
  (newQuery) => {
    if (newQuery.auth_status || newQuery.message) {
      authStore.handleAuthCallback(new URLSearchParams(newQuery));
    }
  },
  { immediate: true } // Запустить наблюдателя сразу при монтировании компонента
);

// При монтировании компонента App.vue
onMounted(async () => {
  // 1. Получить CSRF-токен от Laravel
  await authStore.fetchCsrfToken();
  // 2. Проверить статус авторизации Zoho
  await authStore.checkZohoAuthStatus();
});
</script>

<template>
  <header>
    <div class="wrapper">
      <nav>
      </nav>
    </div>
  </header>

  <main class="container">
    <RouterView />
  </main>
</template>

<style scoped>
/* Базовые стили для макета */
.container {
  padding: 20px;
}
</style>
