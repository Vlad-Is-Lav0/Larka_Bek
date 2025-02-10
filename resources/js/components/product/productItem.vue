<template>
    <tr @click="$emit('edit', product.id)" style="cursor: pointer;">
          <!-- Отображение номер товара -->
      <td>{{ index }}</td>
        <!-- Отображение код товара -->
      <td>{{ product.code }}</td>
        <!-- Отображение названия товара -->
      <td>{{ product.name }}</td>
        <!-- Отображение стомость -->
      <td>{{ product.salePrices ? product.salePrices[0]?.value / 100 : "—" }}</td>
        <!-- Кнопки для редактирования и удаления задачи -->
      <td>
        <v-btn  variant="outlined" color="green">Редактировать</v-btn>
       
      </td>
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