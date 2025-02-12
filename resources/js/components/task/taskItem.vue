<template>
    <tr @click="$emit('edit', task.id)" style="cursor: pointer;" class="hover-row">
       <!-- Отображение ID задачи -->
    <td>{{ idex }}</td>

      <!-- Отображение описания задачи -->
    <td>{{ task.description}}</td>

      <!-- Отображение статуса выполнения задачи -->
    <td>{{ task.is_completed ? "Выполнено" : "Не выполнено" }}</td>

      <!-- Отображение даты создания задачи -->
    <td>{{ formatDate(task.created_at) }}</td> <!-- Форматируем дату --> 
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

  <style>
  /* Добавляем эффект при наведении */
  .hover-row:hover {
    background-color: #f0f0f0; /* Светло-серый фон */
    transition: background-color 0.2s ease-in-out;
  }
  </style>