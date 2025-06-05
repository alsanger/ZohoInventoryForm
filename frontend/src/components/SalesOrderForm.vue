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
            <th class="item-desc-col">ITEM</th>
            <th class="ordered-col">ORDERED</th>
            <th class="discount-col">DISCOUNT (%)</th>
            <th class="rate-col">RATE</th>
            <th class="amount-col">AMOUNT</th>
            <th class="action-col text-center"></th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, index) in form.line_items" :key="item.item_id || 'new-item-' + index"> <td class="item-desc-col">
            <select v-model="item.item_id" @change="updateItemDetails(index)" class="form-select form-select-sm" required>
              <option value="" disabled>Select Item</option>
              <option v-for="product in products" :key="product.item_id" :value="product.item_id">
                {{ product.name }} ({{ product.sku }})
              </option>
            </select>
          </td>
            <td class="ordered-col">
              <div class="d-flex flex-column align-items-center justify-content-start h-100">
                <input
                  type="number"
                  v-model.number="item.quantity"
                  :min="item.item_id ? 1 : 0" class="form-control form-control-sm text-center"
                  :class="{ 'text-danger': item.item_id && item.quantity > (item.available_stock || 0) && (!form.create_purchase_orders_for_deficit || !isDeficitFullyCoveredByPOForItem(item)) }"
                  required
                >
                <small v-if="item.item_id && item.available_stock !== undefined" :class="{ 'text-danger': item.quantity > (item.available_stock || 0) && (!form.create_purchase_orders_for_deficit || !isDeficitFullyCoveredByPOForItem(item)) }" class="pt-1">
                  Available: {{ item.available_stock }}
                </small>
              </div>
            </td>
            <td class="discount-col">
              <input type="number" v-model.number="item.discount_percentage" min="0" max="100" step="0.01" class="form-control form-control-sm text-center">
            </td>
            <td class="rate-col">
              <input type="number" v-model.number="item.rate" step="0.01" min="0" class="form-control form-control-sm text-end" required>
            </td>
            <td class="amount-col text-end">
              <div>{{ formatCurrency(calculateLineItemAmount(index)) }}</div>
            </td>
            <td class="action-col text-center align-top">
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

      <div v-if="hasDeficitInOrder" class="form-group mt-3">
        <div class="form-check">
          <input
            type="checkbox"
            id="createPoForDeficit"
            v-model="form.create_purchase_orders_for_deficit"
            class="form-check-input"
          >
          <label class="form-check-label" for="createPoForDeficit">
            Create purchase orders for deficit items
          </label>
        </div>
      </div>

      <div v-if="hasDeficitInOrder && form.create_purchase_orders_for_deficit" class="mt-4">
        <h4>Purchase Orders for Deficit Items</h4>
        <table class="table table-bordered table-striped sales-order-table">
          <thead>
          <tr>
            <th class="item-desc-col">ITEM</th>
            <th class="ordered-col">QUANTITY</th>
            <th>VENDOR</th>
            <th class="rate-col">RATE</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, index) in deficitItems" :key="item.item_id + '-po-deficit'"> <td class="item-desc-col">{{ item.name }}</td>
            <td class="ordered-col">
              <div class="d-flex flex-column align-items-center justify-content-start h-100">
                <input
                  type="number"
                  v-model.number="item.quantity_to_order"
                  :min="0"
                  class="form-control form-control-sm text-center"
                  :class="{ 'text-danger': item.quantity_to_order < item.deficit_needed }"
                  required
                >
                <small v-if="item.quantity_to_order < item.deficit_needed" class="text-danger pt-1">
                  Min needed: {{ item.deficit_needed }}
                </small>
              </div>
            </td>
            <td>
              <select
                v-model="item.selected_vendor_id"
                class="form-select form-select-sm"
                required
              >
                <option value="">Select a vendor</option>
                <option
                  v-for="vendor in allVendors"
                  :key="vendor.contact_id" :value="vendor.contact_id" >
                  {{ vendor.contact_name }} </option>
              </select>
            </td>
            <td class="rate-col">
              <input
                type="number"
                v-model.number="item.purchase_rate"
                class="form-control form-control-sm"
                min="0"
                step="any"
                required >
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="text-end mt-4">
        <button type="submit" class="btn btn-primary py-2" :disabled="submitting || hasAnyValidationErrors">
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
            <h5 class="modal-title" id="newContactModalLabel">Create New Contact</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="createNewContact">
            <div class="modal-body">
              <div class="mb-3">
                <label for="newContactName" class="form-label">Contact Name:</label>
                <input type="text" class="form-control" id="newContactName" v-model="newContact.contact_name" required>
              </div>
              <div class="mb-3">
                <label for="newContactType" class="form-label">Contact Type:</label>
                <select class="form-select" id="newContactType" v-model="newContact.contact_type" required>
                  <option value="customer">Customer</option>
                  <option value="vendor">Vendor</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="creatingNewContact">
                {{ creatingNewContact ? 'Creating...' : 'Create' }}
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
      allVendors: [],
      selectedContact: null,
      newContact: {
        contact_name: '',
        contact_type: 'customer',
      },
      form: {
        customer_id: '',
        line_items: [
          // !!! ИЗМЕНЕНИЕ: Количество по умолчанию 0 для первой строки
          { item_id: '', quantity: 0, rate: 0, discount_percentage: 0, available_stock: 0, quantity_to_order: undefined, selected_vendor_id: undefined, purchase_rate: undefined }
        ],
        create_purchase_orders_for_deficit: false,
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
    },
    hasDeficitInOrder() {
      return this.form.line_items.some(item => {
        // Проверяем только выбранные товары с item_id
        return item.item_id && item.quantity > 0 && (item.available_stock || 0) < item.quantity;
      });
    },
    deficitItems() {
      return this.form.line_items.filter(item => {
        // Фильтруем только те, у которых есть реальный дефицит и выбран item_id
        return item.item_id && item.quantity > 0 && (item.available_stock || 0) < item.quantity;
      }).map(item => {
        const product = this.products.find(p => p.item_id === item.item_id);
        const deficitQtyNeeded = Math.max(0, item.quantity - (item.available_stock || 0));

        // !!! ИЗМЕНЕНИЕ: Инициализация полей для PO - только если они UNDEFINED или NULL
        // Это предотвратит перезапись значений, введенных пользователем
        if (item.quantity_to_order === undefined || item.quantity_to_order === null) {
          item.quantity_to_order = deficitQtyNeeded;
        }
        if (item.selected_vendor_id === undefined || item.selected_vendor_id === null) {
          item.selected_vendor_id = ''; // Устанавливаем пустую строку, чтобы "Select a vendor" был выбран по умолчанию
        }
        if (item.purchase_rate === undefined || item.purchase_rate === null) {
          item.purchase_rate = product && product.purchase_rate !== undefined ? product.purchase_rate : 0;
        }

        // Добавляем display-only свойства для удобства отображения
        item.name = product ? product.name : 'Unknown Item';
        item.deficit_needed = deficitQtyNeeded;

        return item; // Возвращаем ссылку на модифицированный исходный объект
      });
    },

    isDeficitFullyCoveredByPO() {
      if (!this.form.create_purchase_orders_for_deficit) {
        return false; // !!! ИЗМЕНЕНИЕ: Если PO не создаются, и есть дефицит, то дефицит не покрыт
      }
      return this.deficitItems.every(item => {
        const totalCovered = (item.available_stock || 0) + (item.quantity_to_order || 0);
        return totalCovered >= item.quantity && item.quantity_to_order >= item.deficit_needed && item.selected_vendor_id && item.purchase_rate >= 0;
      });
    },

    hasInvalidPOQuantityToOrder() {
      if (!this.form.create_purchase_orders_for_deficit) return false;
      return this.deficitItems.some(item => {
        return (item.quantity_to_order || 0) < item.deficit_needed || item.quantity_to_order <= 0; // !!! ИЗМЕНЕНИЕ: Количество PO должно быть > 0
      });
    },

    hasUnselectedVendorInPO() {
      if (!this.form.create_purchase_orders_for_deficit) return false;
      return this.deficitItems.some(item => !item.selected_vendor_id);
    },
    hasInvalidPORate() { // !!! ИЗМЕНЕНИЕ: Новая проверка для ставки закупки
      if (!this.form.create_purchase_orders_for_deficit) return false;
      return this.deficitItems.some(item => item.purchase_rate === undefined || item.purchase_rate < 0);
    },

    hasAnyValidationErrors() {
      // 1. Базовая валидация: клиент выбран, все основные позиции заказа заполнены корректно
      if (!this.form.customer_id) return true;
      if (this.form.line_items.length === 0) return true;
      if (this.form.line_items.some(item => !item.item_id || item.quantity < 0 || item.rate < 0)) { // !!! ИЗМЕНЕНИЕ: quantity может быть 0
        return true;
      }
      // Если есть пустые строки, их нельзя отправлять (добавлять)
      if (this.form.line_items.some(item => item.item_id && item.quantity === 0 && item.rate === 0)) { // Это для случая когда item выбран, но quantity=0 и rate=0.
        // Либо quantity > 0, либо item_id пустой.
        return true;
      }
      // Убедимся, что для выбранных товаров quantity > 0
      if (this.form.line_items.some(item => item.item_id && item.quantity <= 0)) {
        return true;
      }


      // 2. Валидация, связанная с дефицитом и PO
      if (this.hasDeficitInOrder) { // Если в заказе есть дефицит
        if (this.form.create_purchase_orders_for_deficit) { // И пользователь выбрал создать PO
          if (!this.isDeficitFullyCoveredByPO) return true;
          if (this.hasInvalidPOQuantityToOrder) return true;
          if (this.hasUnselectedVendorInPO) return true;
          if (this.hasInvalidPORate) return true; // !!! ИЗМЕНЕНИЕ: Добавлена проверка ставки закупки
        } else {
          // Если дефицит есть, но PO не создается, то кнопка заблокирована
          return true;
        }
      }
      return false;
    }
  },
  watch: {
    'form.create_purchase_orders_for_deficit'(newValue) {
      if (!newValue) {
        this.form.line_items.forEach(item => {
          // Сбрасываем значения, но не удаляем свойства
          item.quantity_to_order = undefined;
          item.selected_vendor_id = undefined;
          item.purchase_rate = undefined;
        });
      }
    }
  },
  async mounted() {
    await this.fetchInitialData();
    await this.fetchAllVendors();
    if (window.bootstrap && window.bootstrap.Modal) {
      this.newContactModalInstance = new window.bootstrap.Modal(document.getElementById('newContactModal'));
    } else {
      console.warn('Bootstrap Modal object not found. Ensure Bootstrap JS is loaded via CDN in index.html.');
    }
    if (this.products.length > 0) {
      console.log('Products loaded, checking available_stock for first product:', this.products[0]);
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
    isDeficitFullyCoveredByPOForItem(item) {
      if (!this.form.create_purchase_orders_for_deficit) {
        return false;
      }
      const totalCovered = (item.available_stock || 0) + (item.quantity_to_order || 0);
      // !!! ИЗМЕНЕНИЕ: Также учитываем, что PO-quantity не может быть меньше deficit_needed
      return totalCovered >= item.quantity && item.quantity_to_order >= item.deficit_needed;
    },
    async fetchInitialData() {
      this.loading = true;
      this.error = null;
      try {
        const contactsResponse = await apiClient.get('/zoho/contacts');
        this.contacts = contactsResponse.data.contacts;
        if (this.form.customer_id) {
          this.updateSelectedContactInfo();
        }

        const itemsResponse = await apiClient.get('/zoho/items');
        this.products = itemsResponse.data.items.map(item => ({
          ...item,
          available_stock: item.available_stock !== undefined ? item.available_stock : Math.floor(Math.random() * 50) + 1,
          purchase_rate: item.purchase_rate !== undefined ? item.purchase_rate : (Math.floor(Math.random() * 200) + 10) // Добавил мок purchase_rate
        }));

      } catch (err) {
        this.error = 'Failed to load data: ' + (err.response?.data?.message || err.message);
        console.error('Error loading data:', err);
      } finally {
        this.loading = false;
      }
    },
    async fetchAllVendors() {
      this.error = null;
      try {
        const response = await apiClient.get('/zoho/vendors');
        // !!! ИЗМЕНЕНИЕ: ZohoVendorService теперь возвращает 'vendors' с contact_id и contact_name
        this.allVendors = response.data.vendors;
        console.log('Successfully fetched vendors:', this.allVendors);
      } catch (error) {
        console.error('Error fetching all vendors:', error);
        this.error = 'Failed to load vendors: ' + (error.response?.data?.message || error.message);
      }
    },
    addLineItem() {
      // !!! ИЗМЕНЕНИЕ: Количество по умолчанию 0
      this.form.line_items.push({
        item_id: '',
        quantity: 0,
        rate: 0,
        discount_percentage: 0,
        available_stock: 0,
        quantity_to_order: undefined,
        selected_vendor_id: undefined,
        purchase_rate: undefined
      });
    },
    removeLineItem(index) {
      this.form.line_items.splice(index, 1);
    },
    updateItemDetails(index) {
      const selectedProduct = this.products.find(p => p.item_id === this.form.line_items[index].item_id);
      const currentItem = this.form.line_items[index];

      if (selectedProduct) {
        currentItem.rate = selectedProduct.rate || 0;
        currentItem.available_stock = selectedProduct.available_stock !== undefined ? selectedProduct.available_stock : 0;

        // Если товар стал дефицитным
        const deficitQty = Math.max(0, currentItem.quantity - (currentItem.available_stock || 0));

        if (deficitQty > 0) {
          // Инициализируем только если не задано или задано 0 (для quantity_to_order)
          if (currentItem.quantity_to_order === undefined || currentItem.quantity_to_order === null || currentItem.quantity_to_order === 0) {
            currentItem.quantity_to_order = deficitQty;
          }
          if (currentItem.selected_vendor_id === undefined || currentItem.selected_vendor_id === null) {
            currentItem.selected_vendor_id = ''; // Устанавливаем пустую строку для выбора по умолчанию
          }
          // !!! ИЗМЕНЕНИЕ: Устанавливаем purchase_rate только если не задано или задано 0
          if (currentItem.purchase_rate === undefined || currentItem.purchase_rate === null || currentItem.purchase_rate === 0) {
            currentItem.purchase_rate = selectedProduct.purchase_rate || 0;
          }
        } else {
          // Если дефицита нет, сбрасываем PO-поля
          currentItem.quantity_to_order = undefined;
          currentItem.selected_vendor_id = undefined;
          currentItem.purchase_rate = undefined;
        }

      } else {
        // Если товар не выбран, сбрасываем все связанные поля
        currentItem.available_stock = 0;
        currentItem.rate = 0; // Сбросить rate при невыбранном товаре
        currentItem.quantity = 0; // !!! ИЗМЕНЕНИЕ: Сбросить количество при невыбранном товаре
        currentItem.quantity_to_order = undefined;
        currentItem.selected_vendor_id = undefined;
        currentItem.purchase_rate = undefined;
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
        this.updateSelectedContactInfo();
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

      if (this.hasAnyValidationErrors) {
        if (!this.form.customer_id) {
          this.submitError = 'Please select a client.';
        } else if (this.form.line_items.some(item => !item.item_id || item.quantity <= 0 || item.rate < 0)) {
          this.submitError = 'Please ensure all selected line items have a quantity greater than 0 and a non-negative rate.';
        } else if (this.hasDeficitInOrder) {
          if (!this.form.create_purchase_orders_for_deficit) {
            this.submitError = 'There are deficit items. Please either adjust the quantity or check "Create purchase orders for deficit items" to cover the deficit.';
          } else if (this.hasUnselectedVendorInPO) {
            this.submitError = 'Please select a vendor for all deficit items in the Purchase Orders section.';
          } else if (this.hasInvalidPOQuantityToOrder) {
            this.submitError = 'The quantity to order for deficit items cannot be less than the actual deficit, and must be greater than 0. Please adjust.';
          } else if (this.hasInvalidPORate) { // !!! ИЗМЕНЕНИЕ: Добавлена ошибка для ставки закупки
            this.submitError = 'Purchase rate for deficit items cannot be undefined or negative. Please specify a valid rate.';
          } else if (!this.isDeficitFullyCoveredByPO) {
            this.submitError = 'The ordered quantity still exceeds available stock even with the proposed purchase orders. Please adjust quantities or order more via PO.';
          }
        }
        this.submitting = false;
        return;
      }

      try {
        const zohoLineItems = this.form.line_items.map(item => {
          const zohoItem = {
            item_id: item.item_id,
            quantity: item.quantity,
            rate: item.rate,
          };
          if (typeof item.discount_percentage === 'number' && item.discount_percentage > 0) {
            const lineItemTotal = item.quantity * item.rate;
            const discountAmount = (lineItemTotal * (item.discount_percentage / 100));
            zohoItem.discount_amount = parseFloat(discountAmount.toFixed(2));
          }
          return zohoItem;
        }).filter(item => item.item_id && item.quantity > 0); // !!! ИЗМЕНЕНИЕ: Фильтруем пустые/нулевые строки перед отправкой

        const payload = {
          customer_id: this.form.customer_id,
          date: new Date().toISOString().split('T')[0],
          line_items: zohoLineItems,
        };

        if (this.hasDeficitInOrder && this.form.create_purchase_orders_for_deficit) {
          payload.create_purchase_orders_for_deficit = true;
          payload.purchase_orders_data = this.deficitItems.map(item => ({
            item_id: item.item_id,
            quantity_needed: item.quantity_to_order,
            vendor_id: item.selected_vendor_id,
            purchase_rate: item.purchase_rate
          }));
        } else {
          payload.create_purchase_orders_for_deficit = false;
        }

        const response = await apiClient.post('/zoho/sales-orders', payload);

        this.successMessage = response.data.message || 'Sales order successfully created!';
        console.log('Order created:', response.data);

        this.form = {
          customer_id: '',
          line_items: [{ item_id: '', quantity: 0, rate: 0, discount_percentage: 0, available_stock: 0, quantity_to_order: undefined, selected_vendor_id: undefined, purchase_rate: undefined }], // !!! ИЗМЕНЕНИЕ: Количество 0
          create_purchase_orders_for_deficit: false,
        };
        this.selectedContact = null;

      } catch (err) {
        this.submitError = 'Error creating order: ' + (err.response?.data?.message || err.message);
        console.error('Error creating order:', err.response?.data || err);
      } finally {
        this.submitting = false;
      }
    },
    updateSelectedContactInfo() {
      this.selectedContact = this.contacts.find(
        contact => contact.contact_id === this.form.customer_id
      ) || null;
    },
    formatShippingAddress(contact) {
      if (!contact || !contact.shipping_address) {
        return 'Address not specified.';
      }
      const address = contact.shipping_address;
      if (typeof address === 'string') {
        return address;
      }
      const addressParts = [];
      if (address.attention) addressParts.push(address.attention);
      if (address.address) addressParts.push(address.address);
      if (address.street2) addressParts.push(address.street2);
      if (address.city) addressParts.push(address.city);
      if (address.state) addressParts.push(address.state);
      if (address.zip) addressParts.push(address.zip);
      if (address.country) addressParts.push(address.country);

      if (addressParts.length > 0) {
        return addressParts.filter(Boolean).join(', ');
      }
      return 'Address not specified.';
    },
  }
};
</script>

<style lang="scss">
@use "sass:color";
@use "../assets/scss/variables" as *;

.sales-order-form {
  max-width: 1000px;
  margin: 40px auto;
  padding: 30px;
  background-color: $zoho-extra-light-gray;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  font-family: 'Inter', sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

  h2, h3 {
    text-align: center;
    color: $zoho-text-color;
    margin-bottom: 20px;
  }

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
      fill: none;
      stroke: currentColor;
      stroke-width: 1.5;
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
        text-align: center;
        vertical-align: middle;
      }
    }

    tbody {
      td {
        padding: 0.4rem 0.75rem;
        vertical-align: top;
        border-top: 1px solid $zoho-gray;
      }
    }

    .item-desc-col { width: 40%; }
    .ordered-col    { width: 12%; }
    .discount-col   { width: 10%; }
    .rate-col       { width: 14%; }
    .amount-col     { width: 14%; }
    .action-col     { width: 10%; }

    .form-select-sm,
    .form-control-sm {
      margin-top: 0.5rem;
      margin-bottom: 0.25rem;
    }

    td > div, td > button {
      margin-top: 0.5rem;
      margin-bottom: 0.25rem;
      &:first-child {
        margin-top: 0.5rem;
      }
    }
    .action-col .remove-item-icon-button {
      margin-top: 0.5rem;
      margin-bottom: 0.25rem;
    }

    .ordered-col small {
      margin-top: 0 !important;
      padding-top: 0.25rem !important;
      margin-bottom: 0 !important;
      font-size: 0.75rem;
      line-height: 1;
    }

    .amount-col div {
      margin-top: 0.5rem;
      margin-bottom: 0.25rem;
      line-height: calc(0.875rem + 0.5rem + 0.25rem);
    }
  }

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

  .form-control, .form-select {
    font-size: 0.9rem;
    padding: 0.375rem 0.75rem;
  }
  .form-label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
  }

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

  .text-danger {
    color: $zoho-error-red !important;
  }
}
</style>
