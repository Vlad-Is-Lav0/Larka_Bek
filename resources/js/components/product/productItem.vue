<template>
    <tr @click="$emit('edit', product.id)" style="cursor: pointer;" class="hover-row">
          <!-- Отображение номер товара -->
      <td>{{ index }}</td>
        <!-- Отображение код товара -->
      <td>{{ product.code }}</td>
        <!-- Отображение названия товара -->
      <td>{{ product.name }}</td>
        <!-- Отображение стомость -->
      <td>{{ product.salePrices ? product.salePrices[0]?.value / 100 : "—" }}</td>
        <!-- Кнопки для редактирования и удаления задачи -->
    </tr>
  </template>
  
  <script>
    export default {
      //Это объявление пропса task, который должен быть объектом и обязательным для передачи в компонент.
      // Компонент ожидает, что родительский компонент передаст объект task, содержащий данные задачи (например, описание, статус выполнения и дату создания).
      name: "productItem",
      props: {
        product: {
          type: Object,
          required: true,
        },
        index: {
          type: Number,
          required: true,
        },
      },
      methods: {
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