<template>
  <tr>
    <td>{{ task.description }}</td>
    <td>{{ task.is_completed ? "Выполнено" : "Не выполнено" }}</td>
    <td>{{ task.created_at }}</td>
    <td>
      <v-btn @click="$emit('edit', task.id)">Редактировать</v-btn>
      <v-btn color="red" @click="$emit('delete', task.id)">Удалить</v-btn>
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
  },
};
</script>