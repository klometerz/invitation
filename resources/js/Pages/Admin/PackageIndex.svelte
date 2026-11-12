<script>
    import { router, useForm } from "@inertiajs/svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";

    let { packages = null, featureRegistry = null, limitKeys = [] } = $props();

    const pkgList = $derived(Array.isArray(packages) ? packages : []);
    const features = $derived(
        Array.isArray(featureRegistry) ? featureRegistry : [],
    );
    const limits = $derived(Array.isArray(limitKeys) ? limitKeys : []);

    const limitLabels = {
        max_guests: "Maks. tamu",
        max_gallery_images: "Maks. foto galeri",
        max_photobooth_per_guest: "Maks. foto photobooth / tamu",
        active_days_after_event: "Hari aktif setelah acara",
    };

    let editingId = $state(null);

    function blankForm() {
        const keys = Array.isArray(limitKeys) ? limitKeys : [];
        return {
            name: "",
            slug: "",
            description: "",
            price: "",
            features: [],
            limits: Object.fromEntries(keys.map((key) => [key, ""])),
            is_public: true,
            is_active: true,
            sort_order: 0,
        };
    }

    const form = useForm(blankForm());

    function resetForm() {
        editingId = null;
        form.defaults(blankForm());
        form.reset();
        form.clearErrors();
    }

    function editPackage(pkg) {
        const keys = Array.isArray(limitKeys) ? limitKeys : [];
        editingId = pkg.id;
        form.setStore({
            name: pkg.name,
            slug: pkg.slug,
            description: pkg.description ?? "",
            price: pkg.price ?? "",
            features: features
                .filter((feature) => pkg.features?.[feature.key])
                .map((feature) => feature.key),
            limits: Object.fromEntries(
                keys.map((key) => [key, pkg.limits?.[key] ?? ""]),
            ),
            is_public: pkg.is_public,
            is_active: pkg.is_active,
            sort_order: pkg.sort_order ?? 0,
        });
        form.clearErrors();
    }

    function toggleFeature(key) {
        form.setStore(
            "features",
            $form.features.includes(key)
                ? $form.features.filter((item) => item !== key)
                : [...$form.features, key],
        );
    }

    function submit(event) {
        event.preventDefault();

        const options = { onSuccess: () => resetForm() };

        if (editingId) {
            form.put(`/admin/packages/${editingId}`, options);
        } else {
            form.post("/admin/packages", options);
        }
    }

    function destroyPackage(pkg) {
        if (confirm(`Hapus paket "${pkg.name}"? Undangan yang memakainya kembali tanpa paket.`)) {
            router.delete(`/admin/packages/${pkg.id}`);
        }
    }

    function formatPrice(price) {
        return price === null || price === undefined
            ? "Hubungi kami"
            : `Rp ${Number(price).toLocaleString("id-ID")}`;
    }
</script>

