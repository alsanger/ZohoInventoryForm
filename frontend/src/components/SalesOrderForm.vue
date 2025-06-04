<template>
  <div class="zoho-form-container">
    <h2 class="form-title">Тестовая форма заказа на продажу</h2>

    <div v-if="isLoading" class="zoho-alert zoho-alert--warning">Загрузка данных...</div>
    <div v-if="errorMessage" class="zoho-alert zoho-alert--error">{{ errorMessage }}</div>
    <div v-if="successMessage" class="zoho-alert zoho-alert--success">{{ successMessage }}</div>

    <div v-if="testData">
      <h3>Полученные тестовые данные:</h3>
      <pre>{{ testData }}</pre>
    </div>
    <div v-else>
      <p>Ожидание получения тестовых данных...</p>
    </div>
  </div>
</template>

<script>
/*import axios from 'axios';*/
import apiClient from '@/plugins/axios';

export default {
  name: 'SalesOrderForm',
  data() {
    return {
      isLoading: false,
      errorMessage: '',
      successMessage: '',
      testData: null, // Для хранения полученных данных
    };
  },
  async mounted() {
    console.log('SalesOrderForm mounted: Attempting to fetch test data.');
    await this.fetchTestData();
  },
  methods: {
    async fetchTestData() {
      this.isLoading = true;
      this.errorMessage = '';
      this.successMessage = '';
      this.testData = null; // Очищаем данные перед запросом

      try {
        console.log('Fetching test data from /api/zoho/contacts...');
        //const response = await axios.get('/api/zoho/contacts'); // Запрашиваем список контактов
        const response = await apiClient.get('/zoho/contacts');
        console.log('Response received:', response);

        if (response.data.success) {
          this.testData = response.data.contacts; // Сохраняем контакты
          this.successMessage = 'Данные успешно получены.';
          console.log('Test data successfully fetched:', this.testData);
        } else {
          this.errorMessage = response.data.message || 'Не удалось получить тестовые данные.';
          console.error('Failed to fetch test data:', response.data.message);
        }
      } catch (error) {
        console.error('Error fetching test data:', error);
        if (error.response) {
          console.error('Error response status:', error.response.status);
          console.error('Error response data:', error.response.data);
          if (error.response.status === 401) {
            this.errorMessage = error.response.data.message || 'Сессия Zoho истекла или не авторизована. Пожалуйста, обновите авторизацию.';
            // Если мы находимся на маршруте AuthRequired, это должно быть обработано роутером
            // Если это ошибка 401 на любом другом маршруте, роутер должен перенаправить
            // Но для целей отладки, пока просто выводим сообщение
          } else {
            this.errorMessage = `Произошла ошибка при загрузке данных: ${error.response.status} ${error.response.statusText}.`;
          }
        } else if (error.request) {
          this.errorMessage = 'Нет ответа от сервера. Проверьте подключение или CORS.';
        } else {
          this.errorMessage = 'Неизвестная ошибка при настройке запроса.';
        }
      } finally {
        this.isLoading = false;
      }
    },
  }
};
</script>

<style scoped>
/* Эти стили можно оставить как есть для базового вида */
.zoho-form-container {
  max-width: 900px;
  margin: 40px auto;
  padding: 30px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  font-family: 'Inter', sans-serif;
  color: #424242;
}

.form-title {
  font-size: 24px;
  color: #263238;
  margin-bottom: 25px;
  text-align: center;
  font-weight: 500;
  border-bottom: 1px solid #eee;
  padding-bottom: 15px;
}

.zoho-alert {
  padding: 15px;
  margin-bottom: 20px;
  border-radius: 4px;
  font-weight: 500;
  color: #333;
}
.zoho-alert--warning {
  background-color: #fffde7;
  border: 1px solid #ffecb3;
  color: #ff9800;
}
.zoho-alert--error {
  background-color: #ffebee;
  border: 1px solid #ef9a9a;
  color: #f44336;
}
.zoho-alert--success {
  background-color: #e8f5e9;
  border: 1px solid #a5d6a7;
  color: #4CAF50;
}

pre {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  padding: 15px;
  border-radius: 4px;
  white-space: pre-wrap;
  word-break: break-all;
  max-height: 400px;
  overflow-y: auto;
}
</style>
