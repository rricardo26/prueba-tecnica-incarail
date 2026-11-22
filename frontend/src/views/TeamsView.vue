<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { api } from '@/services/api';
import PageHeader from '@/components/common/PageHeader.vue';
import EmptyStateCard from '@/components/common/EmptyStateCard.vue';
import type { Team } from '@/types';

const teams = ref<Team[]>([]);
const search = ref('');
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);

async function fetchTeams() {
    loading.value = true;
    try {
        const { data } = await api.get<{ data: Team[]; meta?: { last_page?: number } }>('/team', {
            params: {
                page: page.value,
                search: search.value || undefined,
            },
        });
        teams.value = data.data;
        totalPages.value = data.meta?.last_page ?? 1;
    } finally {
        loading.value = false;
    }
}

watch(search, () => {
    if (page.value !== 1) {
        page.value = 1;
    } else {
        fetchTeams();
    }
});
watch(page, fetchTeams);
onMounted(fetchTeams);
</script>

<template>
    <div>
        <PageHeader title="Equipos Guardados" subtitle="Clubes almacenados localmente tras importar competiciones.">
            <template #actions>
                <v-text-field v-model="search" density="compact" variant="outlined" placeholder="Buscar equipo..."
                    prepend-inner-icon="mdi-magnify" hide-details style="min-width: 240px" />
            </template>
        </PageHeader>

        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

        <EmptyStateCard v-else-if="teams.length === 0" title="Aún no hay equipos guardados"
            description="Importa una liga desde la sección de Competiciones para poblar los equipos.">
            <template #action>
                <v-btn color="primary" :to="{ name: 'competitions' }">Ir a Ligas</v-btn>
            </template>
        </EmptyStateCard>

        <v-row v-else>
            <v-col v-for="team in teams" :key="team.id" cols="12" sm="6" md="4" lg="3">
                <v-card :to="{ name: 'team-detail', params: { id: team.id } }" hover class="h-100">
                    <v-card-item>
                        <template #prepend>
                            <v-avatar size="42" rounded="0">
                                <v-img v-if="team.crest_url" :src="team.crest_url" />
                                <v-icon v-else icon="mdi-shield" />
                            </v-avatar>
                        </template>
                        <v-card-title class="text-body-1 font-weight-bold">{{ team.name }}</v-card-title>
                        <v-card-subtitle>{{ team.tla || '---' }} · {{ team.players_count ?? 0 }}
                            jugadores</v-card-subtitle>
                    </v-card-item>
                </v-card>
            </v-col>
        </v-row>

        <div v-if="!loading && teams.length > 0 && totalPages > 1" class="d-flex justify-center pa-4">
            <v-pagination v-model="page" :length="totalPages" total-visible="5" />
        </div>
    </div>
</template>