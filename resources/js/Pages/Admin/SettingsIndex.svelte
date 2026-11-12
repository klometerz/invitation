<script>
    import { useForm } from "@inertiajs/svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";

    let { settings = null } = $props();

    // Read through $derived with a fallback so a missing/partial payload can
    // never crash the page (see UserIndex for the same pattern).
    const values = $derived(settings && typeof settings === "object" ? settings : {});

    const settingsObj = settings && typeof settings === "object" ? settings : {};

    const form = useForm({
        business_name: settingsObj.business_name ?? "",
        whatsapp_number: settingsObj.whatsapp_number ?? "",
        instagram_url: settingsObj.instagram_url ?? "",
        footer_text: settingsObj.footer_text ?? "",
        platform_logo: null,
    });

    function submit(event) {
        event.preventDefault();
        form.post("/admin/settings", { forceFormData: true });
    }
</script>

<AdminLayout title="Settings" breadcrumbs={["Settings"]}>
    <form class="card" onsubmit={submit}>
        <h1 class="page-title">Pengaturan Platform</h1>
        <p class="page-sub">
            Identitas usaha dan kontak yang dipakai di halaman depan, footer,
            dan tombol WhatsApp.
        </p>

        <div class="grid grid-2">
            <div class="field">
                <label class="label" for="s-name">Nama Usaha</label>
                <input id="s-name" class="input" bind:value={$form.business_name} />
                {#if $form.errors.business_name}<p class="error">{$form.errors.business_name}</p>{/if}
            </div>

            <div class="field">
                <label class="label" for="s-wa">Nomor WhatsApp</label>
                <input
                    id="s-wa"
                    class="input"
                    bind:value={$form.whatsapp_number}
                    placeholder="628123456789"
                />
                <p class="hint">Format internasional tanpa tanda +, mis. 628123456789.</p>
                {#if $form.errors.whatsapp_number}<p class="error">{$form.errors.whatsapp_number}</p>{/if}
            </div>

            <div class="field">
                <label class="label" for="s-ig">Instagram (URL)</label>
                <input
                    id="s-ig"
                    class="input"
                    bind:value={$form.instagram_url}
                    placeholder="https://instagram.com/..."
                />
            </div>

            <div class="field">
                <label class="label" for="s-logo">Logo Platform</label>
                {#if values.platform_logo}
                    <img src={values.platform_logo} alt="Logo" class="logo-preview" />
                {/if}
                <input
                    id="s-logo"
                    class="input"
                    type="file"
                    accept="image/*"
                    onchange={(e) => form.setStore("platform_logo", e.currentTarget.files[0])}
                />
                {#if $form.errors.platform_logo}<p class="error">{$form.errors.platform_logo}</p>{/if}
            </div>
        </div>

        <div class="field">
            <label class="label" for="s-footer">Teks Footer</label>
            <textarea
                id="s-footer"
                class="input textarea"
                rows="3"
                bind:value={$form.footer_text}
            ></textarea>
        </div>

        <div class="actions">
            <button type="submit" class="btn-primary" disabled={$form.processing}>
                {$form.processing ? "Menyimpan..." : "Simpan Pengaturan"}
            </button>
        </div>
    </form>
</AdminLayout>

<style>
    .card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 24px;
        max-width: 860px;
    }

    .page-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0 0 4px;
        color: var(--text-primary);
    }

    .page-sub {
        font-size: 13px;
        color: var(--text-secondary);
        margin: 0 0 20px;
    }

    .grid {
        display: grid;
        gap: 14px;
        margin-bottom: 14px;
    }

    .grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    @media (max-width: 760px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 14px;
    }

    .label {
        font-size: 12.5px;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .input {
        height: 38px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 13.5px;
        color: var(--text-primary);
        background: var(--card);
        box-sizing: border-box;
        width: 100%;
    }

    .textarea {
        height: auto;
        padding: 10px 12px;
        resize: vertical;
    }

    .hint {
        font-size: 12px;
        color: var(--text-muted);
        margin: 0;
    }

    .error {
        font-size: 12px;
        color: var(--danger);
        margin: 0;
    }

    .logo-preview {
        width: 64px;
        height: 64px;
        object-fit: contain;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg);
    }

    .actions {
        display: flex;
        justify-content: flex-end;
        border-top: 1px solid var(--border);
        padding-top: 16px;
    }

    .btn-primary {
        height: 38px;
        padding: 0 18px;
        border-radius: 8px;
        border: none;
        background: var(--primary);
        color: #fff;
        font-family: inherit;
        font-size: 13.5px;
        font-weight: 500;
        cursor: pointer;
    }

    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
