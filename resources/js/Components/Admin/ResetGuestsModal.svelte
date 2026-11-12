<script>
    // P1-T6: replacing the old unscoped truncate. Deleting every guest of the
    // current invitation requires typing the invitation slug, so it cannot
    // happen by a careless double click.
    let {
        open = $bindable(false),
        slug = "",
        count = 0,
        onConfirm = () => {},
    } = $props();

    let typed = $state("");
    let error = $state("");

    const matches = $derived(typed.trim() === slug);

    function close() {
        open = false;
        typed = "";
        error = "";
    }

    function confirm() {
        if (!matches) {
            error = "Slug tidak cocok.";
            return;
        }
        open = false;
        const value = typed.trim();
        typed = "";
        error = "";
        onConfirm(value);
    }
</script>

{#if open}
    <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
    <div class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="reset-title">
        <div
            class="modal-overlay"
            role="button"
            tabindex="0"
            aria-label="Tutup"
            onclick={close}
            onkeydown={(e) => e.key === "Enter" && close()}
        ></div>

        <div class="modal-card">
            <div class="modal-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>

            <h2 class="modal-title" id="reset-title">Hapus semua tamu acara ini?</h2>
            <p class="modal-message">
                {count} tamu pada acara ini akan dihapus permanen. Undangan lain
                tidak terpengaruh. Tindakan ini tidak bisa dibatalkan.
            </p>

            <label class="modal-label" for="reset-slug-input">
                Ketik slug undangan <code>{slug}</code> untuk konfirmasi
            </label>
            <input
                id="reset-slug-input"
                class="modal-input"
                type="text"
                autocomplete="off"
                bind:value={typed}
                placeholder={slug}
                onkeydown={(e) => e.key === "Enter" && confirm()}
            />
            {#if error}<p class="modal-error">{error}</p>{/if}

            <div class="modal-actions">
                <button class="btn-cancel" onclick={close}>Batal</button>
                <button
                    class="btn-confirm btn-danger"
                    disabled={!matches}
                    onclick={confirm}
                >
                    Hapus semua tamu
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
        padding: 28px;
        max-width: 420px;
        width: 100%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 4px 16px rgba(0, 0, 0, 0.08);
        font-family: "Inter", sans-serif;
    }

    .modal-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: #fef2f2;
        color: #ef4444;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }

    .modal-title {
        font-size: 17px;
        font-weight: 600;
        color: #111827;
        margin: 0 0 8px;
    }

    .modal-message {
        font-size: 13.5px;
        color: #6b7280;
        margin: 0 0 18px;
        line-height: 1.5;
    }

    .modal-label {
        display: block;
        text-align: left;
        font-size: 12.5px;
        color: #374151;
        margin-bottom: 6px;
    }

    .modal-label code {
        background: #f3f4f6;
        padding: 1px 5px;
        border-radius: 4px;
    }

    .modal-input {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        font-family: inherit;
        font-size: 13.5px;
        box-sizing: border-box;
    }

    .modal-input:focus {
        outline: 2px solid #eff6ff;
        border-color: #2563eb;
    }

    .modal-error {
        color: #ef4444;
        font-size: 12.5px;
        margin: 6px 0 0;
        text-align: left;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }

    .btn-cancel,
    .btn-confirm {
        padding: 9px 20px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 500;
        cursor: pointer;
        font-family: inherit;
        border: none;
    }

    .btn-cancel {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
    }

    .btn-confirm.btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-confirm.btn-danger:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
</style>
