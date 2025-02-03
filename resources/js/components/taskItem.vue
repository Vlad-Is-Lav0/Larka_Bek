<template>
    <tr>
       <!-- Отображение ID задачи -->
    <td>{{ idex }}</td>

      <!-- Отображение описания задачи -->
    <td>{{ task.description }}</td>

      <!-- Отображение статуса выполнения задачи -->
    <td>{{ task.is_completed ? "Выполнено" : "Не выполнено" }}</td>

      <!-- Отображение даты создания задачи -->
    <td>{{ formatDate(task.created_at) }}</td> <!-- Форматируем дату --> 

      <!-- Кнопки для редактирования и удаления задачи -->
    <td>
      <v-btn @click="$emit('edit', task.id)" variant="outlined" color="green" text="Редактировать" />
      <v-btn @click="$emit('delete', task.id)" variant="outlined" color="red" text="Удалить" />
    </td>
    </tr>
  </template>
  
  <script>
    export default {
      //Это объявление пропса task, который должен быть объектом и обязательным для передачи в компонент.
      // Компонент ожидает, что родительский компонент передаст объект task, содержащий данные задачи (например, описание, статус выполнения и дату создания).
      name: "taskItem",
      props: {
        task: {
          type: Object,
          required: true,
        },
        idex: {
          type: [String],
          required: true
        }
      },
      methods: {
      // Метод для форматирования даты
      formatDate(date) {
        const options = { year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric' };
        return new Date(date).toLocaleString("ru-RU", options);
      }
    }
    };
  </script>