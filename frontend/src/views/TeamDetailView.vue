<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '@/services/api';
import PageHeader from '@/components/common/PageHeader.vue';
import type { Team } from '@/types';

const route = useRoute();
const team = ref<Team | null>(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.get<{ data: Team }>(`/team/${route.params.id}`);
        team.value = data.data;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <v-btn variant="text" prepend-icon="mdi-arrow-left" :to="{ name: 'teams' }" class="mb-3">
            Volver a Equipos
        </v-btn>

        <v-progress-linear v-if="loading" indeterminate color="primary" />

        <template v-else-if="team">
            <PageHeader :title="team.name" :subtitle="team.venue || 'Estadio no registrado'" />

            <v-card>
                <v-card-title>Plantilla de Jugadores ({{ team.players?.length ?? 0 }})</v-card-title>
                <v-table>
                    <thead>
                        <tr>
                            <th>Camiseta</th>
                            <th>Nombre</th>
                            <th>Posición</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="player in team.players" :key="player.id">
                            <td>{{ player.shirt_number ?? '-' }}</td>
                            <td class="font-weight-medium">{{ player.name }}</td>
                            <td>{{ player.position || 'N/A' }}</td>
                        </tr>
                    </tbody>
                </v-table>
            </v-card>
        </template>
    </div>
</template>