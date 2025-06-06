// frontend/src/main.js
import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import axios from 'axios'

import './assets/scss/app.scss';

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Настройки axios должны быть до любых запросов
axios.defaults.withCredentials = true;
axios.defaults.baseURL = import.meta.env.VITE_BACKEND_BASE_URL;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

app.config.globalProperties.$axios = axios;

// Получаем CSRF cookie перед монтированием приложения
axios.get('/sanctum/csrf-cookie')
  .then(() => {
    console.log('Sanctum CSRF cookie получен успешно.');
    app.mount('#app');
  })
  .catch(error => {
    console.error('Не удалось получить Sanctum CSRF cookie:', error);
    app.mount('#app');
  });
