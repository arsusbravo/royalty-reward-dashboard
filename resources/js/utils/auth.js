import { reactive } from 'vue';
import { api } from './fetch.js';

export const authState = reactive({
    user: null,
    loading: true,
});

export async function fetchUser() {
    try {
        const data = await api.get('/user');
        authState.user = data.user;
    } catch {
        authState.user = null;
    } finally {
        authState.loading = false;
    }
}

export async function login(email, password, remember = false) {
    const data = await api.post('/login', { email, password, remember });
    authState.user = data.user;
    return data;
}

export async function logout() {
    try {
        await api.post('/logout');
    } finally {
        authState.user = null;
    }
}
