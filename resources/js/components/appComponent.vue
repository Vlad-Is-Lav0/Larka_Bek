<template>
  <v-app>
    <!-- Боковое меню -->
    <v-navigation-drawer v-model="drawer" app class="custom-drawer" >
      <v-list>
        <!-- Заголовок меню -->
        <v-list-item class="drawer-header">
          <v-list-item-avatar>
            <v-img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSwsC_tWIRSU4HdwTeytM-7bg4GJvMUlPSPHg&s"
            contain
            max-width="225"
            max-height="225"/>
          </v-list-item-avatar>
          <v-list-item-content>
            <v-list-item-title class="text-h6 font-weight-bold">Smart Innovations</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-divider />
        <!-- Пункты меню -->
        <v-list>
          <v-list-item v-for="item in menuItems" :key="item.route" :to="item.route" active-class="active-menu-item" link>
            <template v-slot:prepend>
              <v-icon class="menu-icon">{{ item.icon }}</v-icon>
            </template>
            <v-list-item-content>
              <v-list-item-title class="menu-title">{{ item.title }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-list>
    </v-navigation-drawer>

    <!-- Верхняя панель -->
    <v-app-bar app color="primary" dark class="elevation-2">
      <v-app-bar-nav-icon @click="drawer = !drawer" />
      <v-toolbar-title class="font-weight-bold">{{ pageTitle }}</v-toolbar-title>
      <v-spacer />
      <v-btn color="white" outlined @click="addItem" class="add-btn">
        <v-icon left>mdi-plus</v-icon>
        {{ addButtonLabel }}
      </v-btn>
    </v-app-bar>

    <!-- Основной контент -->
    <v-main class="main-content">
      <v-container fluid>
        <v-row>
          <v-col>
            <v-card class="content-card" elevation="3">
              <v-card-text>
                <router-view />
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
const MENU_ITEMS = [
  { title: "Задачи", icon: "mdi-check-circle", route: "/tasks" },
  { title: "Товары", icon: "mdi-cart", route: "/products" },
  { title: "Заказы", icon: "mdi-cart-outline", route: "/orders" },
];

export default {
  data() {
    return {
      drawer: true,
      menuItems: MENU_ITEMS,
    };
  },
  computed: {
    pageTitle() {
      switch (this.$route.path) {
        case "/tasks":
          return "Задачи";
        case "/products":
          return "Товары";
        case "/orders":
          return "Заказы";
        default:
          return "Главная";
      }
    },
    addButtonLabel() {
      switch (this.$route.path) {
        case "/tasks":
          return "Добавить задачу";
        case "/products":
          return "Добавить товар";
        case "/orders":
          return "Добавить заказ";
        default:
          return "Добавить";
      }
    },
  },
  methods: {
    addItem() {
      if (this.$route.path === "/tasks") {
        this.$router.push("/tasks/add");
      } else if (this.$route.path === "/products") {
        this.$router.push("/products/add");
      } else if (this.$route.path === "/orders") {
        this.$router.push("/orders/add");
      }
    },
  },
};
</script>

<style scoped>
.custom-drawer {
  background: linear-gradient(135deg, #1e3c72, #2a5298);
  color: white;
}

.drawer-header {
  padding: 20px;
  text-align: center;
}

.menu-icon {
  color: white;
}

.menu-title {
  font-weight: 500;
  color: white;
}

.active-menu-item {
  background: rgba(255, 255, 255, 0.2) !important;
  border-radius: 8px;
}

.v-app-bar {
  background: linear-gradient(135deg, #1e3c72, #2a5298) !important;
}

.add-btn {
  border-radius: 8px;
  text-transform: none;
  font-weight: bold;
}

.main-content {
  background: #f5f5f5;
  min-height: 100vh;
  padding-top: 20px;
}

.content-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
}
.v-list-item-title {
  text-align: left;
}

</style>