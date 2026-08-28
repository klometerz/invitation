<script>
    let {
        open = $bindable(false),
        title = "Konfirmasi",
        message = "Apakah kamu yakin?",
        confirmLabel = "Konfirmasi",
        cancelLabel = "Batal",
        variant = "default", // "default" | "danger"
        onConfirm = () => {},
        onCancel = () => {},
    } = $props();

    function handleConfirm() {
        open = false;
        onConfirm();
    }

    function handleCancel() {
        open = false;
        onCancel();
    }

    function handleKeydown(e) {
        if (e.key === "Escape") handleCancel();
    }
</script>

{#if open}
    <!-- svelte-ignore a11y-no-noninteractive-element-interactions -->
    <div
        class="modal-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        onkeydown={handleKeydown}
    >
        <div
            class="modal-overlay"
            role="button"
            tabindex="0"
            aria-label="Close modal"
            onclick={handleCancel}
            onkeydown={(e) => e.key === "Enter" && handleCancel()}
        ></div>
        <div class="modal-card">
            <div class="modal-icon" class:icon-danger={variant === "danger"}>
                {#if variant === "danger"}
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                {:else}
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                {/if}
            </div>

            <h2 class="modal-title" id="modal-title">{title}</h2>
            <p class="modal-message">{message}</p>

            <div class="modal-actions">
                <button class="btn-cancel" onclick={handleCancel}>
                    {cancelLabel}
                </button>
                <button
                    class="btn-confirm"
                    class:btn-danger={variant === "danger"}
                    onclick={handleConfirm}
                >
                    {confirmLabel}
                </button>
            </div>
        </div>
    </div>
{/if}

<style>
    .modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(2px);
        cursor: pointer;
    }

    .modal-card {
        position: relative;
        background: white;
        border-radius: 16px;
        padding: 32px 28px 24px;
        max-width: 400px;
        width: 100%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 4px 16px rgba(0,0,0,0.08);
        animation: modalIn 0.2s ease;
    }

    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: #EFF6FF;
        color: #2563EB;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }

    .modal-icon.icon-danger {
        background: #FEF2F2;
        color: #EF4444;
    }

    .modal-title {
        font-size: 17px;
        font-weight: 600;
        color: #111827;
        margin: 0 0 8px;
        font-family: 'Inter', sans-serif;
    }

    .modal-message {
        font-size: 14px;
        color: #6B7280;
        margin: 0 0 24px;
        line-height: 1.5;
        font-family: 'Inter', sans-serif;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .btn-cancel, .btn-confirm {
        padding: 9px 20px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        font-family: 'Inter', sans-serif;
        border: none;
    }

    .btn-cancel {
        background: #F3F4F6;
        color: #374151;
        border: 1px solid #E5E7EB;
    }

    .btn-cancel:hover {
        background: #E5E7EB;
    }

    .btn-confirm {
        background: #2563EB;
        color: white;
    }

    .btn-confirm:hover {
        background: #1D4ED8;
    }

    .btn-confirm.btn-danger {
        background: #EF4444;
    }

    .btn-confirm.btn-danger:hover {
        background: #DC2626;
    }
</style>
