<script>
    import { router, useForm } from "@inertiajs/svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";

    let {
        invitation = null,
        themes = [],
        packages = [],
        reservedSlugs = [],
        sectionOptions = [],
    } = $props();

    const isEdit = invitation !== null;

    const defaultSections = sectionOptions.length
        ? sectionOptions.map((section) => section.key)
        : ["welcome", "verses", "event", "info", "gallery", "rsvp", "thanks"];

    const form = useForm({
        title: invitation?.title ?? "",
        slug: invitation?.slug ?? "",
        theme_id: invitation?.theme_id ?? themes[0]?.id ?? "",
        package_id: invitation?.package_id ?? "",
        status: invitation?.status ?? "draft",
        event_date: invitation?.event_date ?? "",
        active_from: invitation?.active_from ?? "",
        active_until: invitation?.active_until ?? "",
        client_name: invitation?.client_name ?? "",
        client_phone: invitation?.client_phone ?? "",
        client_email: invitation?.client_email ?? "",
        price_agreed: invitation?.price_agreed ?? "",
        payment_status: invitation?.payment_status ?? "unpaid",
        amount_paid: invitation?.amount_paid ?? 0,
        paid_at: invitation?.paid_at ?? "",
        internal_notes: invitation?.internal_notes ?? "",
        is_demo: invitation?.is_demo ?? false,
        sections: invitation?.sections?.length
            ? [...invitation.sections]
            : [...defaultSections],
    });

    function toggleSection(key) {
        if (form.sections.includes(key)) {
            // `welcome` is mandatory (server also forces it).
            if (key === "welcome") return;
            form.sections = form.sections.filter((item) => item !== key);
        } else {
            form.sections = [...form.sections, key];
        }
    }

    function moveSection(index, direction) {
        const target = index + direction;
        if (target < 0 || target >= form.sections.length) return;
        // `welcome` stays first.
        if (form.sections[index] === "welcome" || form.sections[target] === "welcome") return;

        const next = [...form.sections];
        [next[index], next[target]] = [next[target], next[index]];
        form.sections = next;
    }

    function sectionLabel(key) {
        return sectionOptions.find((section) => section.key === key)?.label ?? key;
    }

    const slugPattern = /^[a-z0-9]+(-[a-z0-9]+)*$/;

    const slugError = $derived.by(() => {
        const value = (form.slug ?? "").trim();
        if (!value) return "Slug wajib diisi.";
        if (!slugPattern.test(value)) {
            return "Hanya huruf kecil, angka dan tanda hubung tunggal.";
        }
        if (reservedSlugs.includes(value)) return "Slug ini tidak tersedia.";
        return "";
    });

    function submit(event) {
        event.preventDefault();
        if (slugError) return;

        if (isEdit) {
            form.put(`/admin/invitations/${invitation.id}`);
        } else {
            form.post("/admin/invitations");
        }
    }

    function cancel() {
        router.get("/admin/invitations");
    }
</script>

<AdminLayout
    title={isEdit ? "Edit Undangan" : "Undangan Baru"}
    breadcrumbs={["Invitations", isEdit ? "Edit" : "Baru"]}
