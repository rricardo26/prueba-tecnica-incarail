<script setup lang="ts">
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
</script>

<template>
    <v-snackbar v-model="notify.visible" :color="notify.color" :timeout="4500" location="top right">
        {{ notify.message }}
        <template #actions>
            <v-btn variant="text" @click="notify.visible = false">Cerrar</v-btn>
        </template>
    </v-snackbar>
</template>