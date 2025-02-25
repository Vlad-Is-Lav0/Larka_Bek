<template>
  <div>
    <h2>Список заказов</h2>
    <v-table>
      <thead>
        <tr>
          <th>№</th>
          <th>Контрагент</th>
          <th>Дата</th>
          <th>Статус</th>
          <th>Сумма</th>
        </tr>
      </thead>
      <tbody>
        <order-item
          v-for="(order, index) in orders"
          :key="order.id"
          :index="(currentPage - 1) * limit + index + 1"
          :order="order"
          @edit="editOrder"
        />
      </tbody>
    </v-table>

    <!-- Страницы -->
    <div class="pagination">
      <button @click="prevPage" :disabled="currentPage === 1" class="pagination-btn">
        ⬅ Назад
      </button>
      <span class="page-info">Страница {{ currentPage }} из {{ totalPages }}</span>
      <button @click="nextPage" :disabled="currentPage >= totalPages" class="pagination-btn">
        Вперед ➡
      </button>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import orderItem from "./orderItem.vue";

export default {
  components: { orderItem },
  data() {
    return {
      orders: [],
      currentPage: 1,
      limit: 20, // Ограничение на 20 заказов
      totalOrders: 0, // Общее количество заказов
    };
  },
  computed: {
    totalPages() {
      return Math.ceil(this.totalOrders / this.limit);
    },
  },
  mounted() {
    this.fetchOrders();
  },
  methods: {
    async fetchOrders() {
      try {
        const response = await axios.get("/api/orders", {
          params: { limit: this.limit, page: this.currentPage },
        });
        this.orders = response.data.orders;
        this.totalOrders = response.data.total;
      } catch (error) {
        console.error("Ошибка загрузки заказов:", error);
      }
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.fetchOrders();
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.fetchOrders();
      }
    },
    editOrder(orderId) {
      this.$router.push(`/orders/edit/${orderId}`);
    },
  },
};
</script>

<style scoped>
.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
}

.page-info {
  font-size: 16px;
  font-weight: bold;
}

.pagination-btn {
  background-color: #007bff;
  color: white;
  border: none;
  padding: 8px 16px;
  font-size: 14px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.3s, transform 0.2s;
}

.pagination-btn:hover {
  background-color: #0056b3;
  transform: scale(1.05);
}

.pagination-btn:disabled {
  background-color: #b0b0b0;
  cursor: not-allowed;
  transform: none;
}
</style>
