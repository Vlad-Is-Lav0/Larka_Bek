<template>
    <div>
      <h1>{{ isEditMode ? "Редактировать задачу" : "Добавить задачу" }}</h1>
      <v-form @submit.prevent="saveProduct">
      <div class="input-group mb-3">
        <span class="input-group-text" id="basic-addon1">Товар</span>
        <input type="text"
        class="form-control"
        placeholder="Введите названия товара"
        v-model="product.name"
        aria-label="Название товара"
        aria-describedby="basic-addon1"
        required
        maxlength="200"
        />
      </div>
      <div class="input-group mb-3">
        <span class="input-group-text" id="basic-addon2">Цена</span>
        <input type="text"
        class="form-control"
        placeholder="Введите цену товара"
        v-model="product.price"
        aria-label="Цена товара"
        aria-describedby="basic-addon1"
        required
        maxlength="200"
        />
      </div>
      <v-text-field
        v-if="product.code"
        v-model="product.code"
        label="Код товара"
        readonly
      />
      <div class="d-flex justify-space-between mt-3">
        <v-btn type="submit" color="primary">{{ isEditMode ? "Сохранить" : "Добавить" }}</v-btn>
        <v-btn v-if="isEditMode" @click="deleteProduct" variant="outlined" color="red" text="Удалить" />
      </div>
      </v-form>
    </div>
  </template>
  
  <script>
  import axios from "axios";

  export default {
    name: "productForm",
  // task хранит описание задачи и ее статус
    data() {
      return {
        product: {
          code: '',
          name: '',
          price: 0,
        },
      };
    },
  //В computed свойстве isEditMode вы проверяете, есть ли параметр id в маршруте. Если есть, значит, вы находитесь в режиме редактирования.
    computed: {
      isEditMode() {
        return !!this.$route.params.id;
      },
    },
  //Метод fetchTask: В mounted() вызывается метод fetchTask, который получает данные задачи с сервера, если это режим редактирования.
    mounted() {
      if (this.isEditMode) {this.fetchProduct();}
    },
    methods: {
      async fetchProduct() {
      try {
        const response = await axios.get(`/api/products/${this.$route.params.id}`);
        this.product = {
          code: response.data.code,
          name: response.data.name,
          price: response.data.salePrices ? response.data.salePrices[0].value / 100 : 0,
        };
      } catch (error) {
        console.error("Ошибка загрузки товара", error);
      }
    },
    async saveProduct() {
      try {
        const payload = {
          name: this.product.name,
          price: this.product.price,
        };

        if (this.isEditMode) {
          await axios.put(`/api/products/${this.$route.params.id}`, payload);
        } else {
          await axios.post("/api/products", payload);
        }

        this.$router.push("/products");
      } catch (error) {
        console.error("Ошибка сохранения товара", error);
      }
    },
    async deleteProduct() {
      if (confirm("Вы уверены, что хотите удалить задачу?")) {
        try {
          await axios.delete(`/api/products/${this.$route.params.id}`);
          this.$router.push("/products"); // Перенаправление на список задач после удаления
        } catch (error) {
          this.error = "Ошибка при удалении задачи.";
          console.error("Ошибка:", error);
        }
      }
    },
  },
};
  </script>