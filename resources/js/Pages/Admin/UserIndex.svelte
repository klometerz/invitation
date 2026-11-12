<script>
    import { router, useForm } from "@inertiajs/svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";

    let { users, invitations = [] } = $props();

    let editingId = $state(null);

    const form = useForm({
        name: "",
        email: "",
        password: "",
        role: "staff",
        invitation_id: invitations[0]?.id ?? "",
        is_active: true,
    });

    function resetForm() {
        editingId = null;
        form.reset();
        form.clearErrors();
        form.invitation_id = invitations[0]?.id ?? "";
        form.role = "staff";
        form.is_active = true;
    }

    function editUser(user) {
        editingId = user.id;
        form.name = user.name;
        form.email = user.email;
        form.role = user.role;
        form.invitation_id = user.invitation_id ?? invitations[0]?.id ?? "";
        form.is_active = user.is_active;
        form.password = "";
        form.clearErrors();
    }

    function submit(event) {
        event.preventDefault();

        if (editingId) {
            form.put(`/admin/users/${editingId}`, {
                onSuccess: () => resetForm(),
            });
            return;
        }

        form.post("/admin/users", {
            onSuccess: () => resetForm(),
        });
    }

    function toggleActive(user) {
        router.put(`/admin/users/${user.id}`, {
            name: user.name,
            email: user.email,
            role: user.role,
            invitation_id: user.invitation_id,
            is_active: !user.is_active,
        });
    }

    function resetPassword(user) {
        const password = prompt(`Password baru untuk ${user.name} (min. 8 karakter):`);
        if (!password) return;
        router.post(`/admin/users/${user.id}/reset-password`, { password });
    }
</script>

<AdminLayout title="Users" breadcrumbs={["Users"]}>
    <div class="page-head">
        <div>
            <h1 class="page-title">Petugas</h1>
            <p class="page-sub">
                Akun staff hanya bisa membuka scanner untuk acara yang ditugaskan.
            </p>
        </div>
    </div>

    <form class="card create-card" onsubmit={submit}>
        <h2 class="section-title">
            {editingId ? "Edit Petugas" : "Tambah Petugas"}
        </h2>

        <div class="grid grid-3">
            <div class="field">
                <label class="label" for="u-name">Nama</label>
                <input id="u-name" class="input" bind:value={form.name} />
                {#if form.errors.name}<p class="error">{form.errors.name}</p>{/if}
            </div>
            <div class="field">
                <label class="label" for="u-email">Email</label>
                <input id="u-email" class="input" type="email" bind:value={form.email} />
                {#if form.errors.email}<p class="error">{form.errors.email}</p>{/if}
            </div>
            {#if !editingId}
                <div class="field">
                    <label class="label" for="u-password">Password</label>
                    <input
                        id="u-password"
                        class="input"
                        type="password"
                        autocomplete="new-password"
                        bind:value={form.password}
                    />
                    {#if form.errors.password}<p class="error">{form.errors.password}</p>{/if}
                </div>
            {/if}
            <div class="field">
                <label class="label" for="u-invitation">Acara</label>
                <select id="u-invitation" class="input" bind:value={form.invitation_id}>
                    {#each invitations as invitation (invitation.id)}
                        <option value={invitation.id}>{invitation.title}</option>
                    {/each}
                </select>
                {#if form.errors.invitation_id}<p class="error">{form.errors.invitation_id}</p>{/if}
            </div>
            <div class="field">
                <label class="label" for="u-role">Peran</label>
                <select id="u-role" class="input" bind:value={form.role}>
                    <option value="staff">Staff (scanner)</option>
                    <option value="client">Client (tanpa akses)</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            {#if editingId}
                <button type="button" class="btn" onclick={resetForm}>Batal</button>
            {/if}
            <button type="submit" class="btn btn-primary" disabled={form.processing}>
                {editingId ? "Simpan Perubahan" : "Tambah Petugas"}
            </button>
        </div>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Acara</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th class="actions-col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#each users.data as user (user.id)}
                        <tr>
                            <td class="title-cell">{user.name}</td>
                            <td>{user.email}</td>
                            <td>{user.invitation ?? "-"}</td>
                            <td>{user.role}</td>
                            <td>
                                <span class="badge" class:badge-on={user.is_active} class:badge-off={!user.is_active}>
                                    {user.is_active ? "Aktif" : "Nonaktif"}
                                </span>
                            </td>
                            <td class="actions-col">
                                <div class="row-actions">
                                    <button class="mini" onclick={() => editUser(user)}>Edit</button>
                                    <button class="mini" onclick={() => resetPassword(user)}>Reset password</button>
                                    <button
                                        class="mini"
                                        class:danger={user.is_active}
                                        onclick={() => toggleActive(user)}
                                    >
                                        {user.is_active ? "Nonaktifkan" : "Aktifkan"}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    {/each}

                    {#if users.data.length === 0}
                        <tr>
                            <td colspan="6" class="empty">Belum ada petugas.</td>
                        </tr>
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

    .create-card {
        padding: 20px;
        margin-bottom: 18px;
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

    .grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    @media (max-width: 760px) {
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

    .error {
        font-size: 12px;
        color: var(--danger);
        margin: 0;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 16px;
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
    }

    .title-cell {
        font-weight: 600;
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
