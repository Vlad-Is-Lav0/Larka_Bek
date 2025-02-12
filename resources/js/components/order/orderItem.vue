<template>
  <tr @click="$emit('edit', order.id)" style="cursor: pointer;" class="hover-row">
    <td>{{ index }}</td>
    <td>{{ order.agentName || "Не указан" }}</td>
    <td>{{ formatDate(order.moment) }}</td>
    <td>{{ order.stateName || "Не указан" }}</td>
    <td>{{ (order.sum / 100).toFixed(2) }} ₽</td>
  </tr>
</template>

<script>
export default {
  name: "orderItem",
  props: {
    order: {
      type: Object,
      required: true,
    },
    index: {
      type: Number,
      required: true,
    },
  },
  methods: {
    formatDate(dateString) {
      return dateString
        ? new Date(dateString).toLocaleDateString("ru-RU", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
          })
        : "Не указано";
    },
  },
};
</script>

<style>
/* Эффект при наведении */
.hover-row:hover {
  background-color: #f0f0f0;
  transition: background-color 0.2s ease-in-out;
}
</style>
