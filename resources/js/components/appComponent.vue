<template>
  <v-app>
    <!-- Боковая панель -->
    <v-navigation-drawer v-model="drawer" app permanent>
      <v-list>
        <v-list-item>
          <v-list-item-avatar>
            <v-img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSwsC_tWIRSU4HdwTeytM-7bg4GJvMUlPSPHg&s"></v-img>
          </v-list-item-avatar>
          <v-list-item-content>
            <v-list-item-title class="text-h6">Smart Innovations</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-divider></v-divider>
        <v-list-item-group v-model="selectedItem">
          <v-list-item v-for="item in menuItems" :key="item.title" link @click="navigate(item.route)">
            <v-list-item-icon>
              <v-icon>{{ item.icon }}</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title>{{ item.title }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-item-group>
      </v-list>
    </v-navigation-drawer>
    <!-- Верхняя панель -->
    <v-app-bar app color="primary" dark>
      <v-toolbar-title>{{ pageTitle }}</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn color="success" dark @click="addItem">
        <v-icon left>mdi-plus</v-icon> {{ addButtonLabel }}
      </v-btn>
    </v-app-bar>
    <!-- Контент -->
    <v-main>
      <router-view></router-view>
    </v-main>
  </v-app>
</template>

<script>
export default {
  data() {
    return {
      drawer: true,
      selectedItem: 0,
      menuItems: [
        { title: "Задачи", icon: "mdi-check-circle", route: "/tasks" },
        { title: "Товары", icon: "mdi-cart", route: "/products" },
      ],
    };
  },
  computed: {
    // Заголовок страницы в зависимости от активного маршрута
    pageTitle() {
      return this.$route.path === "/tasks" ? "Задачи" : "Товары";
    },
    // Надпись на кнопке добавления
    addButtonLabel() {
      return this.$route.path === "/tasks" ? "Добавить задачу" : "Добавить товар";
    },
  },
  methods: {
    navigate(route) {
      this.$router.push(route);
    },
    addItem() {
      if (this.$route.path === "/tasks") {
        // Логика добавления задачи
        this.$router.push("/tasks/add");
      } else if (this.$route.path === "/products") {
        // Логика добавления товара
        this.$router.push("/products/add");
        console.log("Добавить новый товар");
      }
    },
  },
};
</script>
