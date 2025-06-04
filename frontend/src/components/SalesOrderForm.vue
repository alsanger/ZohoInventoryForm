<template>
  <div class="sales-order-form">
    <h2>Создание заказа на продажу Zoho</h2>

    <div v-if="loading" class="loading-indicator">Загрузка данных...</div>
    <div v-if="error" class="error-message">{{ error }}</div>

    <form @submit.prevent="handleSubmit" v-if="!loading && !error">
      <div class="form-group">
        <label for="contact">Контакт:</label>
        <select id="contact" v-model="form.contact_id" required>
          <option value="" disabled>Выберите контакт</option>
          <option v-for="contact in contacts" :key="contact.contact_id" :value="contact.contact_id">
            {{ contact.contact_name }}
          </option>
        </select>
      </div>

      <h3>Позиции заказа</h3>
      <div class="line-items-container">
        <div v-for="(item, index) in form.line_items" :key="index" class="line-item">
          <div class="form-group">
            <label :for="'item-' + index">Товар:</label>
            <select :id="'item-' + index" v-model="item.item_id" @change="updateItemPrice(index)" required>
              <option value="" disabled>Выберите товар</option>
              <option v-for="product in products" :key="product.item_id" :value="product.item_id">
                {{ product.name }} ({{ product.sku }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label :for="'quantity-' + index">Количество:</label>
            <input type="number" :id="'quantity-' + index" v-model.number="item.quantity" min="1" required>
          </div>

          <div class="form-group">
            <label :for="'rate-' + index">Цена за единицу:</label>
            <input type="number" :id="'rate-' + index" v-model.number="item.rate" step="0.01" min="0" required>
          </div>

          <button type="button" @click="removeLineItem(index)" class="remove-item-button">Удалить</button>
        </div>
      </div>

      <button type="button" @click="addLineItem" class="add-item-button">Добавить позицию</button>

      <div class="form-actions">
        <button type="submit" :disabled="submitting">
          {{ submitting ? 'Отправка...' : 'Создать заказ' }}
        </button>
      </div>
    </form>

    <div v-if="successMessage" class="success-message">{{ successMessage }}</div>
    <div v-if="submitError" class="error-message">{{ submitError }}</div>
  </div>
</template>

<script>
// Импортируем наш настроенный клиент Axios
import apiClient from '@/plugins/axios'; // Убедитесь, что путь правильный

export default {
  name: 'SalesOrderForm',
  data() {
    return {
      loading: true,
      submitting: false,
      error: null,
      submitError: null,
      successMessage: null,
      contacts: [],
      products: [],
      form: {
        contact_id: '',
        line_items: [
          { item_id: '', quantity: 1, rate: 0 } // Начальная позиция
        ]
      }
    };
  },
  async created() {
    await this.fetchInitialData();
  },
  methods: {
    async fetchInitialData() {
      this.loading = true;
      this.error = null;
      try {
        // Загрузка контактов
        const contactsResponse = await apiClient.get('/zoho/contacts');
        this.contacts = contactsResponse.data.contacts;
        console.log('Contacts loaded:', this.contacts);

        // Загрузка товаров
        const itemsResponse = await apiClient.get('/zoho/items');
        this.products = itemsResponse.data.items; // Предполагаем, что API возвращает 'items'
        console.log('Items loaded:', this.products);

      } catch (err) {
        this.error = 'Не удалось загрузить данные: ' + (err.response?.data?.message || err.message);
        console.error('Ошибка загрузки данных:', err);
      } finally {
        this.loading = false;
      }
    },
    addLineItem() {
      this.form.line_items.push({ item_id: '', quantity: 1, rate: 0 });
    },
    removeLineItem(index) {
      this.form.line_items.splice(index, 1);
    },
    updateItemPrice(index) {
      const selectedProduct = this.products.find(p => p.item_id === this.form.line_items[index].item_id);
      if (selectedProduct) {
        // Устанавливаем цену по умолчанию из товара, если она есть
        // Если нет, оставляем 0 или то, что было
        this.form.line_items[index].rate = selectedProduct.rate || 0;
      }
    },
    async handleSubmit() {
      this.submitting = true;
      this.submitError = null;
      this.successMessage = null;

      try {
        // Фильтрация пустых позиций (если пользователь добавил и не заполнил)
        const validLineItems = this.form.line_items.filter(item => item.item_id && item.quantity > 0 && item.rate >= 0);

        if (validLineItems.length === 0) {
          this.submitError = 'Добавьте хотя бы одну позицию заказа с выбранным товаром, количеством и ценой.';
          return;
        }

        const payload = {
          contact_id: this.form.contact_id,
          line_items: validLineItems,
          // Добавьте другие поля, если они требуются Zoho API для создания заказа
          // Например, date: new Date().toISOString().split('T')[0],
          // salesorder_number: 'SO-' + Date.now(), // Пример
        };

        const response = await apiClient.post('/zoho/sales-orders', payload);

        this.successMessage = response.data.message || 'Заказ на продажу успешно создан!';
        console.log('Order created:', response.data);

        // Опционально: очистить форму после успешной отправки
        this.form.contact_id = '';
        this.form.line_items = [{ item_id: '', quantity: 1, rate: 0 }];

      } catch (err) {
        this.submitError = 'Ошибка при создании заказа: ' + (err.response?.data?.message || err.message);
        console.error('Ошибка создания заказа:', err.response?.data || err);
      } finally {
        this.submitting = false;
      }
    }
  }
};
</script>

<style lang="scss">
@use "sass:color";

.sales-order-form {
  max-width: 800px;
  margin: 40px auto;
  padding: 30px;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  font-family: Arial, sans-serif;

  h2, h3 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
  }

  .loading-indicator, .error-message, .success-message {
    text-align: center;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
  }

  .loading-indicator {
    background-color: #e0f7fa;
    color: #00796b;
  }

  .error-message {
    background-color: #ffebee;
    color: #d32f2f;
    border: 1px solid #ef9a9a;
  }

  .success-message {
    background-color: #e8f5e9;
    color: #388e3c;
    border: 1px solid #a5d6a7;
  }

  .form-group {
    margin-bottom: 15px;

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #555;
    }

    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box; /* Включает padding в общую ширину */
    }

    select {
      appearance: none; /* Убираем стандартный стиль выпадающего списка */
      background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20256%20256%22%3E%3Cpolygon%20points%3D%220%2C64%20128%2C192%20256%2C64%22%2F%3E%3C%2Fsvg%3E'); /* Кастомная стрелка */
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 12px;
    }
  }

  .line-items-container {
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 20px;
  }

  .line-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto; /* Товар, кол-во, цена, кнопка */
    gap: 15px;
    align-items: flex-end;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px dashed #eee;

    &:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .form-group {
      margin-bottom: 0; // Переопределяем для grid
    }
  }

  .add-item-button,
  .remove-item-button,
  .form-actions button {
    padding: 10px 18px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
  }

  .add-item-button {
    background-color: #4CAF50; /* Green */
    color: white;
    &:hover {
      background-color: color.adjust(#4CAF50, $lightness: -10%);
    }
  }

  .remove-item-button {
    background-color: #f44336; /* Red */
    color: white;
    align-self: center; // Выравнивание по центру в сетке
    &:hover {
      background-color: color.adjust(#f44336, $lightness: -10%); // Использование color.adjust
    }
  }

  .form-actions {
    text-align: right;
    margin-top: 20px;

    button[type="submit"] {
      background-color: #007bff; /* Blue */
      color: white;
      padding: 12px 25px;
      font-size: 18px;

      &:hover {
        background-color: color.adjust(#007bff, $lightness: -10%);
      }

      &:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
      }
    }
  }
}
</style>
