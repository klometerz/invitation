<script>
    import { router } from "@inertiajs/svelte";

    let { 
        open = $bindable(false),
        guest = null, // null = create mode, object = edit mode
        categories = [],
        onSuccess = () => {},
    } = $props();

    let isEditMode = $derived(guest !== null);

    let form = $state({
        name: "",
        city: "",
        category: "",
        quota: 1,
    });

    let isSubmitting = $state(false);
    let errors = $state({});

    // Reset form setiap kali modal dibuka, sync dengan data guest (kalau edit)
    $effect(() => {
        if (open) {
            form = {
                name: guest?.name || "",
                city: guest?.city || "",
                category: guest?.category || "",
                quota: guest?.quota || 1,
            };
            errors = {};
        }
    });

    function closeModal() {
        open = false;
    }

    function handleBackdropClick(e) {
        if (e.target === e.currentTarget) closeModal();
    }

    function incrementQuota() {
        if (form.quota < 20) form.quota++;
    }

    function decrementQuota() {
        if (form.quota > 1) form.quota--;
    }

    function handleSubmit(e) {
        e.preventDefault();
        isSubmitting = true;
        errors = {};

        const payload = {
            name: form.name,
            city: form.city,
            category: form.category,
            quota: form.quota,
        };

        if (isEditMode) {
            router.put(`/admin/guests/${guest.id}`, payload, {
                onSuccess: () => {
                    isSubmitting = false;
                    closeModal();
                    onSuccess();
                },
                onError: (err) => {
                    isSubmitting = false;
                    errors = err;
                },
            });
        } else {
            router.post("/admin/guests", payload, {
                onSuccess: () => {
                    isSubmitting = false;
                    closeModal();
                    onSuccess();
                },
                onError: (err) => {
                    isSubmitting = false;
                    errors = err;
                },
            });
        }
    }
</script>

{#if open}
    <div class="modal-backdrop" onclick={handleBackdropClick}>
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title">
                    {isEditMode ? "Edit Tamu" : "Tambah Tamu Baru"}
                </h2>
                <button class="modal-close" onclick={closeModal}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <form onsubmit={handleSubmit} class="modal-form">
                <div class="field-group">
                    <label for="name">Nama Tamu <span class="required">*</span></label>
                    <input
                        id="name"
                        type="text"
                        bind:value={form.name}
                        placeholder="Nama lengkap tamu"
                        class:input-error={errors.name}
                    />
                    {#if errors.name}<span class="error-text">{errors.name}</span>{/if}
                </div>

                <div class="field-group">
                    <label for="city">Kota / Asal</label>
                    <input
                        id="city"
                        type="text"
                        bind:value={form.city}
                        placeholder="Jakarta, Cirebon, dll"
                    />
                </div>

                <div class="field-group">
                    <label for="category">Jenis Tamu</label>
                    <input
                        id="category"
                        type="text"
                        bind:value={form.category}
                        placeholder="Tamu Pria, Tamu Wanita, Keluarga, dll"
                        list="category-suggestions"
                    />
                    <datalist id="category-suggestions">
                        {#each categories as cat}
                            <option value={cat}></option>
                        {/each}
                    </datalist>
                    <span class="hint-text">Ketik bebas atau pilih dari yang sudah ada</span>
                </div>

                <div class="field-group">
                    <label>Quota (jumlah tamu yang boleh dibawa)</label>
                    <div class="quota-stepper">
                        <button type="button" class="quota-btn" onclick={decrementQuota}>−</button>
                        <input
                            type="number"
                            bind:value={form.quota}
                            min="1"
                            max="20"
                            class="quota-input"
                        />
                        <button type="button" class="quota-btn" onclick={incrementQuota}>+</button>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick={closeModal}>
                        Batal
                    </button>
                    <button type="submit" class="btn-submit" disabled={isSubmitting}>
                        {isSubmitting ? "Menyimpan..." : (isEditMode ? "Simpan Perubahan" : "Tambah Tamu")}
                    </button>
                </div>
            </form>
        </div>
    </div>
{/if}

<style>
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 200;
        padding: 1rem;
    }

    .modal-box {
        background: var(--card, #FFFFFF);
        border-radius: var(--radius, 12px);
        width: 100%;
        max-width: 440px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border, #E5E7EB);
    }

    .modal-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary, #111827);
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        color: var(--text-muted, #9CA3AF);
        cursor: pointer;
        padding: 4px;
        display: flex;
        border-radius: 6px;
        transition: all 0.15s;
    }

    .modal-close:hover {
        background: var(--bg, #F8FAFC);
        color: var(--text-primary, #111827);
    }

    .modal-form {
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .field-group label {
        font-size: 13px;
        font-weight: 500;
        color: #374151;
    }

    .required {
        color: #EF4444;
    }

    .field-group input[type="text"],
    .field-group input[type="number"] {
        height: 38px;
        padding: 0 12px;
        border: 1px solid var(--border, #E5E7EB);
        border-radius: 8px;
        font-size: 13.5px;
        font-family: inherit;
        color: var(--text-primary, #111827);
    }

    .field-group input:focus {
        outline: none;
        border-color: var(--primary, #2563EB);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .field-group input.input-error {
        border-color: #EF4444;
    }

    .error-text {
        font-size: 11.5px;
        color: #EF4444;
    }

    .hint-text {
        font-size: 11px;
        color: var(--text-muted, #9CA3AF);
    }

    .quota-stepper {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .quota-btn {
        width: 36px;
        height: 36px;
        border: 1px solid var(--border, #E5E7EB);
        background: var(--bg, #F8FAFC);
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        color: var(--text-primary, #111827);
        cursor: pointer;
        flex-shrink: 0;
    }

    .quota-btn:hover {
        background: var(--border, #E5E7EB);
    }

    .quota-input {
        text-align: center;
        max-width: 70px;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 8px;
    }

    .btn-cancel {
        height: 38px;
        padding: 0 16px;
        border-radius: 8px;
        background: var(--card, #FFFFFF);
        border: 1px solid var(--border, #E5E7EB);
        color: var(--text-secondary, #6B7280);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        font-family: inherit;
    }

    .btn-cancel:hover {
        background: var(--bg, #F8FAFC);
    }

    .btn-submit {
        height: 38px;
        padding: 0 18px;
        border-radius: 8px;
        background: var(--primary, #2563EB);
        border: none;
        color: white;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
    }

    .btn-submit:hover:not(:disabled) {
        background: var(--primary-hover, #1D4ED8);
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>