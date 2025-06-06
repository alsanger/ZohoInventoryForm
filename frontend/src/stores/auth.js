import { defineStore } from 'pinia';
import apiClient from '@/plugins/axios';
import router from '@/router'; // Импортируем роутер для очистки URL

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isAuthenticated: false, // Флаг авторизации Zoho
    authMessage: null,      // Сообщение о статусе
    loadingAuth: false,     // Флаг загрузки
    csrfToken: null,        // CSRF-токен
  }),
  actions: {
    async checkZohoAuthStatus() {
      this.loadingAuth = true;
      try {
        const response = await apiClient.get('/zoho/auth-status');
        this.isAuthenticated = response.data.authenticated;
        this.authMessage = response.data.message || (this.isAuthenticated ? 'Успешная авторизация Zoho.' : 'Требуется авторизация Zoho.');
        console.log('Zoho Auth Status:', this.isAuthenticated, this.authMessage);
        return this.isAuthenticated;
      } catch (error) {
        console.error('Ошибка проверки статуса авторизации Zoho:', error);
        this.isAuthenticated = false;
        this.authMessage = 'Не удалось проверить статус авторизации Zoho. Пожалуйста, попробуйте снова.';
        return false;
      } finally {
        this.loadingAuth = false;
      }
    },

    redirectToZohoAuth() {
      // Перенаправляем на эндпоинт Laravel для OAuth
      const backendBaseUrl = import.meta.env.VITE_API_BASE_URL.replace('/api', '');
      const authUrl = `${backendBaseUrl}/zoho/auth`;
      console.log('Перенаправление на URL авторизации Zoho:', authUrl);
      window.location.href = authUrl;
    },

    handleAuthCallback(params) {
      const status = params.get('auth_status');
      const message = params.get('message');

      if (status === 'success') {
        this.isAuthenticated = true;
        this.authMessage = message || 'Авторизация Zoho успешно завершена!';
        console.log('Callback авторизации Zoho: Успех!', this.authMessage);
        // Очищаем параметры URL
        router.replace({ query: {} });
      } else if (status === 'error') {
        this.isAuthenticated = false;
        this.authMessage = message || 'Ошибка авторизации Zoho.';
        console.error('Callback авторизации Zoho: Ошибка!', this.authMessage);
        router.replace({ query: {} });
      }
    },

    async fetchCsrfToken() {
      try {
        const response = await apiClient.get('/csrf-token');
        this.csrfToken = response.data.csrf_token;
        console.log('CSRF токен получен:', this.csrfToken);
        // Устанавливаем токен для Axios
        apiClient.defaults.headers.common['X-CSRF-TOKEN'] = this.csrfToken;
      } catch (error) {
        console.error('Ошибка получения CSRF токена:', error);
        this.csrfToken = null;
      }
    },
  },
  getters: {
    isZohoAuthenticated: (state) => state.isAuthenticated,
    getAuthMessage: (state) => state.authMessage,
    isLoadingAuth: (state) => state.loadingAuth,
  },
});
