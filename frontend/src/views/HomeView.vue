<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth'; // Импортируем наш Pinia Store

const authStore = useAuthStore();

// Вычисляемое свойство для удобного доступа к статусу авторизации
const isZohoAuthenticated = computed(() => authStore.isZohoAuthenticated);
// Вычисляемое свойство для удобного доступа к сообщению об авторизации
const authMessage = computed(() => authStore.getAuthMessage);
// Вычисляемое свойство для состояния загрузки
const isLoadingAuth = computed(() => authStore.isLoadingAuth);

/**
 * Обработчик нажатия кнопки "Авторизоваться с Zoho".
 * Запускает процесс перенаправления на бэкенд для OAuth авторизации.
 */
const startZohoAuth = () => {
  authStore.redirectToZohoAuth();
};
</script>

<template>
  <div class="home-view">
    <h1>Приложение Zoho Inventory</h1>

    <div v-if="isLoadingAuth" class="loading-message">
      <p>Проверка статуса авторизации Zoho...</p>
    </div>

    <div v-else-if="!isZohoAuthenticated" class="auth-required">
      <p>{{ authMessage }}</p>
      <button @click="startZohoAuth" class="auth-button">Авторизоваться с Zoho</button>
      <p class="small-text">Вы будете перенаправлены на сайт Zoho для предоставления доступа.</p>
    </div>

    <div v-else class="authenticated-content">
      <h2>Вы успешно авторизованы в Zoho Inventory!</h2>
      <p>{{ authMessage }}</p>
      <p>Теперь вы можете приступить к созданию заказа на продажу.</p>
    </div>
  </div>
</template>

<style scoped>
.home-view {
  text-align: center;
  padding: 40px;
  max-width: 800px;
  margin: 0 auto;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h1 {
  color: #333;
  margin-bottom: 30px;
  font-size: 2.5em;
}

h2 {
  color: #28a745; /* Зеленый цвет для успешного статуса */
  margin-bottom: 20px;
  font-size: 1.8em;
}

.loading-message, .auth-required, .authenticated-content {
  margin-top: 30px;
  padding: 25px;
  border-radius: 6px;
}

.loading-message {
  background-color: #e0f7fa;
  border: 1px solid #b2ebf2;
  color: #007bb2;
}

.auth-required {
  background-color: #ffe0b2;
  border: 1px solid #ffcc80;
  color: #e65100;
}

.authenticated-content {
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.auth-button {
  background-color: #007bff;
  color: white;
  padding: 12px 25px;
  border: none;
  border-radius: 5px;
  font-size: 1.1em;
  cursor: pointer;
  transition: background-color 0.3s ease;
  margin-top: 20px;
}

.auth-button:hover {
  background-color: #0056b3;
}

.small-text {
  font-size: 0.9em;
  color: #666;
  margin-top: 10px;
}

p {
  margin-bottom: 15px;
  line-height: 1.6;
}
</style>
