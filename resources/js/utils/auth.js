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
    // Don't update authState here — LoginPage does a full page reload immediately after,
    // so fetchUser() will populate authState fresh on the new load.
    // Setting authState.user here would cause a brief flash of the authenticated shell.
    return data;
}

export async function logout() {
    await api.post('/logout');
    authState.user = null;
}
