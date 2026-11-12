<script>
    import { router } from "@inertiajs/svelte";
    import { onMount } from "svelte";
    import GuestFormModal from "../../Components/Admin/GuestFormModal.svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";
    import Toast from "../../Components/Admin/Toast.svelte";
    import ConfirmModal from "../../Components/Admin/ConfirmModal.svelte";
    import ResetGuestsModal from "../../Components/Admin/ResetGuestsModal.svelte";
    import { toast } from "../../Components/Admin/toast.js";

    let {
        guests,
        summary,
        filters,
        categories,
        invitation,
        inviteTemplate = "",
        coupleNames = "",
    } = $props();
    let fileInput = $state(null);
    let isUploading = $state(false);
    let copyStatus = $state(null);
    let searchQuery = $state(filters?.search || "");

    let selectedIds = $state(new Set());

    function toggleSelect(id) {
        const newSet = new Set(selectedIds);
        if (newSet.has(id)) {
            newSet.delete(id);
        } else {
            newSet.add(id);
        }
        selectedIds = newSet;
    }

    function toggleSelectAll() {
        if (selectedIds.size === guests.data.length) {
            selectedIds = new Set();
        } else {
            selectedIds = new Set(guests.data.map((g) => g.id));
        }
    }

    function printSelected() {
        if (selectedIds.size === 0) return;
        const idsParam = Array.from(selectedIds).join(",");
        window.open(`/admin/guests/print-labels?ids=${idsParam}`, "_blank");
    }

    // Confirm modal state
    let confirmOpen = $state(false);
    let confirmConfig = $state({
        title: "",
        message: "",
        variant: "default",
        onConfirm: () => {},
    });

    function showConfirm({ title, message, variant = "default", onConfirm }) {
        confirmConfig = { title, message, variant, onConfirm };
        confirmOpen = true;
    }

    function handleDeleteAll() {
        resetOpen = true;
    }

    let resetOpen = $state(false);

    function handleResetConfirmed(confirmSlug) {
        router.delete("/admin/guests/reset", {
            data: { confirm_slug: confirmSlug },
            onSuccess: () => toast("Data tamu acara ini telah dihapus.", "success"),
            onError: () =>
                toast("Gagal menghapus: slug konfirmasi tidak cocok.", "error"),
        });
    }

    function handleImport() {
        if (!fileInput.files[0]) {
            toast("Pilih file Excel terlebih dahulu.", "warning");
            return;
        }
        isUploading = true;
        const formData = new FormData();
        formData.append("file", fileInput.files[0]);
        router.post("/admin/guests/import", formData, {
            onSuccess: () => {
                isUploading = false;
                toast("Import berhasil!", "success");
            },
            onError: () => {
                isUploading = false;
                toast("Import gagal. Periksa format file.", "error");
            },
        });
    }

    let formModalOpen = $state(false);
    let editingGuest = $state(null); // null = create mode

    function openCreateModal() {
        editingGuest = null;
        formModalOpen = true;
    }

    function openEditModal(guest) {
        editingGuest = guest;
        formModalOpen = true;
    }

    function handleFormSuccess() {
        toast(
            editingGuest
                ? "Data tamu berhasil diupdate!"
                : "Tamu baru berhasil ditambahkan!",
            "success",
        );
    }

    //     function buildInviteMessage(guest) {
    //         const greeting = `Halo ${guest.name},`;
    //         const intro = `Dengan senang hati kami mengundang Anda untuk hadir pada acara pernikahan kami.`;
    //         const quotaInfo = guest.quota > 1 ? ` Acara ini terbuka beserta ${guest.quota} orang pendamping.` : "";
    //         const linkInfo = `Silakan lihat undangan lengkap di:
    // ${guest.link}`;
    //         const closing = `Terima kasih atas perhatian dan kehadirannya. Sampai jumpa di hari bahagia kami.`;

    //         return `${greeting}\n\n${intro}${quotaInfo}\n\n${linkInfo}\n\n${closing}`;
    //     }

    // P2-T8: render the per-invitation template instead of hardcoded wording.
    function buildInviteMessage(guest) {
        if (!inviteTemplate) return guest.link;

        return inviteTemplate
            .replaceAll("{name}", guest.name ?? "")
            .replaceAll("{link}", guest.link ?? "")
            .replaceAll("{quota}", String(guest.quota ?? 1))
            .replaceAll("{couple}", coupleNames ?? "");
    }

    async function copyToClipboard(text, id, failMessage = "Gagal copy link!") {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                showSuccess(id);
                return;
            } catch (err) {}
        }
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand("copy");
            showSuccess(id);
        } catch (err) {
            toast(failMessage, "error");
        }
        document.body.removeChild(textArea);
    }

    function copyInviteMessage(guest, id) {
        const message = buildInviteMessage(guest);
        copyToClipboard(message, id, "Gagal copy pesan!");
    }

    function openWhatsApp(guest) {
        const message = buildInviteMessage(guest);
        const url = `https://wa.me/?text=${encodeURIComponent(message)}`;
        window.open(url, "_blank");
    }

    function showSuccess(id) {
        copyStatus = id;
        setTimeout(() => (copyStatus = null), 2000);
    }

    function handleDelete(id, name) {
        showConfirm({
            title: `Hapus Tamu?`,
            message: `Yakin ingin menghapus "${name}" dari daftar tamu?`,
            variant: "danger",
            onConfirm: () => {
                router.delete(`/admin/guests/${id}`, {
                    onSuccess: () =>
                        toast(`${name} berhasil dihapus.`, "success"),
                    onError: () => toast("Gagal menghapus tamu.", "error"),
                });
            },
        });
    }

    function applyFilter(filterValue) {
        router.get(
            "/admin/guests",
            { filter: filterValue, search: searchQuery || undefined },
            { preserveState: true },
        );
    }

    function handleSearch() {
        router.get(
            "/admin/guests",
            {
                filter: filters?.filter || undefined,
                search: searchQuery || undefined,
            },
            { preserveState: true },
        );
    }

    function exportExcel() {
        window.location.href = "/admin/guests/export";
    }

    function downloadQrCode(guest, event) {
        const row = event.currentTarget.closest("tr");
        const svgEl = row.querySelector(".qr-thumb svg");

        if (!svgEl) {
            toast("QR code tidak ditemukan.", "error");
            return;
        }

        const svgData = new XMLSerializer().serializeToString(svgEl);
        const svgBlob = new Blob([svgData], {
            type: "image/svg+xml;charset=utf-8",
        });
        const svgUrl = URL.createObjectURL(svgBlob);

        const img = new Image();
        img.onload = () => {
            const size = 500; // resolusi lebih besar biar tajam pas di-zoom/print
            const canvas = document.createElement("canvas");
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext("2d");
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, size, size);
            ctx.drawImage(img, 0, 0, size, size);

            canvas.toBlob((blob) => {
                const link = document.createElement("a");
                const safeName = guest.name.replace(/\s+/g, "-").toLowerCase();
                link.download = `qr-${safeName}.png`;
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
            }, "image/png");

            URL.revokeObjectURL(svgUrl);
        };
        img.src = svgUrl;
    }
