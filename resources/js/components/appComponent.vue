<template>
  <v-app>
    <v-app-bar>
      <v-toolbar-title>
    <router-link to="/">Лист</router-link>
  </v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn density="comfortable" variant="outlined" to="/tasks">Список задач</v-btn>
      <v-btn density="comfortable" variant="outlined" to="/tasks/add">Добавить задачу</v-btn>
    </v-app-bar>
    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script>
export default {
  name: "appComponent",
};


</script>

<style >

</style>