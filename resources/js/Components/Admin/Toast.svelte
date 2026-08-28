<script>
    import { toasts, dismissToast } from "./toast.js";
    import { fly } from "svelte/transition";

    const icons = {
        success: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
        error: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
        warning: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
        info: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
    };
</script>

<div class="toast-container" aria-live="polite" aria-atomic="false">
    {#each $toasts as t (t.id)}
        <div
            class="toast toast-{t.type}"
            role="alert"
            transition:fly={{ x: 60, duration: 250 }}
        >
            <span class="toast-icon">{@html icons[t.type] ?? icons.info}</span>
            <span class="toast-msg">{t.message}</span>
            <button
                class="toast-close"
                onclick={() => dismissToast(t.id)}
                aria-label="Dismiss"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    {/each}
</div>

<style>
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 8px;
        pointer-events: none;
        max-width: 360px;
        width: calc(100vw - 40px);
    }

    .toast {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 500;
        border: 1px solid transparent;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.06);
        pointer-events: all;
        font-family: 'Inter', -apple-system, sans-serif;
        background: white;
    }

    .toast-success {
        border-color: #A7F3D0;
        background: #F0FDF9;
        color: #065F46;
    }

    .toast-success .toast-icon { color: #10B981; }

    .toast-error {
        border-color: #FECACA;
        background: #FFF1F1;
        color: #7F1D1D;
    }

    .toast-error .toast-icon { color: #EF4444; }

    .toast-warning {
        border-color: #FDE68A;
        background: #FFFBEB;
        color: #78350F;
    }

    .toast-warning .toast-icon { color: #F59E0B; }

    .toast-info {
        border-color: #BFDBFE;
        background: #EFF6FF;
        color: #1E3A5F;
    }

    .toast-info .toast-icon { color: #2563EB; }

    .toast-icon {
        flex-shrink: 0;
        display: flex;
        align-items: center;
    }

    .toast-msg {
        flex: 1;
        line-height: 1.4;
    }

    .toast-close {
        background: none;
        border: none;
        cursor: pointer;
        padding: 2px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        color: currentColor;
        opacity: 0.5;
        flex-shrink: 0;
    }

    .toast-close:hover { opacity: 1; }
</style>