</script>

<AdminLayout title="Guests" breadcrumbs={["Guests"]}>
    <Toast />
    <ConfirmModal
        bind:open={confirmOpen}
        title={confirmConfig.title}
        message={confirmConfig.message}
        variant={confirmConfig.variant}
        confirmLabel={confirmConfig.variant === "danger"
            ? "Ya, Hapus"
            : "Konfirmasi"}
        onConfirm={confirmConfig.onConfirm}
    />

    <ResetGuestsModal
        bind:open={resetOpen}
        slug={invitation?.slug ?? ""}
        count={guests.total}
        onConfirm={handleResetConfirmed}
    />

    <!-- PAGE TITLE + TOOLBAR -->
    <div class="page-head">
        <div class="page-title-wrap">
            <h1 class="page-title">Guest Management</h1>
            <p class="page-sub">{guests.total} tamu terdaftar</p>
        </div>

        <div class="toolbar-right">
            <button class="btn btn-primary" onclick={openCreateModal}>
                <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <line x1="12" y1="5" x2="12" y2="19" /><line
                        x1="5"
                        y1="12"
                        x2="19"
                        y2="12"
                    />
                </svg>
                Tambah Tamu
            </button>
            <!-- Import Group -->
            <div class="import-group">
                <input
                    type="file"
                    bind:this={fileInput}
                    accept=".xlsx,.xls"
                    hidden
                    id="excel-input"
                />
                <label for="excel-input" class="file-picker-btn">
                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                        /><polyline points="17 8 12 3 7 8" /><line
                            x1="12"
                            y1="3"
                            x2="12"
                            y2="15"
                        />
                    </svg>
                    {fileInput?.files?.[0]?.name
                        ? fileInput.files[0].name.substring(0, 16) + "…"
                        : "Pilih Excel"}
                </label>
                <button
                    class="btn btn-secondary"
                    onclick={handleImport}
                    disabled={isUploading}
                >
                    {isUploading ? "Importing..." : "Import"}
                </button>
            </div>

            <button class="btn btn-ghost" onclick={exportExcel}>
                <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path
                        d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                    /><polyline points="7 10 12 15 17 10" /><line
                        x1="12"
                        y1="15"
                        x2="12"
                        y2="3"
                    />
                </svg>
                Export Excel
            </button>

            <a
                href="/admin/guests/print-labels"
                target="_blank"
                class="btn btn-ghost"
            >
                <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <polyline points="6 9 6 2 18 2 18 9" /><path
                        d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
                    /><rect x="6" y="14" width="12" height="8" />
                </svg>
                Print Labels
            </a>

            <button class="btn btn-danger-ghost" onclick={handleDeleteAll}>
                <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <polyline points="3 6 5 6 21 6" /><path
                        d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"
                    />
                </svg>
                Reset Data
            </button>
        </div>
    </div>

    {#if selectedIds.size > 0}
        <div class="selection-bar">
            <span class="selection-count">{selectedIds.size} tamu dipilih</span>
            <div class="selection-actions">
                <button class="btn btn-primary btn-sm" onclick={printSelected}>
                    🖨 Print {selectedIds.size} Label
                </button>
                <button
                    class="btn btn-ghost btn-sm"
                    onclick={() => (selectedIds = new Set())}
                >
                    Batal Pilih
                </button>
            </div>
        </div>
    {/if}

    <!-- STAT CARDS -->
    <div class="stat-grid">
        <div class="stat-card">
            <span class="stat-value">{summary.total}</span>
            <span class="stat-label">Total Tamu</span>
            <div class="stat-bar" style="--pct: 100%; --clr: #2563EB"></div>
        </div>

        <div class="stat-card stat-success">
            <span class="stat-value" style="color: var(--success)"
                >{summary.arrived}</span
            >
            <span class="stat-label">Sudah Hadir</span>
            <div
                class="stat-bar"
                style="--pct: {summary.total > 0
                    ? ((summary.arrived / summary.total) * 100).toFixed(0)
                    : 0}%; --clr: #10B981"
            ></div>
        </div>

        <div class="stat-card stat-warning">
            <span class="stat-value" style="color: var(--warning)"
                >{summary.not_arrived}</span
            >
            <span class="stat-label">Belum Hadir</span>
            <div
                class="stat-bar"
                style="--pct: {summary.total > 0
                    ? ((summary.not_arrived / summary.total) * 100).toFixed(0)
                    : 0}%; --clr: #F59E0B"
            ></div>
        </div>

        <!-- RSVP CONSOLIDATED CARD -->
        <div class="stat-card stat-rsvp-multi">
            <span class="stat-multi-label">Status RSVP</span>
            <div class="stat-multi-row">
                <div class="stat-multi-item">
                    <span class="stat-multi-value" style="color: #10B981"
                        >{summary.rsvp_confirmed}</span
                    >
                    <span class="stat-multi-sublabel">Akan Hadir</span>
                </div>
                <div class="stat-multi-divider"></div>
                <div class="stat-multi-item">
                    <span class="stat-multi-value" style="color: #EF4444"
                        >{summary.rsvp_declined}</span
                    >
                    <span class="stat-multi-sublabel">Tidak Hadir</span>
                </div>
                <div class="stat-multi-divider"></div>
                <div class="stat-multi-item">
                    <span class="stat-multi-value" style="color: #9CA3AF"
                        >{summary.rsvp_pending}</span
                    >
                    <span class="stat-multi-sublabel">Belum RSVP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <div class="filter-bar">
        <div class="filter-tabs">
            <button
                class="filter-tab"
                class:active={!filters?.filter}
                onclick={() => applyFilter(null)}>Semua</button
            >
            <button
                class="filter-tab"
                class:active={filters?.filter === "arrived"}
                onclick={() => applyFilter("arrived")}
            >
                <span class="dot dot-green"></span>
                Sudah Hadir
            </button>
            <button
                class="filter-tab"
                class:active={filters?.filter === "not_arrived"}
                onclick={() => applyFilter("not_arrived")}
            >
                <span class="dot dot-gray"></span>
                Belum Hadir
            </button>

            {#if categories.length > 0}
                <select
                    class="category-filter"
                    value={filters?.category || ""}
                    onchange={(e) => {
                        router.get(
                            "/admin/guests",
                            {
                                filter: filters?.filter,
                                search: searchQuery,
                                category: e.target.value || undefined,
                            },
                            { preserveState: true },
                        );
                    }}
                >
                    <option value="">Semua Kategori</option>
                    {#each categories as cat}
                        <option value={cat}>{cat}</option>
                    {/each}
                </select>
            {/if}

            <select
                class="category-filter"
                value={filters?.rsvp || ""}
                onchange={(e) => {
                    router.get(
                        "/admin/guests",
                        {
                            filter: filters?.filter,
                            search: searchQuery,
                            category: filters?.category,
                            rsvp: e.target.value || undefined,
                        },
                        { preserveState: true },
                    );
                }}
            >
                <option value="">Semua Status RSVP</option>
                <option value="confirmed">Akan Hadir</option>
                <option value="declined">Tidak Hadir</option>
                <option value="pending">Belum RSVP</option>
            </select>
        </div>

        <div class="search-group">
            <div class="search-input-wrap">
                <svg
                    class="search-icon"
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <circle cx="11" cy="11" r="8" /><line
                        x1="21"
                        y1="21"
                        x2="16.65"
                        y2="16.65"
                    />
                </svg>
                <input
                    type="text"
                    class="search-input"
                    bind:value={searchQuery}
                    placeholder="Cari nama tamu..."
                    onkeydown={(e) => e.key === "Enter" && handleSearch()}
                />
            </div>
            <button class="btn btn-primary btn-sm" onclick={handleSearch}
                >Cari</button
            >
        </div>
    </div>

    <!-- DATA TABLE -->
    <div class="table-card">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:36px">
                            <input
                                type="checkbox"
                                class="row-checkbox"
                                checked={guests.data.length > 0 &&
                                    selectedIds.size === guests.data.length}
                                onchange={toggleSelectAll}
                            />
                        </th>
                        <th style="width:60px">QR</th>
                        <th>Info Tamu</th>
                        <th style="width:130px">Kode Tamu</th>
                        <th>Invitation Link</th>
                        <th style="width:130px">RSVP</th>
                        <th style="width:130px">Check-in</th>
                        <th style="width:160px; text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {#each guests.data as guest}
                        <tr class:row-selected={selectedIds.has(guest.id)}>
                            <td>
                                <input
                                    type="checkbox"
                                    class="row-checkbox"
                                    checked={selectedIds.has(guest.id)}
                                    onchange={() => toggleSelect(guest.id)}
                                />
                            </td>
                            <td>
                                <div class="qr-thumb">
                                    {@html guest.qrcode}
                                </div>
                            </td>
                            <td>
                                <div class="guest-info">
                                    <div class="guest-name-row">
                                        <span
                                            class="guest-name"
                                            title={guest.message
                                                ? guest.message
                                                : ""}>{guest.name}</span
                                        >
                                        {#if guest.category}
                                            <span class="category-badge"
                                                >{guest.category}</span
                                            >
                                        {/if}
                                    </div>
                                    <span class="guest-meta"
                                        >{guest.city || "—"} · Quota: {guest.quota}</span
                                    >
                                </div>
                            </td>
                            <td>
                                <code class="code-pill">{guest.code}</code>
                            </td>
                            <td>
                                <code class="link-pill">{guest.link}</code>
                            </td>
                            <td>
                                {#if guest.is_rsvp}
                                    {#if guest.attendance === "confirmed"}
                                        <span class="badge badge-success">
                                            <span class="badge-dot"></span>
                                            Akan Hadir
                                        </span>
                                    {:else}
                                        <span class="badge badge-danger">
                                            <span class="badge-dot"></span>
                                            Tidak Hadir
                                        </span>
                                    {/if}
                                    {#if guest.message}
                                        <div
                                            class="message-preview"
                                            title={guest.message}
                                        >
                                            💬 {guest.message.length > 25
                                                ? guest.message.substring(
                                                      0,
                                                      25,
                                                  ) + "..."
                                                : guest.message}
                                        </div>
                                    {/if}
                                {:else}
                                    <span class="badge badge-neutral">
                                        <span class="badge-dot"></span>
                                        Belum RSVP
                                    </span>
                                {/if}
                            </td>
                            <td>
                                {#if guest.checkin_status === "arrived"}
                                    <span class="badge badge-success">
                                        <span class="badge-dot"></span>
                                        Hadir
                                    </span>
                                    <div class="checkin-time">
                                        {guest.checked_in_at}
                                    </div>
                                {:else}
                                    <span class="badge badge-neutral">
                                        <span class="badge-dot"></span>
                                        Belum Hadir
                                    </span>
                                {/if}
                            </td>
                            <td>
                                <div class="action-group">
                                    <button
                                        class="action-icon-btn"
                                        class:action-icon-btn-copied={copyStatus ===
                                            `msg-${guest.id}`}
                                        onclick={() =>
                                            copyInviteMessage(
                                                guest,
                                                `msg-${guest.id}`,
                                            )}
                                        title="Copy Pesan Undangan"
                                    >
                                        {#if copyStatus === `msg-${guest.id}`}
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.5"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                ><polyline
                                                    points="20 6 9 17 4 12"
                                                /></svg
                                            >
                                        {:else}
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                ><path
                                                    d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10Z"
                                                /><path
                                                    d="M7 8h10M7 12h7"
                                                /></svg
                                            >
                                        {/if}
                                    </button>

                                    <button
                                        class="action-icon-btn action-icon-btn-whatsapp"
                                        onclick={() => openWhatsApp(guest)}
                                        title="Kirim via WhatsApp"
                                    >
                                        <svg
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                        >
                                            <path
                                                d="M12.01 2C6.47 2 2 6.48 2 12.04c0 1.99.52 3.84 1.42 5.45L2 22l4.69-1.23A9.99 9.99 0 0 0 12.01 22c5.54 0 10.01-4.48 10.01-9.96C22.02 6.48 17.55 2 12.01 2Zm5.03 13.14c-.26.73-1.53 1.42-2.14 1.51-.57.09-1.25.12-2.95-.56-2.36-.95-3.88-3.33-4.01-3.49-.14-.16-1.14-1.31-1.14-2.5 0-1.19.64-1.77.87-2.01.23-.22.51-.28.69-.28.17 0 .34 0 .49 0 .16.01.37.02.56.27.19.24.63.82.69.88.06.06.11.13.02.25-.08.12-.12.2-.24.31-.12.11-.25.24-.35.32-.12.09-.25.19-.11.37.14.18.62.99 1.33 1.6.92.78 1.69 1.02 1.93 1.14.25.12.4.1.55-.06.16-.16.67-.78.84-.99.16-.2.32-.17.55-.1.18.05 1.16.55 1.36.65.2.1.33.16.38.25.05.09.05.52-.21 1.25Z"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        class="action-icon-btn"
                                        class:action-icon-btn-copied={copyStatus ===
                                            `link-${guest.id}`}
                                        onclick={() =>
                                            copyToClipboard(
                                                guest.link,
                                                `link-${guest.id}`,
                                            )}
                                        title="Copy Link"
                                    >
                                        {#if copyStatus === `link-${guest.id}`}
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.5"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                ><polyline
                                                    points="20 6 9 17 4 12"
                                                /></svg
                                            >
                                        {:else}
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                ><rect
                                                    x="9"
                                                    y="9"
                                                    width="13"
                                                    height="13"
                                                    rx="2"
                                                    ry="2"
                                                /><path
                                                    d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"
                                                /></svg
                                            >
                                        {/if}
                                    </button>

                                    <a
                                        href="/admin/guests/print-labels?id={guest.id}"
                                        target="_blank"
                                        class="action-icon-btn"
                                        title="Print Label"
                                    >
                                        <svg
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <polyline
                                                points="6 9 6 2 18 2 18 9"
                                            /><path
                                                d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
                                            /><rect
                                                x="6"
                                                y="14"
                                                width="12"
                                                height="8"
                                            />
                                        </svg>
                                    </a>

                                    <button
                                        class="action-icon-btn"
                                        onclick={(e) =>
                                            downloadQrCode(guest, e)}
                                        title="Download QR Code"
                                    >
                                        <svg
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path
                                                d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                            />
                                            <polyline
                                                points="7 10 12 15 17 10"
                                            />
                                            <line
                                                x1="12"
                                                y1="15"
                                                x2="12"
                                                y2="3"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        class="action-icon-btn"
                                        onclick={() => openEditModal(guest)}
                                        title="Edit Tamu"
                                    >
                                        <svg
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path
                                                d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
                                            />
                                            <path
                                                d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        class="action-icon-btn action-icon-btn-danger"
                                        onclick={() =>
                                            handleDelete(guest.id, guest.name)}
                                        title="Hapus Tamu"
                                    >
                                        <svg
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <polyline
                                                points="3 6 5 6 21 6"
                                            /><path
                                                d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    {:else}
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <svg
                                        width="40"
                                        height="40"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#D1D5DB"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
                                        /><circle cx="9" cy="7" r="4" />
                                        <path
                                            d="M23 21v-2a4 4 0 0 0-3-3.87"
                                        /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                    <p>Tidak ada tamu yang sesuai filter</p>
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        {#if guests.links && guests.links.length > 3}
            <div class="pagination">
                {#each guests.links as link}
                    <button
                        class="page-btn"
                        class:page-active={link.active}
                        class:page-disabled={!link.url}
                        onclick={() => link.url && router.get(link.url)}
                    >
                        {@html link.label}
                    </button>
                {/each}
            </div>
        {/if}
    </div>

    <GuestFormModal
        bind:open={formModalOpen}
        guest={editingGuest}
        {categories}
        onSuccess={handleFormSuccess}
    />
</AdminLayout>

<style>
    /* PAGE HEADER */
    .page-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 2px;
    }

    .page-sub {
        font-size: 13px;
        color: var(--text-secondary);
        margin: 0;
    }

    .toolbar-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* BUTTONS */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 14px;
        height: 36px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.15s;
        text-decoration: none;
        white-space: nowrap;
        font-family: inherit;
    }

    .btn-sm {
        height: 32px;
        padding: 0 12px;
        font-size: 12.5px;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }
    .btn-primary:hover {
        background: var(--primary-hover);
    }

    .btn-secondary {
        background: var(--card);
        color: var(--text-primary);
        border: 1px solid var(--border);
    }
    .btn-secondary:hover {
        background: var(--bg);
    }
    .btn-secondary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-ghost {
        background: var(--card);
        color: var(--text-secondary);
        border: 1px solid var(--border);
    }
    .btn-ghost:hover {
        background: var(--bg);
        color: var(--text-primary);
    }

    .btn-danger-ghost {
        background: var(--card);
        color: var(--danger);
        border: 1px solid #fecaca;
    }
    .btn-danger-ghost:hover {
        background: var(--danger-light);
    }

    /* IMPORT GROUP */
    .import-group {
        display: flex;
        align-items: center;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--card);
        overflow: hidden;
    }

    .file-picker-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 12px;
        height: 36px;
        font-size: 12.5px;
        color: var(--text-secondary);
        cursor: pointer;
        border-right: 1px solid var(--border);
        white-space: nowrap;
        font-family: inherit;
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .file-picker-btn:hover {
        background: var(--bg);
        color: var(--text-primary);
    }

    .import-group .btn {
        border-radius: 0;
        border: none;
        border-left: 1px solid var(--border);
        height: 36px;
    }

    /* STAT CARDS */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px 20px 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        box-shadow: var(--shadow);
        overflow: hidden;
        position: relative;
    }

    .stat-rsvp-multi {
        padding: 16px 18px 14px;
    }

    .stat-multi-label {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        display: block;
        margin-bottom: 12px;
    }

    .stat-multi-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-multi-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
        text-align: center;
    }

    .stat-multi-value {
        font-size: 20px;
        font-weight: 700;
        line-height: 1;
    }

    .stat-multi-sublabel {
        font-size: 10px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .stat-multi-divider {
        width: 1px;
        height: 28px;
        background: var(--border);
        flex-shrink: 0;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1;
    }

    .stat-label {
        font-size: 12px;
        color: var(--text-secondary);
        font-weight: 500;
        margin-top: 2px;
    }

    .stat-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        width: var(--pct, 0%);
        background: var(--clr, #2563eb);
        border-radius: 0 2px 0 0;
        transition: width 0.6s ease;
    }

    /* FILTER BAR */
    .filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .filter-tabs {
        display: flex;
        gap: 4px;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 3px;
    }

    .filter-tab {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 500;
        color: var(--text-secondary);
        background: none;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
        font-family: inherit;
    }

    .filter-tab.active {
        background: var(--primary);
        color: white;
    }

    .filter-tab:not(.active):hover {
        background: var(--bg);
        color: var(--text-primary);
    }

    .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .dot-green {
        background: var(--success);
    }
    .dot-gray {
        background: #d1d5db;
    }

    .search-group {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .search-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-icon {
        position: absolute;
        left: 10px;
        color: var(--text-muted);
        pointer-events: none;
    }

    .search-input {
        padding: 0 12px 0 32px;
        height: 36px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--card);
        font-size: 13px;
        color: var(--text-primary);
        width: 220px;
        font-family: inherit;
        transition: border-color 0.15s;
    }

    .search-input::placeholder {
        color: var(--text-muted);
    }
    .search-input:focus {
        outline: none;
        border-color: var(--primary);
    }

    /* TABLE */
    .table-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .table-scroll {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .data-table thead tr {
        background: var(--bg);
        border-bottom: 1px solid var(--border);
    }

    .data-table th {
        padding: 12px 16px;
        text-align: left;
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    .data-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
    }

    .data-table tbody tr:last-child td {
        border-bottom: none;
    }
    .data-table tbody tr:hover {
        background: #fafafa;
    }

    .qr-thumb {
        width: 40px;
        height: 40px;
        background: white;
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .qr-thumb :global(svg) {
        width: 100% !important;
        height: 100% !important;
    }

    .guest-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .guest-name {
        font-weight: 600;
        color: var(--text-primary);
    }

    .guest-meta {
        font-size: 12px;
        color: var(--text-secondary);
    }

    .link-pill {
        display: inline-block;
        background: var(--primary-light);
        color: var(--primary);
        padding: 3px 8px;
        border-radius: 5px;
        font-size: 11.5px;
        font-family: "SF Mono", "Fira Code", monospace;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .code-pill {
        display: inline-block;
        background: #f3f4f6;
        color: var(--text-secondary);
        padding: 3px 8px;
        border-radius: 5px;
        font-size: 11px;
        font-family: "SF Mono", "Fira Code", monospace;
        font-weight: 500;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .badge-success {
        background: var(--success-light);
        color: #065f46;
    }

    .badge-success .badge-dot {
        background: var(--success);
    }

    .badge-neutral {
        background: #f3f4f6;
        color: var(--text-secondary);
    }

    .badge-danger {
        background: var(--danger-light);
        color: #991b1b;
    }

    .badge-danger .badge-dot {
        background: var(--danger);
    }

    .message-preview {
        font-size: 10.5px;
        color: var(--text-muted);
        margin-top: 3px;
        font-style: italic;
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: help;
    }

    .badge-neutral .badge-dot {
        background: #9ca3af;
    }

    .checkin-time {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 3px;
    }

    /* ACTION BUTTONS - Icon Only, Compact */
    .action-group {
        display: flex;
        gap: 4px;
        justify-content: flex-end;
    }

    .action-icon-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        cursor: pointer;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-secondary);
        text-decoration: none;
        transition: all 0.15s;
        flex-shrink: 0;
    }

    .action-icon-btn:hover {
        background: var(--bg);
        color: var(--text-primary);
        border-color: #d1d5db;
    }

    .action-icon-btn-copied {
        background: var(--success-light);
        color: #065f46;
        border-color: #a7f3d0;
    }

    .action-icon-btn-whatsapp:hover {
        background: #ecfdf5;
        color: #10b981;
        border-color: #a7f3d0;
    }

    .action-icon-btn-danger {
        color: var(--danger);
    }

    .action-icon-btn-danger:hover {
        background: var(--danger-light);
        color: var(--danger);
        border-color: #fecaca;
    }
    /* EMPTY STATE */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 60px 20px;
        color: var(--text-muted);
        font-size: 13.5px;
    }

    .empty-state p {
        margin: 0;
    }

    /* PAGINATION */
    .pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 16px;
        border-top: 1px solid var(--border);
    }

    .page-btn {
        min-width: 36px;
        height: 36px;
        padding: 0 8px;
        border-radius: 8px;
        font-size: 13px;
        cursor: pointer;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-secondary);
        transition: all 0.15s;
        font-family: inherit;
    }

    .page-btn:hover:not(.page-disabled) {
        background: var(--bg);
        color: var(--text-primary);
    }

    .page-active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        font-weight: 600;
    }

    .page-disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .page-head {
            flex-direction: column;
            align-items: stretch;
        }
        .toolbar-right {
            flex-direction: column;
            align-items: stretch;
        }
        .import-group {
            width: 100%;
        }
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .search-group {
            width: 100%;
        }
        .search-input {
            width: 100%;
        }
        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .action-group {
            flex-wrap: wrap;
        }
    }

    .category-badge {
        display: inline-block;
        margin-left: 6px;
        padding: 1px 8px;
        background: #f3f4f6;
        color: var(--text-secondary);
        border-radius: 10px;
        font-size: 10.5px;
        font-weight: 500;
    }

    .category-filter {
        height: 36px;
        padding: 0 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--card);
        font-size: 12.5px;
        color: var(--text-primary);
        font-family: inherit;
        cursor: pointer;
    }

    .row-checkbox {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: var(--primary);
    }

    .row-selected {
        background: var(--primary-light) !important;
    }

    .selection-bar {
        position: sticky;
        top: 0;
        z-index: 30;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #111827;
        color: white;
        padding: 12px 20px;
        border-radius: var(--radius-sm);
        margin-bottom: 16px;
        box-shadow: var(--shadow-md);
    }

    .selection-count {
        font-size: 13px;
        font-weight: 600;
    }

    .selection-actions {
        display: flex;
        gap: 8px;
    }

    .selection-actions .btn-ghost {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.8);
    }

    .selection-actions .btn-ghost:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
</style>