>
    <form class="form-card" onsubmit={submit}>
        <div class="page-head">
            <h1 class="page-title">{isEdit ? "Edit Undangan" : "Undangan Baru"}</h1>
            <p class="page-sub">
                Data acara, status publikasi, dan pembayaran manual.
            </p>
        </div>

        <section class="section">
            <h2 class="section-title">Info Acara</h2>
            <div class="grid grid-2">
                <div class="field">
                    <label class="label" for="f-title">Judul</label>
                    <input id="f-title" class="input" bind:value={form.title} />
                    {#if form.errors.title}<p class="error">{form.errors.title}</p>{/if}
                </div>

                <div class="field">
                    <label class="label" for="f-slug">Slug (URL)</label>
                    <input
                        id="f-slug"
                        class="input"
                        bind:value={form.slug}
                        placeholder="nama-pasangan"
                    />
                    {#if slugError}
                        <p class="error">{slugError}</p>
                    {:else if form.errors.slug}
                        <p class="error">{form.errors.slug}</p>
                    {:else}
                        <p class="hint">/{form.slug}</p>
                    {/if}
                </div>

                <div class="field">
                    <label class="label" for="f-theme">Tema</label>
                    <select id="f-theme" class="input" bind:value={form.theme_id}>
                        {#each themes as theme (theme.id)}
                            <option value={theme.id}>{theme.name}</option>
                        {/each}
                    </select>
                    {#if form.errors.theme_id}<p class="error">{form.errors.theme_id}</p>{/if}
                </div>

                <div class="field">
                    <label class="label" for="f-package">Paket</label>
                    <select id="f-package" class="input" bind:value={form.package_id}>
                        <option value="">Tanpa paket</option>
                        {#each packages as pkg (pkg.id)}
                            <option value={pkg.id}>
                                {pkg.name}{pkg.price === null ? " (hubungi kami)" : ` — Rp ${pkg.price}`}
                            </option>
                        {/each}
                    </select>
                    {#if form.errors.package_id}<p class="error">{form.errors.package_id}</p>{/if}
                </div>

                <div class="field">
                    <label class="label" for="f-status">Status</label>
                    <select id="f-status" class="input" bind:value={form.status}>
                        <option value="draft">Draft (hanya admin)</option>
                        <option value="active">Aktif</option>
                        <option value="expired">Berakhir</option>
                        <option value="archived">Arsip (404)</option>
                    </select>
                </div>

                <div class="field">
                    <label class="label" for="f-event">Tanggal Acara</label>
                    <input id="f-event" class="input" type="date" bind:value={form.event_date} />
                </div>

                <div class="field">
                    <label class="label" for="f-from">Aktif Dari</label>
                    <input id="f-from" class="input" type="datetime-local" bind:value={form.active_from} />
                </div>

                <div class="field">
                    <label class="label" for="f-until">Aktif Sampai</label>
                    <input id="f-until" class="input" type="datetime-local" bind:value={form.active_until} />
                    {#if form.errors.active_until}<p class="error">{form.errors.active_until}</p>{/if}
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Data Klien</h2>
            <div class="grid grid-3">
                <div class="field">
                    <label class="label" for="f-cname">Nama Klien</label>
                    <input id="f-cname" class="input" bind:value={form.client_name} />
                </div>
                <div class="field">
                    <label class="label" for="f-cphone">No. WhatsApp</label>
                    <input id="f-cphone" class="input" bind:value={form.client_phone} />
                </div>
                <div class="field">
                    <label class="label" for="f-cemail">Email</label>
                    <input id="f-cemail" class="input" type="email" bind:value={form.client_email} />
                    {#if form.errors.client_email}<p class="error">{form.errors.client_email}</p>{/if}
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Pembayaran (manual)</h2>
            <div class="grid grid-3">
                <div class="field">
                    <label class="label" for="f-price">Harga Disepakati</label>
                    <input id="f-price" class="input" type="number" min="0" bind:value={form.price_agreed} />
                </div>
                <div class="field">
                    <label class="label" for="f-payment">Status Pembayaran</label>
                    <select id="f-payment" class="input" bind:value={form.payment_status}>
                        <option value="unpaid">Belum bayar</option>
                        <option value="partial">DP / sebagian</option>
                        <option value="paid">Lunas</option>
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="f-paid">Jumlah Dibayar</label>
                    <input id="f-paid" class="input" type="number" min="0" bind:value={form.amount_paid} />
                </div>
                <div class="field">
                    <label class="label" for="f-paidat">Tanggal Bayar</label>
                    <input id="f-paidat" class="input" type="datetime-local" bind:value={form.paid_at} />
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Bagian Undangan</h2>
            <p class="hint">
                Atur bagian yang tampil dan urutannya. Bagian yang fiturnya
                tidak termasuk paket otomatis disembunyikan.
            </p>

            <ul class="sections-list">
                {#each form.sections as key, i (key)}
                    <li class="section-row">
                        <label class="section-check">
                            <input
                                type="checkbox"
                                checked
                                disabled={key === "welcome"}
                                onchange={() => toggleSection(key)}
                            />
                            <span>{sectionLabel(key)}</span>
                            {#if key === "welcome"}
                                <span class="badge">wajib</span>
                            {/if}
                        </label>
                        <div class="section-actions">
                            <button
                                type="button"
                                class="mini"
                                disabled={i === 0}
                                onclick={() => moveSection(i, -1)}
                                aria-label="Naikkan"
                            >↑</button>
                            <button
                                type="button"
                                class="mini"
                                disabled={i === form.sections.length - 1}
                                onclick={() => moveSection(i, 1)}
                                aria-label="Turunkan"
                            >↓</button>
                        </div>
                    </li>
                {/each}
            </ul>

            {#if sectionOptions.some((section) => !form.sections.includes(section.key))}
                <p class="hint" style="margin-top: 12px;">Tambah bagian:</p>
                <div class="chips">
                    {#each sectionOptions.filter((section) => !form.sections.includes(section.key)) as section (section.key)}
                        <button
                            type="button"
                            class="chip"
                            onclick={() => toggleSection(section.key)}
                        >+ {section.label}</button>
                    {/each}
                </div>
            {/if}
        </section>

        <section class="section">
            <h2 class="section-title">Catatan Internal</h2>
            <textarea class="input textarea" rows="3" bind:value={form.internal_notes}></textarea>
        </section>

        <div class="form-actions">
            <button type="button" class="btn" onclick={cancel}>Batal</button>
            <button
                type="submit"
                class="btn btn-primary"
                disabled={form.processing || !!slugError}
            >
                {isEdit ? "Simpan Perubahan" : "Buat Undangan"}
            </button>
        </div>
    </form>
</AdminLayout>

<style>
    .form-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 24px;
        max-width: 980px;
    }

    .page-head {
        margin-bottom: 8px;
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
        margin: 0;
    }

    .section {
        padding: 18px 0;
        border-top: 1px solid var(--border);
    }

    .section-title {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        margin: 0 0 12px;
    }

    .grid {
        display: grid;
        gap: 12px;
    }

    .grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    @media (max-width: 760px) {
        .grid-2,
        .grid-3 {
            grid-template-columns: 1fr;
        }
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
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

    .sections-list {
        list-style: none;
        padding: 0;
        margin: 12px 0 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .section-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 8px 12px;
        background: var(--bg);
    }

    .section-check {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        color: var(--text-primary);
    }

    .badge {
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        border: 1px solid var(--border);
        border-radius: 999px;
        padding: 1px 7px;
    }

    .section-actions {
        display: flex;
        gap: 4px;
    }

    .mini {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 13px;
        line-height: 1;
    }

    .mini:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
    }

    .chip {
        height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        border: 1px dashed var(--border);
        background: transparent;
        color: var(--text-secondary);
        font-size: 12.5px;
        font-family: inherit;
        cursor: pointer;
    }

    .chip:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .error {
        font-size: 12px;
        color: var(--danger);
        margin: 0;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        border-top: 1px solid var(--border);
        padding-top: 16px;
    }

    .btn {
        height: 38px;
        padding: 0 16px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-primary);
        font-family: inherit;
        font-size: 13.5px;
        font-weight: 500;
        cursor: pointer;
    }

    .btn-primary {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
