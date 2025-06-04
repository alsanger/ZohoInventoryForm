<template>
  <div style="display: none;"></div>
</template>

<script>
import apiClient from "@/plugins/axios.js";

export default {
  name: 'AuthStatusChecker',
  data() {
    return {
      message: 'Проверка статуса авторизации...',
      detailMessage: null,
      isLoading: true, // Флаг для отображения спиннера
    };
  },
  async mounted() {
    // 1. Проверяем параметры из URL после редиректа от ZohoAuthController
    const params = new URLSearchParams(window.location.search);
    const authStatus = params.get('auth_status');
    const authMessage = params.get('message');

    if (authStatus && authMessage) {
      this.detailMessage = decodeURIComponent(authMessage);
      history.replaceState({}, document.title, window.location.pathname); // Очищаем URL

      if (authStatus === 'success') {
        this.message = 'Авторизация Zoho Inventory прошла успешно!';
        // Если успех, перенаправляем на форму заказа через короткую задержку
        setTimeout(() => {
          this.$router.push({ name: 'SalesOrderForm' });
        }, 1500); // Небольшая задержка, чтобы пользователь увидел сообщение
      } else {
        this.message = 'Ошибка авторизации Zoho Inventory.';
        // Если ошибка, перенаправляем на страницу, требующую авторизации
        setTimeout(() => {
          this.$router.push({ name: 'AuthRequired', query: { message: authMessage, status: authStatus } });
        }, 1500);
      }
      this.isLoading = false;
      return; // Завершаем выполнение, так как параметры URL были обработаны
    }

    // 2. Если параметров в URL нет, проверяем статус авторизации через API
    try {
      // Используем axios, который мы настроили в main.js
      //const response = await this.$axios.get('/api/zoho/auth-status');
      const response = await apiClient.get('/zoho/auth');

      if (response.data.authenticated) {
        this.message = 'Вы уже авторизованы в Zoho Inventory.';
        this.detailMessage = 'Перенаправление на форму заказа...';
        setTimeout(() => {
          this.$router.push({ name: 'SalesOrderForm' });
        }, 1000);
      } else {
        this.message = 'Требуется авторизация Zoho Inventory.';
        this.detailMessage = 'Перенаправление на страницу авторизации...';
        setTimeout(() => {
          this.$router.push({ name: 'AuthRequired' });
        }, 1000);
      }
    } catch (error) {
      console.error('Ошибка при проверке статуса авторизации Zoho:', error);
      this.message = 'Не удалось проверить статус авторизации Zoho.';
      this.detailMessage = 'Пожалуйста, попробуйте авторизоваться снова.';
      this.$router.push({ name: 'AuthRequired', query: { error: true, message: 'Не удалось проверить статус авторизации.' } });
    } finally {
      this.isLoading = false;
    }
  },
};
</script>

<style scoped>
.auth-status-checker {
  text-align: center;
  margin-top: 50px;
}
.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: #2196F3; /* Zoho blue */
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 20px auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
