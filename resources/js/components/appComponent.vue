<template>
  <v-app>
    <v-app-bar>
      <v-toolbar-title >Лист</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn to="/tasks"  text>Список задач</v-btn>
      <v-btn to="/tasks/add" text>Добавить задачу</v-btn>
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

<style>

</style>