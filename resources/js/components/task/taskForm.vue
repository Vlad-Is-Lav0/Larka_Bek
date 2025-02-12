<template>
  <div>
    <h1>{{ isEditMode ? "Редактировать задачу" : "Добавить задачу" }}</h1>
    <v-form @submit.prevent="submitForm">
    <div class="input-group mb-3">
      <span class="input-group-text" id="basic-addon1">Описание</span>
      <input
      type="text"
      class="form-control"
      placeholder="Введите описание задачи"
      v-model="task.description"
      aria-label="Описание задачи"
      aria-describedby="basic-addon1"
      required
      maxlength="200"
      />
    </div>
      <v-switch
        v-model="task.is_completed"
        label="Выполнено"
      ></v-switch>
      <div class="d-flex justify-space-between mt-3">
        <v-btn type="submit" color="primary"> {{ isEditMode ? "Сохранить" : "Добавить" }} </v-btn>
        <v-btn v-if="isEditMode" @click="deleteTask" variant="outlined" color="red" text="Удалить" />
      </div> 
    </v-form>
  </div>
</template>

<script>
export default {
  name: "taskForm",
// task хранит описание задачи и ее статус
  data() {
    return {
      task: {
        description:        "",
        is_completed:       false,
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
    this.fetchTask();
  },
  methods: {
    fetchTask() {
// Проверяем, находимся ли мы в режиме редактирования (когда есть параметр id в URL)
      if (this.isEditMode) {
// Устанавливаем флаг загрузки в true, чтобы показать, что данные загружаются
        this.isLoading = true;
// Выполняем GET-запрос с использованием axios для получения данных задачи по ID
        axios
          .get(`/api/tasks/${this.$route.params.id}`)
          .then((response) => {
// Если запрос успешен, присваиваем данные задачи переменной task
            this.task = response.data;
// После получения данных о задаче, убираем флаг загрузки
            this.isLoading = false;
          })
          .catch((error) => {
            this.error = "Ошибка при загрузке задачи.";
// Убираем флаг загрузки, так как запрос завершен (ошибкой или успехом)
            this.isLoading = false;
          });
      }
    },
// submitForm(). Этот метод отвечает за отправку формы с задачей на сервер, с учетом того, является ли задача новой или редактируемой.
    submitForm() {
      let url;
      let method;
      if (this.isEditMode) {
        url = `/api/tasks/${this.$route.params.id}`;
        method = "put";
      } else {
        url = "/api/tasks";
        method = "post";
      }
      this.isLoading = true;
      axios({
        method,
        url,
        data: this.task,
      })
        .then(() => {
          this.isLoading = false;
          this.$router.push("/tasks");
        })
        .catch((error) => {
          this.isLoading = false;
          this.error = "Ошибка при сохранении задачи.";
          console.error("Ошибка:", error);
        });
    },
    async deleteTask() {
      if (confirm("Вы уверены, что хотите удалить задачу?")) {
        try {
          await axios.delete(`/api/tasks/${this.$route.params.id}`);
          this.$router.push("/tasks"); // Перенаправление на список задач после удаления
        } catch (error) {
          this.error = "Ошибка при удалении задачи.";
          console.error("Ошибка:", error);
        }
      }
    },
  },
};
</script>