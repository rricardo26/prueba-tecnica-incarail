import axios from 'axios';
import router from '@/router';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';

export const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const auth = useAuthStore();
    if (auth.token) {
        config.headers.Authorization = `Bearer ${auth.token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const serverMessage = error.response?.data?.error?.message;
        const notify = useNotificationStore();
        const auth = useAuthStore();

        if (status === 401) {
            auth.clearSession();
            notify.show('Tu sesión ha vencido o no es válida. Inicia sesión nuevamente.', 'warning');
            if (router.currentRoute.value.name !== 'login') {
                router.push({ name: 'login' });
            }
        } else if (status === 403) {
            notify.show(serverMessage || 'No tienes permisos para realizar esta acción.', 'error');
        } else if (status === 429) {
            notify.show(
                'Se alcanzó el límite de 10 peticiones por minuto de football-data.org. Intenta en unos segundos.',
                'warning'
            );
        } else if (status >= 500) {
            notify.show(
                serverMessage || 'El servicio externo no está disponible en este momento.',
                'error'
            );
        }

        return Promise.reject(error);
    }
);