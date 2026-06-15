import { createApp } from 'vue';
import router from './router/index.js';
import AppLayout from './components/AppLayout.vue';

const app = createApp(AppLayout);
app.use(router);
app.mount('#app');
