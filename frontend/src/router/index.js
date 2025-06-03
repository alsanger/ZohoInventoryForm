import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios'; // <-- Добавляем импорт axios

// Импортируем компоненты, которые будем использовать в маршрутах.
import AuthRequired from '../components/AuthRequired.vue'; // Страница для неавторизованных
import SalesOrderForm from '../components/SalesOrderForm.vue'; // Форма заказа на продажу
import AuthStatusChecker from '../components/AuthStatusChecker.vue'; // Компонент для проверки статуса после OAuth

const routes = [
  // Маршрут для обработки callback от Zoho.
  // На этот маршрут будет перенаправлять Laravel после авторизации.
  // AuthStatusChecker будет определять, куда идти дальше (на форму заказа или показывать ошибку).
  {
    path: '/',
    name: 'AuthStatusChecker',
    component: AuthStatusChecker,
  },
  // Маршрут для отображения страницы с кнопкой авторизации
  {
    path: '/auth-required',
    name: 'AuthRequired',
    component: AuthRequired,
  },
  // Маршрут для формы создания заказа на продажу
  {
    path: '/sales-order',
    name: 'SalesOrderForm',
    component: SalesOrderForm,
    // Добавляем meta-поле для защиты маршрута, чтобы только авторизованные могли попасть
    meta: { requiresAuth: true }
  },
  // Другие маршруты по необходимости
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL), // <-- Добавил import.meta.env.BASE_URL
  routes,
});

// Добавляем навигационный хук (navigation guard) для проверки авторизации
router.beforeEach(async (to, from, next) => {
  // Если маршрут требует аутентификации
  if (to.meta.requiresAuth) {
    try {
      // Отправляем запрос на бэкенд для проверки статуса авторизации Zoho
      const response = await axios.get('/api/zoho/auth-status');

      if (response.data.authenticated) {
        // Если пользователь авторизован, разрешаем переход
        next();
      } else {
        // Если не авторизован, перенаправляем на страницу авторизации
        next({ name: 'AuthRequired' });
      }
    } catch (error) {
      console.error('Ошибка при проверке статуса авторизации Zoho в роутере:', error);
      // Если произошла ошибка при проверке (например, сервер недоступен или 500 ошибка),
      // также перенаправляем на страницу авторизации с сообщением об ошибке
      next({ name: 'AuthRequired', query: { error: true, message: 'Не удалось проверить статус авторизации при навигации.' } });
    }
  } else {
    // Для маршрутов, которые не требуют аутентификации, просто разрешаем переход
    next();
  }
});

export default router;
