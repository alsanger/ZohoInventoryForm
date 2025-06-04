<template>
  <div class="auth-required zoho-card">
    <h1 class="zoho-card__title">Требуется авторизация Zoho Inventory</h1>

    <div v-if="displayMessage" :class="['zoho-alert', displayStatus === 'error' ? 'zoho-alert--error' : 'zoho-alert--warning']">
      {{ displayMessage }}
    </div>

    <p class="zoho-text-muted">Для доступа к функциям Zoho Inventory, пожалуйста, авторизуйтесь через свою учетную запись Zoho.</p>

    <button @click="redirectToZohoAuth" class="zoho-button zoho-button--primary">
      Авторизоваться с Zoho
    </button>
  </div>
</template>

<script>
import apiClient from "@/plugins/axios.js";

export default {
  name: 'AuthRequired',
  data() {
    return {
      displayMessage: null,
      displayStatus: null,
    };
  },
  mounted() {
    // Читаем параметры из URL при загрузке компонента
    const params = new URLSearchParams(window.location.search);
    const status = params.get('status'); // Параметр 'status' от AuthStatusChecker
    const message = params.get('message'); // Параметр 'message' от AuthStatusChecker
    const errorParam = params.get('error'); // Параметр 'error' для общих ошибок

    if (message) {
      this.displayMessage = decodeURIComponent(message);
      // Если есть параметр 'error=true' или status='error', устанавливаем статус ошибки
      if (errorParam === 'true' || status === 'error') {
        this.displayStatus = 'error';
      } else {
        // Можно использовать 'warning' или 'info' для других статусов, если нужно
        this.displayStatus = 'warning';
      }
      history.replaceState({}, document.title, window.location.pathname); // Очищаем URL
    }
  },
  methods: {
    // Метод для перенаправления пользователя на URL авторизации Zoho
    async redirectToZohoAuth() {
      try {
        // Вызываем эндпоинт бэкенда, который вернет URL для Zoho Auth
        //const response = await this.$axios.get('/api/zoho/auth');
        const response = await apiClient.get('/zoho/auth');
        if (response.data && response.data.auth_url) {
          window.location.href = response.data.auth_url; // Перенаправляем пользователя на Zoho
        } else {
          this.displayMessage = 'Не удалось получить URL для авторизации Zoho. Пожалуйста, попробуйте позже.';
          this.displayStatus = 'error';
        }
      } catch (error) {
        console.error('Ошибка при получении URL авторизации Zoho:', error);
        this.displayMessage = 'Ошибка соединения с сервером. Не удалось начать авторизацию Zoho.';
        this.displayStatus = 'error';
      }
    },
  },
};
</script>

<style scoped>
.auth-required {
  text-align: center;
  margin-top: 50px;
}

.zoho-button {
  margin-top: 20px;
}
</style>
