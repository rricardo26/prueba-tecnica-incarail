<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useDisplay } from 'vuetify';
import { useAuthStore } from '@/stores/auth';
import GlobalSnackbar from './GlobalSnackbar.vue';

const { mdAndUp } = useDisplay();
const drawer = ref(mdAndUp.value);
const router = useRouter();
const auth = useAuthStore();

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <v-app>
        <v-app-bar color="primary" density="comfortable">
            <v-app-bar-nav-icon @click="drawer = !drawer" />
            <v-app-bar-title class="font-weight-bold">
                Inca Rail · Football Hub
            </v-app-bar-title>

            <template #append>
                <v-chip size="small" variant="tonal" class="mr-3 d-none d-sm-flex">
                    {{ auth.user?.name }} ({{ auth.user?.roles?.[0] }})
                </v-chip>
                <v-btn icon="mdi-logout" title="Cerrar sesión" @click="handleLogout" />
            </template>
        </v-app-bar>

        <v-navigation-drawer v-model="drawer">
            <v-list nav density="comfortable">
                <v-list-item v-if="auth.can('competitions.read')" prepend-icon="mdi-trophy-outline" title="Ligas"
                    :to="{ name: 'competitions' }" />
                <v-list-item v-if="auth.can('teams.read')" prepend-icon="mdi-shield-outline" title="Equipos"
                    :to="{ name: 'teams' }" />
                <v-list-item v-if="auth.can('players.read')" prepend-icon="mdi-account-group-outline" title="Jugadores"
                    :to="{ name: 'players' }" />

                <v-divider v-if="auth.can('users.manage')" class="my-2" />

                <v-list-item v-if="auth.can('users.manage')" prepend-icon="mdi-shield-account-outline"
                    title="Usuarios y Roles" :to="{ name: 'admin-users' }" />
            </v-list>
        </v-navigation-drawer>

        <v-main class="bg-grey-lighten-4">
            <v-container fluid class="pa-4 pa-md-6">
                <router-view />
            </v-container>
        </v-main>

        <GlobalSnackbar />
    </v-app>
</template>