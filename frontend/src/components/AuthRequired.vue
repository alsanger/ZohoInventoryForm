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
    // Получить параметры из URL.
    const params = new URLSearchParams(window.location.search);
    const status = params.get('status');
    const message = params.get('message');
    const errorParam = params.get('error');

    if (message) {
      this.displayMessage = decodeURIComponent(message);
      // Определить статус сообщения.
      if (errorParam === 'true' || status === 'error') {
        this.displayStatus = 'error';
      } else {
        this.displayStatus = 'warning';
      }
      history.replaceState({}, document.title, window.location.pathname); // Очистить URL.
    }
  },
  methods: {
    // Перенаправить пользователя на URL авторизации Zoho.
    async redirectToZohoAuth() {
      try {
        const response = await apiClient.get('/zoho/auth');
        if (response.data && response.data.auth_url) {
          window.location.href = response.data.auth_url; // Перенаправление.
        } else {
          this.displayMessage = 'Failed to get Zoho authorization URL. Please try again later.';
          this.displayStatus = 'error';
        }
      } catch (error) {
        console.error('Ошибка при получении URL авторизации Zoho:', error);
        this.displayMessage = 'Connection error. Failed to start Zoho authorization.';
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
