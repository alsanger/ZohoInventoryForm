import { defineStore } from 'pinia'
import apiClient from '@/plugins/axios' // Используем наш настроенный экземпляр Axios

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isAuthenticated: false, // Флаг, показывающий, авторизовано ли приложение в Zoho
    authMessage: null,      // Сообщение о статусе авторизации (успех/ошибка)
    loadingAuth: false,     // Флаг загрузки (для асинхронных операций авторизации)
    csrfToken: null,        // CSRF-токен для Laravel
  }),
  actions: {
    /**
     * Проверяет статус авторизации Zoho через API бэкенда.
     * Обновляет isAuthenticated и authMessage.
     * @returns {Promise<boolean>} Возвращает true, если авторизовано, false в противном случае.
     */
    async checkZohoAuthStatus() {
      this.loadingAuth = true;
      try {
        const response = await apiClient.get('/zoho/auth-status');
        this.isAuthenticated = response.data.authenticated;
        this.authMessage = response.data.message || (this.isAuthenticated ? 'Успешная авторизация Zoho.' : 'Требуется авторизация Zoho.');
        console.log('Zoho Auth Status:', this.isAuthenticated, this.authMessage);
        return this.isAuthenticated;
      } catch (error) {
        console.error('Error checking Zoho auth status:', error);
        this.isAuthenticated = false;
        this.authMessage = 'Не удалось проверить статус авторизации Zoho. Пожалуйста, попробуйте снова.';
        return false;
      } finally {
        this.loadingAuth = false;
      }
    },

    /**
     * Запускает процесс авторизации Zoho, перенаправляя пользователя на бэкенд.
     */
    redirectToZohoAuth() {
      // Перенаправляем пользователя на эндпоинт Laravel, который инициирует OAuth
      // Базовый URL бэкенда находится в переменной окружения VITE_API_BASE_URL.
      // Наш web-маршрут /zoho/auth не является частью /api, поэтому убираем /api из baseURL.
      const backendBaseUrl = import.meta.env.VITE_API_BASE_URL.replace('/api', '');
      const authUrl = `${backendBaseUrl}/zoho/auth`;
      console.log('Redirecting to Zoho Auth URL:', authUrl);
      window.location.href = authUrl; // Прямое перенаправление
    },

    /**
     * Обрабатывает параметры URL после редиректа от Laravel/Zoho.
     * @param {URLSearchParams} params Параметры из URL (auth_status, message).
     */
    handleAuthCallback(params) {
      const status = params.get('auth_status');
      const message = params.get('message');

      if (status === 'success') {
        this.isAuthenticated = true;
        this.authMessage = message || 'Авторизация Zoho успешно завершена!';
        console.log('Zoho Auth Callback Success:', this.authMessage);
        // Очищаем параметры URL, чтобы они не оставались в строке браузера
        router.replace({ query: {} }); // Используем router для очистки URL
      } else if (status === 'error') {
        this.isAuthenticated = false;
        this.authMessage = message || 'Ошибка авторизации Zoho.';
        console.error('Zoho Auth Callback Error:', this.authMessage);
        router.replace({ query: {} }); // Очищаем параметры URL
      }
    },

    /**
     * Получает CSRF-токен от Laravel бэкенда и сохраняет его.
     * @returns {Promise<void>}
     */
    async fetchCsrfToken() {
      try {
        const response = await apiClient.get('/csrf-token');
        this.csrfToken = response.data.csrf_token;
        console.log('CSRF Token fetched:', this.csrfToken);
        // Устанавливаем CSRF-токен для всех последующих запросов Axios
        apiClient.defaults.headers.common['X-CSRF-TOKEN'] = this.csrfToken;
      } catch (error) {
        console.error('Error fetching CSRF token:', error);
        this.csrfToken = null;
      }
    },
  },
  getters: {
    // Геттеры для удобного доступа к состоянию
    isZohoAuthenticated: (state) => state.isAuthenticated,
    getAuthMessage: (state) => state.authMessage,
    isLoadingAuth: (state) => state.loadingAuth,
  },
});
