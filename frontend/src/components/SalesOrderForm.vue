<template>
  <div class="sales-order-form container mt-4">
    <h2 class="text-center mb-4">Creating a Sales Order</h2>

    <div v-if="loading" class="alert alert-info text-center">Loading data...</div>
    <div v-if="error" class="alert alert-danger text-center">{{ error }}</div>

    <form @submit.prevent="handleSubmit" v-if="!loading && !error">
      <div class="mb-3 row align-items-end">
        <label for="contact" class="col-sm-2 col-form-label fw-bold">Client:</label>
        <div class="col-sm-8">
          <select id="contact" v-model="form.customer_id" @change="updateSelectedContactInfo" class="form-select" required>
            <option value="" disabled>Select a client</option>
            <option v-for="contact in contacts" :key="contact.contact_id" :value="contact.contact_id">
              {{ contact.contact_name }}
            </option>
          </select>
        </div>
        <div class="col-sm-2 text-end">
          <button type="button" class="btn btn-primary btn-sm px-2 py-1" @click="showNewContactModal">
            + New
          </button>
        </div>
      </div>

      <div v-if="selectedContact" class="mb-3 p-3 border rounded bg-light">
        <h5 class="mb-2">Client Information:</h5>
        <p class="mb-1"><strong>Name:</strong> {{ selectedContact.contact_name }}</p>
        <p class="mb-0"><strong>Shipping Address:</strong> {{ formatShippingAddress(selectedContact) }}</p>
      </div>


      <h3 class="text-center mt-4 mb-3">Order positions</h3>
      <div class="table-responsive">
        <table class="table table-bordered table-striped sales-order-table">
          <thead>
          <tr>
            <th class="item-desc-col">ITEMS & DESCRIPTION</th>
            <th class="ordered-col">ORDERED</th>
            <th class="discount-col">DISCOUNT (%)</th>
            <th class="rate-col">RATE</th>
            <th class="amount-col">AMOUNT</th>
            <th class="action-col text-center"></th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, index) in form.line_items" :key="index">
            <td class="item-desc-col">
              <select v-model="item.item_id" @change="updateItemDetails(index)" class="form-select form-select-sm" required>
                <option value="" disabled>Select Item</option>
                <option v-for="product in products" :key="product.item_id" :value="product.item_id">
                  {{ product.name }} ({{ product.sku }})
                </option>
              </select>
            </td>
            <td class="ordered-col">
              <input type="number" v-model.number="item.quantity" min="1" class="form-control form-control-sm text-center" required>
            </td>
            <td class="discount-col">
              <input type="number" v-model.number="item.discount_percentage" min="0" max="100" step="0.01" class="form-control form-control-sm text-center">
            </td>
            <td class="rate-col">
              <input type="number" v-model.number="item.rate" step="0.01" min="0" class="form-control form-control-sm text-end" required>
            </td>
            <td class="amount-col text-end">
              {{ formatCurrency(calculateLineItemAmount(index)) }}
            </td>
            <td class="action-col text-center align-middle">
              <button type="button" @click="removeLineItem(index)" class="btn btn-sm btn-danger remove-item-icon-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                  <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                  <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H.5a.5.5 0 0 1 0-1H4a.5.5 0 0 1 .5.5V2h7V1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V2a1 1 0 0 1-1 1h-.5zM1.5 2H14v.5H1.5V2z"/>
                </svg>
              </button>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="text-start mb-3">
        <button type="button" @click="addLineItem" class="btn btn-success btn-sm add-item-button">Add Item</button>
      </div>

      <div class="summary-section mt-4">
        <div class="row justify-content-end">
          <div class="col-sm-4">
            <div class="d-flex justify-content-between mb-1">
              <span>Sub Total</span>
              <span class="fw-bold">{{ formatCurrency(totalSubTotal) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span>Total Discount</span>
              <span class="fw-bold">{{ formatCurrency(totalDiscountAmount) }}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-5">
              <span>Total</span>
              <span>{{ formatCurrency(finalTotal) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary py-2" :disabled="submitting">
          {{ submitting ? 'Sending...' : 'Create an order' }}
        </button>
      </div>
    </form>

    <div v-if="successMessage" class="alert alert-success mt-4 text-center">{{ successMessage }}</div>
    <div v-if="submitError" class="alert alert-danger mt-4 text-center">{{ submitError }}</div>

    <div class="modal fade" id="newContactModal" tabindex="-1" aria-labelledby="newContactModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="newContactModalLabel">Создать новый контакт</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="createNewContact">
            <div class="modal-body">
              <div class="mb-3">
                <label for="newContactName" class="form-label">Имя контакта:</label>
                <input type="text" class="form-control" id="newContactName" v-model="newContact.contact_name" required>
              </div>
              <div class="mb-3">
                <label for="newContactType" class="form-label">Тип контакта:</label>
                <select class="form-select" id="newContactType" v-model="newContact.contact_type" required>
                  <option value="customer">Клиент</option>
                  <option value="vendor">Поставщик</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
              <button type="submit" class="btn btn-primary" :disabled="creatingNewContact">
                {{ creatingNewContact ? 'Создание...' : 'Создать' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import apiClient from '@/plugins/axios';

export default {
  name: 'SalesOrderForm',
  data() {
    return {
      loading: true,
      submitting: false,
      creatingNewContact: false,
      error: null,
      submitError: null,
      successMessage: null,
      contacts: [],
      products: [],
      selectedContact: null, // <-- НОВОЕ: Для хранения выбранного контакта
      newContact: {
        contact_name: '',
        contact_type: 'customer',
      },
      form: {
        customer_id: '',
        line_items: [
          { item_id: '', quantity: 1, rate: 0, discount_percentage: 0 }
        ]
      },
      newContactModalInstance: null,
    };
  },
  computed: {
    totalSubTotal() {
      return this.form.line_items.reduce((sum, item) => {
        const amount = item.quantity * item.rate;
        return sum + amount;
      }, 0);
    },
    totalDiscountAmount() {
      return this.form.line_items.reduce((sum, item) => {
        const amountBeforeDiscount = item.quantity * item.rate;
        const discountPercent = typeof item.discount_percentage === 'number' ? item.discount_percentage : 0;
        const discountValue = amountBeforeDiscount * (discountPercent / 100);
        return sum + discountValue;
      }, 0);
    },
    finalTotal() {
      return this.totalSubTotal - this.totalDiscountAmount;
    }
  },
  async mounted() {
    await this.fetchInitialData();
    if (window.bootstrap && window.bootstrap.Modal) {
      this.newContactModalInstance = new window.bootstrap.Modal(document.getElementById('newContactModal'));
    } else {
      console.warn('Bootstrap Modal object not found. Ensure Bootstrap JS is loaded via CDN in index.html.');
    }
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat('uk-UA', { style: 'currency', currency: 'UAH' }).format(value);
    },
    calculateLineItemAmount(index) {
      const item = this.form.line_items[index];
      const amount = item.quantity * item.rate;
      const discountPercent = typeof item.discount_percentage === 'number' ? item.discount_percentage : 0;
      const discountValue = amount * (discountPercent / 100);
      return amount - discountValue;
    },
    async fetchInitialData() {
      this.loading = true;
      this.error = null;
      try {
        const contactsResponse = await apiClient.get('/zoho/contacts');
        this.contacts = contactsResponse.data.contacts;
        // НОВОЕ: Если есть выбранный контакт после загрузки, обновить его информацию
        if (this.form.contact_id) {
          this.updateSelectedContactInfo();
        }

        const itemsResponse = await apiClient.get('/zoho/items');
        this.products = itemsResponse.data.items;

      } catch (err) {
        this.error = 'Failed to load data: ' + (err.response?.data?.message || err.message);
        console.error('Error loading data:', err);
      } finally {
        this.loading = false;
      }
    },
    addLineItem() {
      this.form.line_items.push({ item_id: '', quantity: 1, rate: 0, discount_percentage: 0 });
    },
    removeLineItem(index) {
      this.form.line_items.splice(index, 1);
    },
    updateItemDetails(index) {
      const selectedProduct = this.products.find(p => p.item_id === this.form.line_items[index].item_id);
      if (selectedProduct) {
        this.form.line_items[index].rate = selectedProduct.rate || 0;
      }
    },
    showNewContactModal() {
      this.newContactModalInstance.show();
    },
    async createNewContact() {
      this.creatingNewContact = true;
      try {
        const response = await apiClient.post('/zoho/contacts', this.newContact);
        this.newContactModalInstance.hide();
        this.successMessage = 'Contact "' + response.data.contact.contact_name + '" successfully created!';
        await this.fetchInitialData();
        this.form.customer_id = response.data.contact.contact_id;
        this.updateSelectedContactInfo(); // Обновить информацию о выбранном контакте после создания нового
        this.newContact = { contact_name: '', contact_type: 'customer' };
      } catch (err) {
        this.submitError = 'Error creating new contact: ' + (err.response?.data?.message || err.message);
        console.error('Error creating contact:', err.response?.data || err);
      } finally {
        this.creatingNewContact = false;
      }
    },
    async handleSubmit() {
      this.submitting = true;
      this.submitError = null;
      this.successMessage = null;

      try {
        const validLineItems = this.form.line_items.filter(item => item.item_id && item.quantity > 0 && item.rate >= 0);

        if (validLineItems.length === 0) {
          this.submitError = 'Add at least one order item with selected product, quantity and price.';
          return;
        }

        const zohoLineItems = validLineItems.map(item => {
          const zohoItem = {
            item_id: item.item_id,
            quantity: item.quantity,
            rate: item.rate,
          };

          // Пересчитываем процент скидки в твердую сумму скидки
          if (typeof item.discount_percentage === 'number' && item.discount_percentage > 0) {
            const lineItemTotal = item.quantity * item.rate;
            // Вычисляем сумму скидки и округляем до двух знаков после запятой
            const discountAmount = (lineItemTotal * (item.discount_percentage / 100));
            zohoItem.discount_amount = parseFloat(discountAmount.toFixed(2)); // Zoho API часто ожидает число с плавающей точкой
          }

          return zohoItem;
        });

        const payload = {
          customer_id: this.form.customer_id,
          date: new Date().toISOString().split('T')[0],
          line_items: zohoLineItems,
          // Добавить поле для create_purchase_orders_for_deficit
          // create_purchase_orders_for_deficit: this.someCheckboxModel || false,
        };

        const response = await apiClient.post('/zoho/sales-orders', payload);

        this.successMessage = response.data.message || 'Sales order successfully created!';
        console.log('Order created:', response.data);

        this.form.customer_id = '';
        this.form.line_items = [{ item_id: '', quantity: 1, rate: 0, discount_percentage: 0 }];
        this.selectedContact = null; // НОВОЕ: Сбросить выбранный контакт после успешной отправки

      } catch (err) {
        this.submitError = 'Error creating order: ' + (err.response?.data?.message || err.message);
        console.error('Error creating order:', err.response?.data || err);
      } finally {
        this.submitting = false;
      }
    },
    // Метод для обновления информации о выбранном контакте
    updateSelectedContactInfo() {
      // Преобразуем ID формы в число для поиска,
      // так как ID из контактов могут приходить как строки.
      const customerIdToFind = parseInt(this.form.customer_id, 10);
      this.selectedContact = this.contacts.find(
        contact => contact.contact_id === this.form.customer_id
      ) || null;
    },

    // Метод для форматирования адреса доставки
    formatShippingAddress(contact) {
      if (!contact || !contact.shipping_address) {
        return 'Address not specified.';
      }
      const address = contact.shipping_address;
      if (typeof address === 'string') {
        return address;
      }
      // Если это объект, пытаемся собрать адрес из его полей
      const addressParts = [];
      if (address.attention) addressParts.push(address.attention); // Добавим внимание, если есть
      if (address.address) addressParts.push(address.address);
      if (address.street2) addressParts.push(address.street2);
      if (address.city) addressParts.push(address.city);
      if (address.state) addressParts.push(address.state);
      if (address.zip) addressParts.push(address.zip);
      if (address.country) addressParts.push(address.country);

      if (addressParts.length > 0) {
        return addressParts.filter(Boolean).join(', '); // Filter Boolean removes empty strings
      }
      return 'Address not specified.';
    },
  }
};
</script>

<style lang="scss">
// Обратите внимание: @use "sass:color"; должен быть перед @use "../assets/scss/variables"
@use "sass:color";
@use "../assets/scss/variables" as *; // Используем _zoho-variables.scss для переменных

.sales-order-form {
  max-width: 900px;
  margin: 40px auto;
  padding: 30px;
  background-color: $zoho-extra-light-gray  ; // Используем переменную
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  font-family: 'Inter', sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; // Использование 'Inter' из app.scss

  h2, h3 {
    text-align: center;
    color: $zoho-text-color;
    margin-bottom: 20px;
  }

  /* Стили для кнопок */
  .btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  }

  .add-item-button,
  .btn-success {
    background-color: $zoho-success-green !important;
    border-color: $zoho-success-green !important;
    &:hover {
      background-color: color.adjust($zoho-success-green, $lightness: -8%) !important;
      border-color: color.adjust($zoho-success-green, $lightness: -8%) !important;
    }
  }

  .remove-item-icon-button {
    background-color: transparent !important;
    border: none !important;
    color: $zoho-error-red !important;
    padding: 0.25rem;
    font-size: 1rem;
    &:hover {
      color: color.adjust($zoho-error-red, $lightness: -15%) !important;
      background-color: rgba(220, 53, 69, 0.1) !important;
    }
    svg {
      vertical-align: middle;
      // Уже настроено fill="none" stroke="currentColor" в HTML,
      // но на всякий случай можно продублировать или переопределить здесь
      fill: none;
      stroke: currentColor;
      stroke-width: 1.5; /* Для более заметного контура */
      stroke-linecap: round;
      stroke-linejoin: round;
    }
  }

  .btn-primary {
    background-color: $zoho-blue !important;
    border-color: $zoho-blue !important;
    &:hover {
      background-color: color.adjust($zoho-blue, $lightness: -8%) !important;
      border-color: color.adjust($zoho-blue, $lightness: -8%) !important;
    }
  }

  /* Стили для таблицы позиций заказа */
  .sales-order-table {
    margin-bottom: 1rem;
    width: 100%;
    border-collapse: collapse;

    thead {
      background-color: $zoho-light-gray;
      th {
        padding: 0.5rem 0.75rem;
        font-weight: normal;
        color: $zoho-text-muted;
        border-bottom: 1px solid $zoho-gray;
        text-transform: uppercase;
        font-size: 0.85rem;
        white-space: nowrap;
      }
    }

    tbody {
      td {
        padding: 0.4rem 0.75rem;
        vertical-align: middle;
        border-top: 1px solid $zoho-gray;
      }
    }

    // Ширина колонок (можно подстроить при необходимости)
    .item-desc-col { width: 35%; }
    .ordered-col    { width: 10%; }
    .discount-col   { width: 12%; }
    .rate-col       { width: 15%; }
    .amount-col     { width: 18%; }
    .action-col     { width: 10%; }

    // Контролы внутри таблицы
    .form-select-sm,
    .form-control-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem;
      border-radius: 0.2rem;
    }
  }

  /* Стили для секции итогов */
  .summary-section {
    padding: 1rem;
    background-color: white;
    border: 1px solid $zoho-gray;
    border-radius: 0.25rem;
    hr {
      border-top: 1px solid $zoho-gray;
      margin: 0.5rem 0;
    }
    .fw-bold {
      font-weight: 700 !important;
    }
    .fs-5 {
      font-size: 1.25rem !important;
    }
  }

  /* Переопределяем некоторые стандартные стили Bootstrap для форм */
  .form-control, .form-select {
    font-size: 0.9rem;
    padding: 0.375rem 0.75rem;
  }
  .form-label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
  }

  // Стили для alert-сообщений
  .alert-info {
    background-color: color.adjust($zoho-blue, $lightness: 40%) !important;
    color: color.adjust($zoho-blue, $lightness: -20%) !important;
    border-color: $zoho-blue !important;
  }
  .alert-danger {
    background-color: color.adjust($zoho-error-red, $lightness: 40%) !important;
    color: color.adjust($zoho-error-red, $lightness: -20%) !important;
    border-color: $zoho-error-red !important;
  }
  .alert-success {
    background-color: color.adjust($zoho-success-green, $lightness: 40%) !important;
    color: color.adjust($zoho-success-green, $lightness: -20%) !important;
    border-color: $zoho-success-green !important;
  }
}
</style>
