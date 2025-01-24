<template>
    <div>
      <h1>{{ isEditMode ? "Редактировать задачу" : "Добавить задачу" }}</h1>
      <v-form @submit.prevent="submitForm">
        <v-text-field
          v-model="task.description"
          label="Описание"
          required
        ></v-text-field>
  
        <v-switch
          v-model="task.is_completed"
          label="Выполнено"
        ></v-switch>
  
        <v-btn type="submit" color="primary">{{ isEditMode ? "Сохранить" : "Добавить" }}</v-btn>
      </v-form>
    </div>
  </template>
  
  <script>
  export default {
    name: "taskForm",
    data() {
      return {
        task: {
          description: "",
          is_completed: false,
        },
      };
    },
    computed: {
      isEditMode() {
        return !!this.$route.params.id;
      },
    },
    methods: {
      fetchTask() {
        if (this.isEditMode) {
          fetch(`/api/tasks/${this.$route.params.id}`)
            .then((res) => res.json())
            .then((data) => {
              this.task = data;
            });
        }
      },
      submitForm() {
        const url = this.isEditMode
          ? `/api/tasks/${this.$route.params.id}`
          : "/api/tasks";
  
        const method = this.isEditMode ? "PUT" : "POST";
  
        fetch(url, {
          method,
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(this.task),
        }).then(() => this.$router.push("/tasks"));
      },
    },
    mounted() {
      this.fetchTask();
    },
  };
  </script>