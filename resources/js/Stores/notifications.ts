import { defineStore } from 'pinia';

export interface FlashNotification {
    id: number;
    type: 'success' | 'error';
    message: string;
}

let nextId = 1;

export const useNotificationStore = defineStore('notifications', {
    state: () => ({
        items: [] as FlashNotification[],
    }),
    actions: {
        push(type: FlashNotification['type'], message: string) {
            const id = nextId++;
            this.items.push({ id, type, message });
            setTimeout(() => this.dismiss(id), 5000);
        },
        dismiss(id: number) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    },
});
