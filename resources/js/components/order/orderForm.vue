<template>
  <div>
    <h1 class="mb-4">{{ isEditMode ? "Редактировать заказ" : "Создать заказ" }}</h1>
    <v-form @submit.prevent="submitOrder">
      <!-- Контейнер для выбора организации -->
      <div class="card mb-4">
        <div class="card-header">
          <strong>Выберите организацию</strong>
        </div>
        <div class="card-body">
          <v-select
            v-model="order.organization"
            :items="organizationList"
            item-title="name"
            item-value="id"
            label="Выберите организацию"
            required
            class="fixed-select"
          ></v-select>
        </div>
      </div>

      <!-- Контейнер для выбора канала продаж -->
      <div class="card mb-4">
        <div class="card-header">
          <strong>Выберите канал продаж</strong>
        </div>
        <div class="card-body">
          <v-select 
            v-model="order.salesChannel"
            :items="salesChannelList" 
            item-title="name"
            item-value="id"
            label="Выберите канал продаж" 
            required
            class="fixed-select"
          ></v-select>
        </div>
      </div>

      <!-- Контейнер для выбора проекта -->
      <div class="card mb-4">
        <div class="card-header">
          <strong>Выберите проект</strong>
        </div>
        <div class="card-body">
          <v-select 
            v-model="order.project"
            :items="projectList" 
            item-title="name"
            item-value="id"
            label="Выберите проект"
            class="fixed-select"
          ></v-select>
        </div>
      </div>

      <!-- Блок с товарами -->
      <div class="mb-4">
        <h3 class="mb-3">Товары:</h3>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Товар</th>
              <th>Кол-во</th>
              <th>Цена</th>
              <th>Сумма</th>
              <th>Действие</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in (order?.items || [])" :key="index">
              <td>
                <v-select 
                  v-model="item.product"
                  :items="products"
                  item-value="id"
                  item-title="name"
                  label="Выберите товар"
                  required
                  class="fixed-select"
                  @update:modelValue="updateItemPrice(item)"
                ></v-select>
              </td>
              <td>
                <input type="number" v-model.number="item.quantity" min="1" class="form-control" required />
              </td>
              <td>{{ formatPrice(item.price) }} ₸</td>
              <td>{{ formatPrice(item.quantity * item.price) }} ₸</td>
              <td>
                <button class="btn btn-danger" @click="removeItem(index)">❌</button>
              </td>
            </tr>
          </tbody>
        </table>
        <button class="btn btn-outline-primary" type="button" @click="addItem">Добавить товар</button>
      </div>

      <!-- Общая сумма -->
      <div class="mb-4">
        <h3>Общая сумма: {{ formatPrice(totalSum) }} ₸</h3>
      </div>

      <!-- Кнопка отправки формы -->
      <v-btn type="submit" color="primary" class="mt-4">{{ isEditMode ? "Сохранить" : "Создать" }}</v-btn>
    </v-form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      isEditMode: false,
      order: {
        id: null,
        organization: null,
        salesChannel: null,
        project: null,
        agent: null,
        items: []
      },
      retailCustomerId: "1e999adb-d141-11ec-0a80-075e00bbab3a",
      organizations: [],
      salesChannels: [],
      projects: [],
      products: []
    };
  },
  computed: {
    totalSum() {
      return this.order.items.reduce((sum, item) => sum + (item.quantity * item.price), 0);
    },
    organizationList() {
      return Array.isArray(this.organizations) ? this.organizations : [];
    },
    salesChannelList() {
      return Array.isArray(this.salesChannels) ? this.salesChannels : [];
    },
    projectList() {
      return Array.isArray(this.projects) ? this.projects : [];
    }
  },
  methods: {
    async fetchData(endpoint, target) {
      try {
        const response = await axios.get(endpoint);
        this[target] = response.data;
      } catch (error) {
        console.error(`Ошибка при получении данных (${endpoint}):`, error);
        alert(`Ошибка при загрузке данных.`);
      }
    },
    fetchOrganizations() {
      this.fetchData('/api/order/meta/organizations', 'organizations');
    },
    fetchSalesChannels() {
      this.fetchData('/api/order/meta/saleschannels', 'salesChannels');
    },
    fetchProjects() {
      this.fetchData('/api/order/meta/projects', 'projects');
    },
    fetchProducts() {
      this.fetchData('/api/order/meta/products', 'products');
    },
    getProductPrice(productId) {
      const product = this.products.find(p => p.id === productId);
      return product ? (product.salePrices?.[0]?.value / 100 || product.price / 100 || 0) : 0;
    },
    updateItemPrice(item) {
      if (item.product) {
        item.price = this.getProductPrice(item.product);
      }
    },
    formatPrice(value) {
      return value ? value.toFixed(2) : '0.00';
    },
    async submitOrder() {
      try {
        let orderData = {
          name: "Заказ №123",
          organization: this.order.organization,
          agent: this.order.agent && this.order.agent.meta 
              ? this.order.agent 
              : { meta: { href: `/api/entity/counterparty/${this.retailCustomerId}` } },
          positions: this.order.items.map(item => ({
            quantity: item.quantity,
            price: item.price * 100,
            assortment: { meta: { href: `/api/entity/product/${item.product}` } }
          }))
        };

        let response;
        if (this.isEditMode && this.order.id) {
          response = await axios.put(`/api/orders/${this.order.id}`, orderData);
        } else {
          response = await axios.post('/api/orders', orderData);
        }

        console.log('Заказ успешно сохранён:', response.data);
      } catch (error) {
        console.error('Ошибка при сохранении заказа:', error);
      }
    },
    addItem() {
      this.order.items.push({ product: null, quantity: 1, price: 0 });
    },
    removeItem(index) {
      this.order.items.splice(index, 1);
    },
    fetchOrderDetails() {
    const orderId = this.$route.params.id;
    if (!orderId) {
        console.error("Ошибка: orderId не найден в маршруте");
        return;
    }

    axios.get(`/api/orders/${orderId}`)
        .then(response => {
            this.order = response.data || {}; 
            if (!this.order.items) {
                this.order.items = [];
            }
        })
        .catch(error => {
            console.error("Ошибка при загрузке заказа:", error);
            this.order = { items: [] }; // Если API не ответило, устанавливаем пустой массив
        });
}

  },
  mounted() {
    if (this.$route.params.id) {
      this.isEditMode = true;
      this.fetchOrderDetails();
    }
    this.fetchOrganizations();
    this.fetchSalesChannels();
    this.fetchProjects();
    this.fetchProducts();
  }
};
</script>

<style>
.v-list-item__content {
  max-width: 400px;
  min-width: 400px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
