<script setup lang="ts">
withDefaults(
    defineProps<{
        icon?: string;
        title: string;
        description: string;
    }>(),
    { icon: 'mdi-database-search-outline' }
);
</script>

<template>
    <v-card variant="outlined" class="text-center pa-8 my-4">
        <v-icon :icon="icon" size="48" color="medium-emphasis" class="mb-3" />
        <h3 class="text-h6 mb-1">{{ title }}</h3>
        <p class="text-body-2 text-medium-emphasis mb-4">{{ description }}</p>
        <slot name="action" />
    </v-card>
</template>