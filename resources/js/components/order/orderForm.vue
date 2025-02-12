<template>
  <div>
    <h1>{{ isEditMode ? "Редактировать заказ" : "Создать заказ" }}</h1>

    <v-form @submit.prevent="submitOrder">
      <div class="input-group mb-3">
        <span class="input-group-text">Контрагент</span>
        <select class="form-select" v-model="order.agent" required>
          <option disabled value="">Выберите контрагента</option>
          <option v-for="agent in meta.agents" :key="agent.id" :value="agent.meta.href">
            {{ agent.name }}
          </option>
        </select>
      </div>
      <h3>Товары:</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Товар</th>
            <th>Цена</th>
            <th>Кол-во</th>
            <th>Сумма</th>
            <th>Действие</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in order.items" :key="index">
            <td>
              <select class="form-select" v-model="item.product" required>
                <option disabled value="">Выберите товар</option>
                <option v-for="product in meta.products" :key="product.id" :value="product.meta.href">
                  {{ product.name }}
                </option>
              </select>
            </td>
            <td>{{ (getProductPrice(item.product) / 100).toFixed(2) }} ₽</td>
            <td><input type="number" class="form-control" v-model.number="item.quantity" min="1" required /></td>
            <td>{{ ((getProductPrice(item.product) * item.quantity) / 100).toFixed(2) }} ₽</td>
            <td><button class="btn btn-danger" @click="removeItem(index)">❌</button></td>
          </tr>
        </tbody>
      </table>
      <button class="btn btn-primary" @click="addItem">➕ Добавить товар</button>

      <h3>Общая сумма: {{ totalSum.toFixed(2) }} ₽</h3>

      <div class="d-flex justify-content-between mt-3">
        <v-btn type="submit" color="primary">{{ isEditMode ? "Сохранить" : "Создать" }}</v-btn>
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
      order: {
        agent: "",
        items: [],
        status: "",
        createdAt: "",
      },
      meta: {
        agents: [],
        products: [],
      },
    };
  },
  computed: {
    isEditMode() {
      return !!this.$route.params.id;
    },
    totalSum() {
      return this.order.items.reduce(
        (sum, item) => sum + (this.getProductPrice(item.product) * item.quantity) / 100,
        0
      );
    },
  },
  methods: {
    async fetchMeta() {
      try {
        const response = await axios.get("/api/orders/meta");
        this.meta = response.data;
      } catch (error) {
        console.error("Ошибка загрузки справочников:", error);
      }
    },
    getProductPrice(productHref) {
      const product = this.meta.products.find((p) => p.meta.href === productHref);
      return product ? product.salePrices[0]?.value || 0 : 0;
    },
    addItem() {
      this.order.items.push({ product: "", quantity: 1 });
    },
    removeItem(index) {
      this.order.items.splice(index, 1);
    },
    async fetchOrder() {
  if (!this.isEditMode) return;
  try {
    // Запрашиваем сам заказ
    const response = await axios.get(`/api/orders/${this.$route.params.id}`);
    console.log("Ответ API на получение заказа:", response.data);

    this.order = {
      agent: response.data.agent?.meta?.href || "",
      items: [], // Пока пустой массив, заполним после второго запроса
      status: response.data.state?.name || "",
    };

    // Если у заказа есть позиции, делаем второй запрос
    if (response.data.positions?.meta?.href) {
      const positionsResponse = await axios.get(response.data.positions.meta.href);
      console.log("Ответ API на позиции заказа:", positionsResponse.data);

      // Проверяем, есть ли позиции
      if (Array.isArray(positionsResponse.data.rows)) {
        this.order.items = positionsResponse.data.rows.map((pos) => ({
          product: pos.assortment?.meta?.href || "",
          quantity: pos.quantity || 1,
        }));
      }
    }
  } catch (error) {
    console.error("Ошибка загрузки заказа:", error);
  }
},

    async submitOrder() {
      try {
        const payload = {
          agent: this.order.agent,
          items: this.order.items.map((item) => ({
            product: item.product,
            quantity: item.quantity,
            price: this.getProductPrice(item.product),
          })),
        };

        if (this.isEditMode) {
          await axios.put(`/api/orders/${this.$route.params.id}`, payload);
        } else {
          await axios.post("/api/orders", payload);
        }

        this.$router.push("/orders");
      } catch (error) {
        console.error("Ошибка сохранения заказа:", error);
      }
    },
    async deleteOrder() {
      if (confirm("Вы уверены, что хотите удалить заказ?")) {
        try {
          await axios.delete(`/api/orders/${this.$route.params.id}`);
          this.$router.push("/orders");
        } catch (error) {
          console.error("Ошибка удаления заказа:", error);
        }
      }
    },
  },
  async mounted() {
    await this.fetchMeta();
    if (this.isEditMode) {
      await this.fetchOrder();
    }
  },
};
</script>
