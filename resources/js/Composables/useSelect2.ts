import $ from 'jquery';
// select2 is a classic jQuery UMD plugin; bundling it as an ES module confuses Vite/Rolldown's
// CJS interop (it ends up looking for jQuery on the wrong export shape). Loading its built file
// as a genuine <script> tag instead makes it attach to the real global `window.jQuery`.
import select2ScriptUrl from 'select2/dist/js/select2.full.min.js?url';
import 'select2/dist/css/select2.min.css';

window.jQuery = $;
window.$ = $;

let loadPromise: Promise<void> | null = null;

export function useSelect2() {
    if (($.fn as { select2?: unknown }).select2) {
        return Promise.resolve();
    }

    if (!loadPromise) {
        loadPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = select2ScriptUrl;
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load select2'));
            document.head.appendChild(script);
        });
    }

    return loadPromise;
}
