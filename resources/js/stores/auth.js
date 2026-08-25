import { defineStore } from 'pinia';
import api from '../utils/axios.js';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        token: localStorage.getItem('token') || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.user?.role === 'admin',
    },
    actions: {
        async login(credentials) {
            const response = await api.post('/api/auth/login', credentials);
            const res = response.data;
            this.token = res.token || res.access_token || res.data?.token;
            this.user = res.user || res.data?.user;
            if (!this.token) throw new Error('Token not found');
            localStorage.setItem('token', this.token);
            localStorage.setItem('user', JSON.stringify(this.user));
            return true;
        },
        async logout() {
            try { await api.post('/api/auth/logout'); } catch(e) {}
            this.token = null;
            this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        }
    }
});
