import { createRouter, createWebHistory } from 'vue-router';
import taskList from './components/TaskList.vue';
import taskForm from "./components/taskForm.vue";

const routes = [
        { path: '/tasks', component: taskList },
        { path: '/tasks/add', component: taskForm },
        { path: '/tasks/edit/:id', component: taskForm },
];

const router = createRouter({
    history: createWebHistory(), // создание веб истрии
    routes
});

export default router;