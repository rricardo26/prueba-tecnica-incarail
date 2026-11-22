<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { api } from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import PageHeader from '@/components/common/PageHeader.vue';
import EmptyStateCard from '@/components/common/EmptyStateCard.vue';
import type { Competition } from '@/types';

const auth = useAuthStore();
const notify = useNotificationStore();

const competitions = ref<Competition[]>([]);
const search = ref('');
const loading = ref(false);
const syncingId = ref<number | null>(null);

const SYNC_POLL_INTERVAL_MS = 2000;
const SYNC_TIMEOUT_MS = 45000;

const filteredCompetitions = computed(() =>
    competitions.value.filter(
        (c) =>
            c.name.toLowerCase().includes(search.value.toLowerCase()) ||
            (c.area_name ?? '').toLowerCase().includes(search.value.toLowerCase())
    )
);

async function loadCompetitions() {
    loading.value = true;
    try {
        const { data } = await api.get<{ data: Competition[] }>('/competitions');
        competitions.value = data.data;
    } finally {
        loading.value = false;
    }
}

function delay(ms: number) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function waitForCompetitionSync(competitionId: number, previousSyncedAt: string | null) {
    const deadline = Date.now() + SYNC_TIMEOUT_MS;

    while (Date.now() < deadline) {
        await delay(SYNC_POLL_INTERVAL_MS);

        const { data } = await api.get<{ data: Competition[] }>('/competitions');
        competitions.value = data.data;

        const updated = competitions.value.find((c) => c.id === competitionId);
        if (updated?.last_synced_at && updated.last_synced_at !== previousSyncedAt) {
            notify.show(`Liga sincronizada (${updated.teams_count ?? 0} equipos).`, 'success');
            return;
        }
    }

    notify.show('Actualiza la lista en unos segundos.', 'warning');
}

async function importCompetition(competition: Competition) {
    syncingId.value = competition.id;
    const previousSyncedAt = competition.last_synced_at;

    try {
        await api.post(`/competitions/${competition.external_id}/sync-async`);
        notify.show('Sincronización iniciada.', 'info');
        await waitForCompetitionSync(competition.id, previousSyncedAt);
    } finally {
        syncingId.value = null;
    }
}

onMounted(loadCompetitions);
</script>

<template>
    <div>
        <PageHeader title="Competiciones y Ligas"
            subtitle="Consulta las ligas disponibles e importa sus equipos y plantillas a la base local.">
            <template #actions>
                <v-text-field v-model="search" density="compact" variant="outlined" placeholder="Buscar liga o país..."
                    prepend-inner-icon="mdi-magnify" hide-details style="min-width: 240px" />
            </template>
        </PageHeader>

        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

        <EmptyStateCard v-else-if="filteredCompetitions.length === 0" title="Sin competiciones disponibles"
            description="No se encontraron ligas con los criterios actuales o la API externa aún no responde." />

        <v-row v-else>
            <v-col v-for="comp in filteredCompetitions" :key="comp.id" cols="12" sm="6" md="4">
                <v-card class="h-100 d-flex flex-column justify-space-between">
                    <v-card-item>
                        <template #prepend>
                            <v-avatar size="40" rounded="0">
                                <v-img v-if="comp.emblem_url" :src="comp.emblem_url" />
                                <v-icon v-else icon="mdi-trophy" />
                            </v-avatar>
                        </template>
                        <v-card-title>{{ comp.name }}</v-card-title>
                        <v-card-subtitle>{{ comp.area_name || 'Internacional' }} · {{ comp.code }}</v-card-subtitle>
                    </v-card-item>

                    <v-card-text>
                        <v-chip size="small" :color="comp.last_synced_at ? 'success' : 'default'" variant="tonal">
                            {{ comp.last_synced_at ? `Importada (${comp.teams_count ?? 0} equipos)` : 'Pendiente de importar' }}
                        </v-chip>
                    </v-card-text>

                    <v-card-actions class="pa-4 pt-0">
                        <v-btn v-if="auth.can('competitions.import')" color="primary" variant="tonal" block
                            prepend-icon="mdi-cloud-download-outline" :loading="syncingId === comp.id"
                            @click="importCompetition(comp)">
                            {{ comp.last_synced_at ? 'Consultar / Actualizar' : 'Importar Liga' }}
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>