import './bootstrap';
import { createInertiaApp } from '@inertiajs/svelte'
import { mount } from 'svelte'

// Import CSS di sini atau di Blade, tapi kita coba di sini dulu
import '../css/app.css';

createInertiaApp({
    // P2-T6 (K10): lazy resolution. Each page (including every admin screen)
    // becomes its own chunk, so guests never download admin code.
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.svelte')
        const page = pages[`./Pages/${name}.svelte`]

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`)
        }

        return page()
    },
    setup({ el, App, props }) {
        mount(App, { target: el, props })
    },
})
