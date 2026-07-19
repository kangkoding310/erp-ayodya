import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const isBooting = ref(true);
const isNavigating = ref(false);

const NAVIGATION_SHOW_DELAY_MS = 150;
const MIN_BOOT_VISIBLE_MS = 500;

let navigationTimer: ReturnType<typeof setTimeout> | null = null;
let listenersBound = false;

function bindRouterListeners() {
    if (listenersBound) return;
    listenersBound = true;

    router.on('start', () => {
        navigationTimer = setTimeout(() => {
            isNavigating.value = true;
        }, NAVIGATION_SHOW_DELAY_MS);
    });

    router.on('finish', () => {
        if (navigationTimer) {
            clearTimeout(navigationTimer);
            navigationTimer = null;
        }
        isNavigating.value = false;
    });
}

/**
 * Drives the app-wide splash overlay: shown briefly on initial boot, and again
 * (after a short delay, to avoid flicker on fast requests) during Inertia
 * navigations that take a noticeable amount of time.
 */
export function useSplashScreen() {
    bindRouterListeners();

    const finishBooting = () => {
        setTimeout(() => {
            isBooting.value = false;
        }, MIN_BOOT_VISIBLE_MS);
    };

    return { isBooting, isNavigating, finishBooting };
}
