import { createRouter, createWebHistory } from 'vue-router';
import taskList from "./components/task/taskList.vue";
import taskForm from "./components/task/taskForm.vue";
import productList from './components/product/productList.vue';
import productForm from './components/product/productForm.vue';

const routes = [
        { path: '/tasks',               component: taskList },
        { path: '/tasks/add',           component: taskForm },
        { path: '/tasks/edit/:id',      component: taskForm },
        { path: '/products',            component: productList },
        { path: '/products/add',        component: productForm },
        { path: "/products/:id/edit", name: "editProduct", component: productForm, },
];

const router = createRouter({
    history: createWebHistory(), // создание веб истрии
    routes
});

export default router;