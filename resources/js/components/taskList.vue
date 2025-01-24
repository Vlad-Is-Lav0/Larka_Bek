<template>
    <div>
      <h1>Список задач</h1>
      <v-select
        v-model="filter"
        :items="filters"
        label="Фильтр по статусу"
      ></v-select>
  
      <v-table>
        <thead>
          <tr>
            <th>Описание</th>
            <th>Статус</th>
            <th>Дата создания</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <task-item
            v-for="task in filteredTasks"
            :key="task.id"
            :task="task"
            @edit="editTask"
            @delete="deleteTask"
          />
        </tbody>
      </v-table>
    </div>
  </template>
  
  <script>
  import taskItem from "./taskItem.vue";
  
  export default {
    name: "taskList",
    components: { taskItem },
    data() {
      return {
        tasks: [], // Список задач
        filter: "Все", // Фильтр
        filters: ["Все", "Выполненые", "Не выполнены"],
      };
    },
    computed: {
      filteredTasks() {
        if (this.filter === "completed") {
          return this.tasks.filter((task) => task.is_completed);
        } else if (this.filter === "not completed") {
          return this.tasks.filter((task) => !task.is_completed);
        }
        return this.tasks;
      },
    },
    methods: {
      //Используется стандартный fetch, чтобы отправить GET-запрос на URL /api/tasks, затем парсится ответ в формат JSON и сохраняется в this.tasks.
      fetchTasks() {
        fetch("/api/tasks")
          .then((res) => res.json())
          .then((data) => {
            this.tasks = data;
          });
      },
      //Метод для редактирования задачи. Он перенаправляет пользователя на страницу редактирования задачи с использованием Vue Router, передавая id задачи.
      editTask(id) {
        this.$router.push(`/tasks/edit/${id}`);
      },
      //Метод для удаления задачи. Отправляется DELETE-запрос на сервер для удаления задачи с соответствующим id.
      //После этого список задач обновляется с помощью вызова fetchTasks.
      deleteTask(id) {
        fetch(`/api/tasks/${id}`, { method: "DELETE" }).then(() =>
          this.fetchTasks()
        );
      },
    },
    //Это жизненный цикл Vue, который выполняется после того,
    // как компонент был вставлен в DOM. В этом методе вызывается fetchTasks(), чтобы загрузить список задач при монтировании компонента.
    mounted() {
      this.fetchTasks();
    },
  };
  </script>