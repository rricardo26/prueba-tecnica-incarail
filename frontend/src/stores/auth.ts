import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { api } from '@/services/api';
import type { LoginResponse, User } from '@/types';

export const useAuthStore = defineStore('auth', () => {
    const token = ref<string | null>(localStorage.getItem('auth_token'));
    const user = ref<User | null>(JSON.parse(localStorage.getItem('auth_user') || 'null'));

    const isAuthenticated = computed(() => Boolean(token.value));

    function can(permission: string): boolean {
        if (!user.value) return false;
        if (user.value.roles.includes('admin')) return true;
        return user.value.permissions.includes(permission);
    }

    function hasRole(role: string): boolean {
        return user.value?.roles.includes(role) ?? false;
    }

    async function login(email: string, password: string) {
        const { data } = await api.post<LoginResponse>('/login', { email, password });
        setSession(data.access_token, data.user);
    }

    async function fetchMe() {
        if (!token.value) return;
        const { data } = await api.get<{ user: User }>('/me');
        setSession(token.value, data.user);
    }

    async function logout() {
        try {
            await api.post('/logout');
        } finally {
            clearSession();
        }
    }

    function setSession(newToken: string, newUser: User) {
        token.value = newToken;
        user.value = newUser;
        localStorage.setItem('auth_token', newToken);
        localStorage.setItem('auth_user', JSON.stringify(newUser));
    }

    function clearSession() {
        token.value = null;
        user.value = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
    }

    return {
        token,
        user,
        isAuthenticated,
        can,
        hasRole,
        login,
        fetchMe,
        logout,
        clearSession,
    };
});