import { defineStore } from 'pinia';
import { ref } from 'vue';

export type NotificationType = 'success' | 'error' | 'warning' | 'info';

export const useNotificationStore = defineStore('notification', () => {
    const visible = ref(false);
    const message = ref('');
    const color = ref<NotificationType>('info');

    function show(text: string, type: NotificationType = 'info') {
        message.value = text;
        color.value = type;
        visible.value = true;
    }

    return { visible, message, color, show };
});