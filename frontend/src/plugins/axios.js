import axios from 'axios';

// Определяем базовый URL для API бэкенда.
// Используем переменную окружения VITE_API_BASE_URL.
// Это позволит легко менять URL между разработкой и продакшеном.
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api', // Дефолт для разработки Laravel
  withCredentials: true, // Важно для передачи куки сессии Laravel (если используются) и CSRF-токена
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Перехватчик для автоматического добавления CSRF-токена
// при каждом POST, PUT, DELETE запросе.
apiClient.interceptors.request.use(async (config) => {
  // Мы получаем CSRF-токен от Laravel через отдельный эндпоинт.
  // Это делается только один раз при загрузке страницы или при необходимости.
  // Для простоты, здесь мы предполагаем, что токен уже где-то хранится (например, в Pinia store)
  // или что мы его получим динамически.
  // Более надежный способ: при загрузке приложения запросить токен и сохранить его в store/localStorage.

  // Если метод запроса не GET или HEAD, добавляем CSRF-токен.
  if (config.method !== 'get' && config.method !== 'head') {
    // В реальном приложении: const csrfToken = store.state.csrfToken;
    // Или запрос к /api/csrf-token, если токена нет.
    // Пока оставим так, но помним, что токен нужно будет получать.
    // Laravel по умолчанию ожидает X-CSRF-TOKEN заголовок.
    // Vue будет получать его из куки `XSRF-TOKEN` или через отдельный запрос к `/api/csrf-token`.
    // Мы настроим получение токена на уровне корневого компонента Vue.
  }

  return config;
}, (error) => {
  return Promise.reject(error);
});

export default apiClient;
