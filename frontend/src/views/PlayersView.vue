<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { api } from '@/services/api';
import PageHeader from '@/components/common/PageHeader.vue';
import EmptyStateCard from '@/components/common/EmptyStateCard.vue';
import type { Player } from '@/types';

const players = ref<Player[]>([]);
const search = ref('');
const position = ref<string | null>(null);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);

const positions = ['Goalkeeper', 'Defence', 'Midfield', 'Offence'];

async function fetchPlayers() {
    loading.value = true;
    try {
        const { data } = await api.get('/players', {
            params: {
                page: page.value,
                search: search.value || undefined,
                position: position.value || undefined,
            },
        });
        players.value = data.data;
        totalPages.value = data.meta?.last_page ?? 1;
    } finally {
        loading.value = false;
    }
}

watch([search, position], () => {
    page.value = 1;
    fetchPlayers();
});
watch(page, fetchPlayers);
onMounted(fetchPlayers);
</script>

<template>
    <div>
        <PageHeader title="Jugadores Registrados" subtitle="Listado completo de futbolistas guardados en el sistema.">
            <template #actions>
                <v-text-field v-model="search" density="compact" variant="outlined" placeholder="Buscar jugador..."
                    prepend-inner-icon="mdi-magnify" hide-details style="min-width: 210px" />
                <v-select v-model="position" :items="positions" label="Posición" density="compact" variant="outlined"
                    clearable hide-details style="min-width: 170px" />
            </template>
        </PageHeader>

        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

        <EmptyStateCard v-else-if="players.length === 0" title="Sin jugadores encontrados"
            description="No hay jugadores registrados con esos filtros o aún no has importado una competición." />

        <v-card v-else>
            <v-table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jugador</th>
                        <th>Posición</th>
                        <th>Equipo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="player in players" :key="player.id">
                        <td>{{ player.shirt_number ?? '-' }}</td>
                        <td class="font-weight-medium">{{ player.name }}</td>
                        <td>
                            <v-chip size="x-small" variant="tonal">{{ player.position || 'N/A' }}</v-chip>
                        </td>
                        <td>{{ player.team?.name }}</td>
                    </tr>
                </tbody>
            </v-table>

            <div class="pa-4 d-flex justify-center">
                <v-pagination v-model="page" :length="totalPages" total-visible="5" />
            </div>
        </v-card>
    </div>
</template>