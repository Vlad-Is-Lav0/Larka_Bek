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
          :index="index + 1"
          :order="order"
          @edit="editOrder"
        />
      </tbody>
    </v-table>
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
    };
  },
  mounted() {
    this.fetchOrders();
  },
  methods: {
    async fetchOrders() {
      try {
        const response = await axios.get("/api/orders");
        this.orders = response.data;
      } catch (error) {
        console.error("Ошибка загрузки заказов:", error);
      }
    },
    editOrder(orderId) {
      this.$router.push(`/orders/edit/${orderId}`);
    },
  },
};
</script>
