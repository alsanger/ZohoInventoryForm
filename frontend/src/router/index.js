import { createRouter, createWebHistory } from 'vue-router';
import apiClient from '@/plugins/axios';

import AuthRequired from '../components/AuthRequired.vue';
import SalesOrderForm from '../components/SalesOrderForm.vue';
import AuthStatusChecker from '../components/AuthStatusChecker.vue';

const routes = [
  // Маршрут для обработки callback от Zoho
  {
    path: '/',
    name: 'AuthStatusChecker',
    component: AuthStatusChecker,
  },
  // Маршрут для страницы с кнопкой авторизации
  {
    path: '/auth-required',
    name: 'AuthRequired',
    component: AuthRequired,
  },
  // Маршрут для формы создания заказа на продажу, защищенный авторизацией
  {
    path: '/sales-order',
    name: 'SalesOrderForm',
    component: SalesOrderForm,
    meta: { requiresAuth: true }
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

// Проверка авторизации перед каждым переходом
router.beforeEach(async (to, from, next) => {
  // Если маршрут требует авторизации
  if (to.meta.requiresAuth) {
    try {
      // Проверяем статус авторизации Zoho через API
      const response = await apiClient.get('/zoho/auth-status');

      if (response.data.authenticated) {
        // Если авторизован, разрешаем переход
        next();
      } else {
        // Если не авторизован, перенаправляем на страницу авторизации
        next({ name: 'AuthRequired' });
      }
    } catch (error) {
      // Логируем ошибку при проверке статуса
      console.error('Ошибка при проверке статуса авторизации Zoho:', error);
      // Перенаправляем на страницу авторизации с сообщением об ошибке
      next({ name: 'AuthRequired', query: { error: true, message: 'Не удалось проверить статус авторизации при навигации.' } });
    }
  } else {
    // Для маршрутов без авторизации разрешаем переход
    next();
  }
});

export default router;
