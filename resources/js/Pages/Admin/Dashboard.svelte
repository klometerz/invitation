<script>
    import { router } from "@inertiajs/svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";

    let {
        counts = {},
        upcoming = [],
        unpaid = [],
        unpaidCount = 0,
        expiringSoon = 0,
    } = $props();

    const paymentLabel = {
        unpaid: "Belum bayar",
        partial: "DP",
        paid: "Lunas",
    };

    function openInvitation(id) {
        router.post("/admin/tenant/switch", { invitation_id: id });
    }
</script>

<AdminLayout title="Dashboard" breadcrumbs={["Dashboard"]}>
    <div class="page-head">
        <h1 class="page-title">Ringkasan</h1>
        <p class="page-sub">Kondisi semua undangan saat ini.</p>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <span class="stat-label">Aktif</span>
            <span class="stat-value">{counts.active ?? 0}</span>
        </div>
        <div class="stat">
            <span class="stat-label">Draft</span>
            <span class="stat-value">{counts.draft ?? 0}</span>
        </div>
        <div class="stat">
            <span class="stat-label">Berakhir</span>
            <span class="stat-value">{counts.expired ?? 0}</span>
        </div>
        <div class="stat">
            <span class="stat-label">Arsip</span>
            <span class="stat-value">{counts.archived ?? 0}</span>
        </div>
        <div class="stat">
            <span class="stat-label">Belum lunas</span>
            <span class="stat-value">{unpaidCount}</span>
        </div>
        <div class="stat">
            <span class="stat-label">Berakhir &le; 7 hari</span>
            <span class="stat-value">{expiringSoon}</span>
        </div>
    </div>

    <div class="columns">
        <div class="card">
            <div class="card-head">
                <h2>Acara 30 hari ke depan</h2>
                <a class="link" href="/admin/invitations">Semua undangan</a>
            </div>
            {#if upcoming.length === 0}
                <p class="empty">Tidak ada acara dalam 30 hari ke depan.</p>
            {:else}
                <ul class="list">
                    {#each upcoming as item (item.id)}
                        <li>
                            <div>
                                <button class="link" onclick={() => openInvitation(item.id)}>
                                    {item.title}
                                </button>
                                <span class="muted">{item.event_date}</span>
                            </div>
                            <span class="badge badge-{item.status}">{item.status}</span>
                        </li>
                    {/each}
                </ul>
            {/if}
        </div>

        <div class="card">
            <div class="card-head">
                <h2>Pembayaran belum lunas</h2>
            </div>
            {#if unpaid.length === 0}
                <p class="empty">Semua undangan sudah lunas.</p>
            {:else}
                <ul class="list">
                    {#each unpaid as item (item.id)}
                        <li>
                            <div>
                                <button class="link" onclick={() => openInvitation(item.id)}>
                                    {item.title}
                                </button>
                                <span class="muted">
                                    {paymentLabel[item.payment_status] ?? item.payment_status}
                                </span>
                            </div>
                            <span class="amount">
                                {item.amount_paid ?? 0} / {item.price_agreed ?? "-"}
                            </span>
                        </li>
                    {/each}
                </ul>
            {/if}
        </div>
    </div>

    <div class="quick">
        <a class="quick-btn" href="/admin/invitations/create">+ Undangan baru</a>
        <a class="quick-btn" href="/admin/guests">Kelola tamu</a>
        <a class="quick-btn" href="/admin/packages">Paket</a>
        <a class="quick-btn" href="/admin/settings">Pengaturan</a>
    </div>
</AdminLayout>

<style>
    .page-head {
        margin-bottom: 18px;
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

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .stat-label {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
    }

    .stat-value {
        font-size: 22px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .columns {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 16px;
    }

    .card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .card-head h2 {
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        color: var(--text-primary);
    }

    .list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .list li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border-top: 1px solid var(--border);
        padding-top: 8px;
    }

    .link {
        background: none;
        border: none;
        padding: 0;
        color: var(--primary);
        font-family: inherit;
        font-size: 13.5px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
    }

    .muted {
        display: block;
        font-size: 12px;
        color: var(--text-muted);
    }

    .amount {
        font-size: 12.5px;
        color: var(--text-secondary);
        white-space: nowrap;
    }

    .badge {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 999px;
        background: var(--bg);
        color: var(--text-secondary);
        text-transform: capitalize;
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

    .empty {
        font-size: 13px;
        color: var(--text-muted);
        margin: 8px 0 0;
    }

    .quick {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .quick-btn {
        padding: 10px 16px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-primary);
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
    }

    .quick-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
</style>