<AdminLayout title="Packages" breadcrumbs={["Packages"]}>
    <div class="page-head">
        <div>
            <h1 class="page-title">Paket</h1>
            <p class="page-sub">
                Fitur dan batas tiap paket. Nama, harga, dan isi paket sepenuhnya
                keputusan Anda.
            </p>
        </div>
    </div>

    <form class="card form-card" onsubmit={submit}>
        <h2 class="section-title">{editingId ? "Edit Paket" : "Paket Baru"}</h2>

        <div class="grid grid-3">
            <div class="field">
                <label class="label" for="p-name">Nama</label>
                <input id="p-name" class="input" bind:value={$form.name} />
                {#if $form.errors.name}<p class="error">{$form.errors.name}</p>{/if}
            </div>
            <div class="field">
                <label class="label" for="p-slug">Slug</label>
                <input id="p-slug" class="input" bind:value={$form.slug} placeholder="basic" />
                {#if $form.errors.slug}<p class="error">{$form.errors.slug}</p>{/if}
            </div>
            <div class="field">
                <label class="label" for="p-price">Harga (kosong = Hubungi kami)</label>
                <input id="p-price" class="input" type="number" min="0" bind:value={$form.price} />
                {#if $form.errors.price}<p class="error">{$form.errors.price}</p>{/if}
            </div>
        </div>

        <div class="field">
            <label class="label" for="p-desc">Deskripsi</label>
            <textarea id="p-desc" class="input textarea" rows="2" bind:value={$form.description}></textarea>
        </div>

        <div class="section-block">
            <p class="label">Fitur</p>
            <div class="feature-grid">
                {#each features as feature (feature.key)}
                    <label class="feature-item">
                        <input
                            type="checkbox"
                            checked={$form.features.includes(feature.key)}
                            onchange={() => toggleFeature(feature.key)}
                        />
                        <span>
                            <strong>{feature.label}</strong>
                            {#if feature.description}
                                <small>{feature.description}</small>
                            {/if}
                        </span>
                    </label>
                {/each}
            </div>
        </div>

        <div class="section-block">
            <p class="label">Batas (kosong = tanpa batas)</p>
            <div class="grid grid-4">
                {#each limits as key (key)}
                    <div class="field">
                        <label class="label" for={`p-limit-${key}`}>{limitLabels[key] ?? key}</label>
                        <input
                            id={`p-limit-${key}`}
                            class="input"
                            type="number"
                            min="0"
                            bind:value={$form.limits[key]}
                        />
                    </div>
                {/each}
            </div>
        </div>

        <div class="grid grid-3">
            <label class="check">
                <input type="checkbox" bind:checked={$form.is_public} />
                <span>Tampilkan di halaman publik</span>
            </label>
            <label class="check">
                <input type="checkbox" bind:checked={$form.is_active} />
                <span>Aktif</span>
            </label>
            <div class="field">
                <label class="label" for="p-sort">Urutan</label>
                <input id="p-sort" class="input" type="number" min="0" bind:value={$form.sort_order} />
            </div>
        </div>

        <div class="form-actions">
            {#if editingId}
                <button type="button" class="btn" onclick={resetForm}>Batal</button>
            {/if}
            <button type="submit" class="btn-primary" disabled={$form.processing}>
                {editingId ? "Simpan Perubahan" : "Buat Paket"}
            </button>
        </div>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th>Fitur aktif</th>
                        <th>Dipakai</th>
                        <th>Status</th>
                        <th class="actions-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#each pkgList as pkg (pkg.id)}
                        <tr>
                            <td class="title-cell">
                                {pkg.name}
                                <div class="muted">{pkg.slug}</div>
                            </td>
                            <td>{formatPrice(pkg.price)}</td>
                            <td>{Object.values(pkg.features).filter(Boolean).length} / {Object.keys(pkg.features).length}</td>
                            <td>{pkg.invitations_count} undangan</td>
                            <td>
                                <span class="badge" class:badge-on={pkg.is_active} class:badge-off={!pkg.is_active}>
                                    {pkg.is_active ? "Aktif" : "Nonaktif"}
                                </span>
                                {#if !pkg.is_public}<span class="muted"> · privat</span>{/if}
                            </td>
                            <td class="actions-col">
                                <div class="row-actions">
                                    <button class="mini" onclick={() => editPackage(pkg)}>Edit</button>
                                    <button class="mini danger" onclick={() => destroyPackage(pkg)}>Hapus</button>
                                </div>
                            </td>
                        </tr>
                    {/each}

                    {#if pkgList.length === 0}
                        <tr><td colspan="6" class="empty">Belum ada paket.</td></tr>
                    {/if}
                </tbody>
            </table>
        </div>
    </div>
</AdminLayout>

<style>
    .page-head {
        margin-bottom: 16px;
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

    .card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .form-card {
        padding: 20px;
        margin-bottom: 18px;
        overflow: visible;
    }

    .section-title {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        margin: 0 0 14px;
    }

    .section-block {
        border-top: 1px solid var(--border);
        margin: 16px 0;
        padding-top: 14px;
    }

    .grid {
        display: grid;
        gap: 12px;
    }

    .grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .grid-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    @media (max-width: 860px) {
        .grid-3,
        .grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
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
    }

    .feature-grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 8px;
    }

    .feature-item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 13px;
        color: var(--text-primary);
    }

    .feature-item small {
        display: block;
        color: var(--text-muted);
        font-size: 11.5px;
        margin-top: 2px;
    }

    .check {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--text-secondary);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        border-top: 1px solid var(--border);
        padding-top: 16px;
        margin-top: 8px;
    }

    .btn,
    .btn-primary {
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

    .table-wrap {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .table th {
        text-align: left;
        padding: 10px 12px;
        background: var(--bg);
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .table td {
        padding: 10px 12px;
        border-top: 1px solid var(--border);
        color: var(--text-primary);
        vertical-align: top;
    }

    .title-cell {
        font-weight: 600;
    }

    .muted {
        font-weight: 400;
        color: var(--text-muted);
        font-size: 12px;
    }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
    }

    .badge-on {
        background: var(--success-light);
        color: #047857;
    }

    .badge-off {
        background: #f3f4f6;
        color: #4b5563;
    }

    .actions-col {
        text-align: right;
    }

    .row-actions {
        display: flex;
        gap: 6px;
        justify-content: flex-end;
    }

    .mini {
        height: 26px;
        padding: 0 9px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-secondary);
        font-size: 11.5px;
        cursor: pointer;
        font-family: inherit;
    }

    .mini:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .mini.danger:hover {
        border-color: var(--danger);
        color: var(--danger);
    }

    .empty {
        text-align: center;
        color: var(--text-muted);
        padding: 24px;
    }
</style>
