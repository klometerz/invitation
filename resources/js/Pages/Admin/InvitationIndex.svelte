<script>
    import { router } from "@inertiajs/svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";
    import { toast } from "../../Components/Admin/toast.js";

    let { invitations, selectedInvitationId, filters } = $props();

    let search = $state(filters?.search ?? "");

    const statusLabel = {
        draft: "Draft",
        active: "Aktif",
        expired: "Berakhir",
        archived: "Arsip",
    };

    const paymentLabel = {
        unpaid: "Belum bayar",
        partial: "DP",
        paid: "Lunas",
    };

    function switchTo(id) {
        router.post("/admin/tenant/switch", { invitation_id: id });
    }

    function copyLink(url) {
        navigator.clipboard?.writeText(url).then(
            () => toast("Link undangan disalin.", "success"),
            () => toast("Gagal menyalin link.", "error"),
        );
    }

    function duplicate(id) {
        router.post(`/admin/invitations/${id}/duplicate`);
    }

    function archive(id, title) {
        if (confirm(`Arsipkan "${title}"? Undangan tidak akan bisa dibuka publik.`)) {
            router.post(`/admin/invitations/${id}/archive`);
        }
    }

    function activate(id, title) {
        if (confirm(`Aktifkan "${title}" sekarang?`)) {
            router.post(`/admin/invitations/${id}/activate`);
        }
    }

    function submitSearch() {
        router.get(
            "/admin/invitations",
            { search: search || undefined },
            { preserveState: true },
        );
    }
</script>

<AdminLayout title="Invitations" breadcrumbs={["Invitations"]}>
    <div class="page-head">
        <div>
            <h1 class="page-title">Daftar Undangan</h1>
            <p class="page-sub">{invitations.total} acara terdaftar</p>
        </div>
        <div class="toolbar">
            <input
                class="input"
                type="search"
                placeholder="Cari judul / slug / klien"
                bind:value={search}
                onkeydown={(e) => e.key === "Enter" && submitSearch()}
            />
            <button class="btn btn-secondary" onclick={submitSearch}>Cari</button>
            <a class="btn btn-primary" href="/admin/invitations/create">+ Acara Baru</a>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Slug</th>
                        <th>Tema</th>
                        <th>Paket</th>
                        <th>Status</th>
                        <th>Tanggal Acara</th>
                        <th>Pembayaran</th>
                        <th>Berlaku Sampai</th>
                        <th class="actions-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#each invitations.data as invitation (invitation.id)}
                        <tr class:selected={invitation.id === selectedInvitationId}>
                            <td class="title-cell">{invitation.title}</td>
                            <td><code>{invitation.slug}</code></td>
                            <td>{invitation.theme ?? "-"}</td>
                            <td>{invitation.package ?? "-"}</td>
                            <td>
                                <span class="badge badge-{invitation.status}">
                                    {statusLabel[invitation.status] ?? invitation.status}
                                </span>
                            </td>
                            <td>{invitation.event_date ?? "-"}</td>
                            <td>{paymentLabel[invitation.payment_status] ?? invitation.payment_status}</td>
                            <td>{invitation.active_until ?? "-"}</td>
                            <td class="actions-col">
                                <div class="row-actions">
                                    <button class="mini" onclick={() => switchTo(invitation.id)}>Buka</button>
                                    <button class="mini" onclick={() => copyLink(invitation.public_url)}>Link</button>
                                    <button
                                        class="mini"
                                        onclick={() => window.open(invitation.public_url, "_blank")}
                                    >Preview</button>
                                    <a class="mini" href={`/admin/invitations/${invitation.id}/edit`}>Edit</a>
                                    {#if invitation.status === "draft" || invitation.status === "expired"}
                                        <button
                                            class="mini"
                                            onclick={() => activate(invitation.id, invitation.title)}
                                        >Aktifkan</button>
                                    {/if}
                                    <button class="mini" onclick={() => duplicate(invitation.id)}>Duplikat</button>
                                    <button
                                        class="mini danger"
                                        onclick={() => archive(invitation.id, invitation.title)}
                                    >Arsip</button>
                                </div>
                            </td>
                        </tr>
                    {/each}

                    {#if invitations.data.length === 0}
                        <tr>
                            <td colspan="9" class="empty">Belum ada undangan.</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>

        {#if invitations.last_page > 1}
            <div class="pagination">
                {#each Array(invitations.last_page) as _, i}
                    <a
                        class="page-link"
                        class:active={invitations.current_page === i + 1}
                        href={`/admin/invitations?page=${i + 1}`}
                    >{i + 1}</a>
                {/each}
            </div>
        {/if}
    </div>
</AdminLayout>

<style>
    .page-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .page-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 4px;
    }

    .page-sub {
        font-size: 13px;
        color: var(--text-secondary);
        margin: 0;
    }

    .toolbar {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .input {
        height: 36px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-family: inherit;
        font-size: 13px;
        min-width: 240px;
    }

    .btn {
        height: 36px;
        padding: 0 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-primary);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-primary {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
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
        white-space: nowrap;
    }

    .table td {
        padding: 10px 12px;
        border-top: 1px solid var(--border);
        color: var(--text-primary);
        white-space: nowrap;
    }

    .table tr.selected {
        background: var(--primary-light);
    }

    .title-cell {
        font-weight: 600;
    }

    code {
        background: var(--bg);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
    }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
    }

    .badge-active {
        background: var(--success-light);
        color: #047857;
    }

    .badge-draft {
        background: var(--warning-light);
        color: #92400e;
    }

    .badge-expired {
        background: var(--danger-light);
        color: #b91c1c;
    }

    .badge-archived {
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
        flex-wrap: wrap;
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
        text-decoration: none;
        display: inline-flex;
        align-items: center;
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

    .pagination {
        display: flex;
        gap: 6px;
        padding: 12px;
        border-top: 1px solid var(--border);
    }

    .page-link {
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid var(--border);
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 12.5px;
    }

    .page-link.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
</style>
