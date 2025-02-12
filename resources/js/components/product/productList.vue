<template>
  <div>
    <h1>Список товаров</h1>
    <v-table v-if="products.length > 0">
      <thead>
        <tr>
          <th>Номер</th>
          <th>Код</th>
          <th>Название</th>
          <th>Стоимость</th>
        </tr>
      </thead>
      <tbody>
        <productItem
          v-for="(product, index) in products"
          :key="product.id"
          :index="index + 1"
          :product="product"
          @edit="editProduct"
        />
      </tbody>
    </v-table>
  </div>
</template>

<script>
import axios from "axios";
import productItem from "./productItem.vue";

export default {
  name: "productList",
  components: { productItem },
  data() {
    return {
      products: [],
    };
  },
  mounted() {
    this.fetchProducts();
  },
  methods: {
    async fetchProducts() {
      try {
        const response = await axios.get("/api/products");
        this.products = response.data;
      } catch (error) {
        console.error("Ошибка загрузки товаров", error);
      }
    },
    editProduct(id) {
      this.$router.push({ name: "editProduct", params: { id } });
    },
  },
};
</script>