<template>
  <div>
    <h1 class="mb-4">{{ isEditMode ? "Редактировать заказ" : "Создать заказ" }}</h1>
    <v-form @submit.prevent="submitOrder">
      <div class="card mb-4">
        <div class="card-header"><strong>Выберите организацию</strong></div>
        <div class="card-body">
          <v-select
            v-model="order.organization"
            :items="organizationList"
            item-title="name"
            item-value="id"
            required
            class="fixed-select"
          />
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><strong>Выберите канал продаж</strong></div>
        <div class="card-body">
          <v-select 
            v-model="order.salesChannel"
            :items="salesChannelList" 
            item-title="name"
            item-value="id"
            required
            class="fixed-select"
          />
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><strong>Выберите проект</strong></div>
        <div class="card-body">
          <v-select 
            v-model="order.project"
            :items="projectList" 
            item-title="name"
            item-value="id"
            class="fixed-select"
          />
        </div>
      </div>

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
            <tr v-for="(item, index) in order.items" :key="index">
              <td>
                <v-select 
                  v-model="item.product"
                  :items="products"
                  item-title="name"
                  item-value="id"
                  required
                  class="fixed-select"
                  @update:modelValue="updateItemPrice(item)"
                />
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

      <div class="mb-4"><h3>Общая сумма: {{ formatPrice(totalSum) }} ₸</h3></div>

      <div class="d-flex justify-space-between mt-3">
        <v-btn type="submit" color="primary">{{ isEditMode ? "Сохранить" : "Добавить" }}</v-btn>
        <v-btn v-if="isEditMode" @click="deleteOrder" variant="outlined" color="red">Удалить</v-btn>
      </div>
    </v-form>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      isEditMode: false,
      order: { id: null, organization: null, salesChannel: null, project: null, agent: null, items: [] },
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
    formatPrice(value) {
      if (typeof value !== 'number') return '0.00';
      return value.toFixed(2);
    },
    updateItemPrice(item) {
    if (!item.product) return;
      const product = this.products.find(p => p.id === item.product);
      if (product) {
        item.price = (product.salePrices?.[0]?.value || product.price || 0) / 100;
      }
    },
    async fetchData(endpoint, target) {
      try {
        const response = await axios.get(endpoint);
        this[target] = response.data.rows || response.data || [];
      } catch (error) {
        console.error(`Ошибка при получении данных (${endpoint}):`, error);
      }
    },
    async fetchOrderDetails() {
  try {
    const response = await axios.get(`/api/orders/${this.$route.params.id}`);
    const data = response.data;

    this.order = {
      id: data.id,
      organization: data.organization?.meta?.href?.split('/').pop() || null,
      salesChannel: data.salesChannel?.meta?.href?.split('/').pop() || null,
      project: data.project?.meta?.href?.split('/').pop() || null,
      items: (data.positions || []).map(pos => ({
        productName: pos.name, // Временно сохраняем имя
        quantity: pos.quantity || 1,
        price: (pos.price || 0)/ 100, // Преобразуем цену в рубли
        product: null // Пока ставим `null`, найдем позже
      }))
    };

    console.log("Загруженные данные заказа без ID товаров:", JSON.stringify(this.order, null, 2));

    // Если товары уже загружены, выполняем поиск
    if (this.products.length) {
      this.resolveProductIds();
    }
  } catch (error) {
    console.error("Ошибка при загрузке заказа:", error);
  }
},
resolveProductIds() {
  this.order.items = this.order.items.map(item => {
    if (!item.product) { // Если у товара нет ID, ищем его
      let foundProduct = this.products.find(p => 
        p.name === item.productName && 
        (p.salePrices?.[0]?.value || p.price || 0) / 100 === item.price
      );
      if (foundProduct) {
        item.product = foundProduct.id;
      }
    }
    return item;
  });

  console.log("Обновленные товары с ID:", JSON.stringify(this.order.items, null, 2));
}
,



async submitOrder() {
  try {
    let orderData = {
      name: "Заказ №123",
      organization: this.order.organization ? `/api/entity/organization/${this.order.organization}` : null,
      salesChannel: this.order.salesChannel ? `/api/entity/saleschannel/${this.order.salesChannel}` : null,
      project: this.order.project ? `/api/entity/project/${this.order.project}` : null,
      positions: this.order.items
        .filter(item => item.product) // Убираем товары без ID
        .map(item => ({
          quantity: item.quantity,
          price: item.price, // Цена в копейках
          assortment: { meta: { href: `/api/entity/product/${item.product}` } } // Исправлено!
        }))
    };

    if (!orderData.positions.length) {
      console.error("Ошибка: заказ должен содержать хотя бы один товар.");
      alert("Ошибка: заказ должен содержать хотя бы один товар.");
      return;
    }

    console.log("Отправляемые данные заказа:", JSON.stringify(orderData, null, 2));

    let response;
    if (this.isEditMode) {
      response = await axios.put(`/api/orders/${this.order.id}`, orderData);
    } else {
      response = await axios.post('/api/orders', orderData);
    }

    console.log("Заказ успешно сохранён:", response.data);
    this.$router.push("/orders");
  } catch (error) {
    console.error("Ошибка сохранения заказа:", error.response?.data || error.message);
  }
},

    addItem() {
      this.order.items.push({ product: null, quantity: 1, price: 0 });
    },
    removeItem(index) {
      this.order.items.splice(index, 1);
    },
    async deleteOrder() {
      if (!confirm("Удалить заказ?")) return;
      try {
        await axios.delete(`/api/orders/${this.order.id}`);
        this.$router.push("/orders");
      } catch (error) {
        console.error("Ошибка удаления заказа:", error);
      }
    }
  },
  watch: {
  products: {
    handler(newVal) {
      if (newVal.length) {
        this.resolveProductIds();
      }
    },
    deep: true,
    immediate: true
  }
},

  async mounted() {
    await Promise.all([
      this.fetchData("/api/order/meta/organizations", "organizations"),
      this.fetchData("/api/order/meta/saleschannels", "salesChannels"),
      this.fetchData("/api/order/meta/projects", "projects"),
      this.fetchData("/api/order/meta/products", "products")
    ]);

    if (this.$route.params.id) {
      this.isEditMode = true;
      await this.fetchOrderDetails();
    }
  }
};
</script>
