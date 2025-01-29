<template>
    <div>
      <h1>Список задач</h1>
      <select v-model="filter" class="form-select" aria-label="Фильтр по статусу">
      <option v-for="(filterOption, index) in filters" :key="index" :value="filterOption">
      {{ filterOption }}
      </option>
      </select>
      <v-table v-if="tasks != []">
        <thead>
          <tr>
            <th>Номер задачи</th>
            <th>Описание</th>
            <th>Статус</th>
            <th>Дата создания</th>
          </tr>
        </thead>
        <!-- Директива Vue для циклического отображения элементов массива filteredTasks -->
         <!-- Каждый элемент массива передается в переменную task -->
         <!-- :task="task" Пропс. Передает объект task (одна задача из массива filteredTasks) в дочерний компонент task-item -->
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
  import axios from "axios";
import taskItem from "./taskItem.vue";
  
  export default {
    name: "taskList",
    components: { taskItem },
    data() {
      return {
        tasks:            [], // Список задач
        const_tasks:      [], // Список задач
        filter:           "Все", // Фильтр
        filters:          ["Все", "Выполненые", 
                            "Не выполнены"
                          ],
      };
    },
    computed: {
      filteredTasks() {
        let is_task = []
        if (this.tasks != undefined) {
          this.tasks.forEach(item => {
          if (this.filter === "Выполненые") {
            if(item.is_completed == '1') is_task.push(item)
          }
          if (this.filter === "Не выполнены") {
            if(item.is_completed == '0') is_task.push(item)
          }
          if (this.filter === "Все") {
            is_task.push(item)
          }
        });
        console.log(is_task)
        }
        
        return is_task
      }
    },
    mounted() {
      this.fetchTasks();
    },
    methods: {
      //Используется стандартный fetch, чтобы отправить GET-запрос на URL /api/tasks, затем парсится ответ в формат JSON и сохраняется в this.tasks.
      fetchTasks() {
        axios.get("/api/tasks") // Отправляем GET-запрос
          .then( res => { // Обрабатываем успешный ответ
            this.tasks = res.data.data           // Сохраняем данные в свойство tasks
            this.const_tasks = res.data.data    // Создаем неизменяемую копию данных
          })
          .catch(res => {      // Обрабатываем ошибку
            console.log(res)   // Логируем ошибку в консоль
          })
      },
      //Метод для редактирования задачи. Он перенаправляет пользователя на страницу редактирования задачи с использованием Vue Router, передавая id задачи.
      editTask(id) {
        this.$router.push(`/tasks/edit/${id}`);
      },
      //Метод для удаления задачи. Отправляется DELETE-запрос на сервер для удаления задачи с соответствующим id.
      //После этого список задач обновляется с помощью вызова fetchTasks.
      deleteTask(id) {
        axios
          .delete(`/api/tasks/${id}`) // Отправляем DELETE-запрос
          .then(() => this.fetchTasks()) // После успешного удаления обновляем список задач
          .catch((error) => {
          console.error(error); // Обрабатываем ошибку
        });
      },
    },
    //Это жизненный цикл Vue, который выполняется после того,
    // как компонент был вставлен в DOM. В этом методе вызывается fetchTasks(), чтобы загрузить список задач при монтировании компонента.
    
  };
  </script>