import axios from 'axios';
import jquery from 'jquery';

declare global {
    interface Window {
        axios: typeof axios;
        $: typeof jquery;
        jQuery: typeof jquery;
    }
}

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// select2 (used by vue3-select2-component / SelectInput.vue) expects jQuery as a global.
window.$ = jquery;
window.jQuery = jquery;
