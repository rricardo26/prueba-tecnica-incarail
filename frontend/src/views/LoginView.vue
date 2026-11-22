<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import GlobalSnackbar from '@/components/common/GlobalSnackbar.vue';

const router = useRouter();
const auth = useAuthStore();

const email = ref('');
const password = ref('');
const loading = ref(false);
const errorMessage = ref('');

function fillCredentials(role: 'admin' | 'viewer') {
    email.value = role === 'admin' ? 'admin@incarail.pe' : 'viewer@incarail.pe';
    password.value = 'Password123!';
}

async function submit() {
    loading.value = true;
    errorMessage.value = '';
    try {
        await auth.login(email.value, password.value);
        router.push({ name: 'competitions' });
    } catch (err: any) {
        errorMessage.value = err.response?.data?.error?.message || 'Credenciales inválidas.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <v-app>
        <v-main class="bg-grey-lighten-4 d-flex align-center">
            <v-container>
                <v-row justify="center">
                    <v-col cols="12" sm="8" md="5" lg="4">
                        <v-card class="pa-6" elevation="2">
                            <div class="text-center mb-6">
                                <v-icon icon="mdi-soccer" size="42" color="primary" class="mb-2" />
                                <h1 class="text-h5 font-weight-bold">Inca Rail Football</h1>
                                <p class="text-body-2 text-medium-emphasis">Inicia sesión para continuar</p>
                            </div>

                            <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-4" density="compact">
                                {{ errorMessage }}
                            </v-alert>

                            <v-form @submit.prevent="submit">
                                <v-text-field v-model="email" label="Correo electrónico" type="email"
                                    prepend-inner-icon="mdi-email-outline" variant="outlined" required class="mb-2" />
                                <v-text-field v-model="password" label="Contraseña" type="password"
                                    prepend-inner-icon="mdi-lock-outline" variant="outlined" required class="mb-4" />

                                <v-btn type="submit" color="primary" block size="large" :loading="loading">
                                    Ingresar
                                </v-btn>
                            </v-form>

                            <v-divider class="my-5" />
                            <p class="text-caption text-medium-emphasis text-center mb-2">Accesos de prueba:</p>
                            <div class="d-flex justify-center ga-2">
                                <v-btn size="x-small" variant="tonal" color="primary" @click="fillCredentials('admin')">
                                    Admin
                                </v-btn>
                                <v-btn size="x-small" variant="tonal" color="secondary"
                                    @click="fillCredentials('viewer')">
                                    Viewer (Solo lectura)
                                </v-btn>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>
        <GlobalSnackbar />
    </v-app>
</template>