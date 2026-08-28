// Toast store — import { toast } from './toast.js' in any page
import { writable } from "svelte/store";

export const toasts = writable([]);

let idCounter = 0;

export function toast(message, type = "success", duration = 3500) {
    const id = ++idCounter;
    toasts.update((t) => [...t, { id, message, type }]);
    setTimeout(() => {
        toasts.update((t) => t.filter((x) => x.id !== id));
    }, duration);
}

export function dismissToast(id) {
    toasts.update((t) => t.filter((x) => x.id !== id));
}
