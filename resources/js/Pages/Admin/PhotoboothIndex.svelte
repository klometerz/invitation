<script>
    import { router } from "@inertiajs/svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";
    import Toast from "../../Components/Admin/Toast.svelte";
    import ConfirmModal from "../../Components/Admin/ConfirmModal.svelte";
    import { toast } from "../../Components/Admin/toast.js";

    let { photos, total, filters } = $props();
    let searchQuery = $state(filters?.search || "");
    let selectedPhoto = $state(null); // untuk lightbox

    let confirmOpen = $state(false);
    let confirmConfig = $state({ title: "", message: "", onConfirm: () => {} });

    function handleSearch() {
        router.get(
            "/admin/photobooth",
            { search: searchQuery || undefined },
            { preserveState: true },
        );
    }

    function openLightbox(photo) {
        selectedPhoto = photo;
    }

    function closeLightbox() {
        selectedPhoto = null;
    }

    function handleDelete(id, guestName) {
        confirmConfig = {
            title: "Hapus Foto?",
            message: `Foto dari "${guestName}" akan dihapus permanen.`,
            onConfirm: () => {
                router.delete(`/admin/photobooth/${id}`, {
                    onSuccess: () => {
                        toast("Foto berhasil dihapus.", "success");
                        closeLightbox();
                    },
                    onError: () => toast("Gagal menghapus foto.", "error"),
                });
            },
        };
        confirmOpen = true;
    }

    function downloadPhoto(photo) {
        const link = document.createElement("a");
        link.href = photo.image_path;
        link.download = `photobooth-${photo.guest_name.replace(/\s+/g, "-")}.jpg`;
        link.target = "_blank";
        link.click();
    }
</script>

<AdminLayout title="Photobooth" breadcrumbs={["Photobooth"]}>
    <Toast />
    <ConfirmModal
        bind:open={confirmOpen}
        title={confirmConfig.title}
        message={confirmConfig.message}
        variant="danger"
        confirmLabel="Ya, Hapus"
        onConfirm={confirmConfig.onConfirm}
    />

    <div class="page-head">
        <div class="page-title-wrap">
            <h1 class="page-title">Virtual Photobooth</h1>
            <p class="page-sub">{total} foto tersimpan dari tamu undangan</p>
        </div>
        <div class="toolbar-actions">
            <a
                href="/admin/photobooth/print"
                target="_blank"
                class="btn btn-secondary"
            >
                🖨 Print / PDF
            </a>
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

    {#if photos.data.length === 0}
        <div class="empty-state">
            <svg
                width="48"
                height="48"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#D1D5DB"
                stroke-width="1.5"
            >
                <path
                    d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"
                />
                <circle cx="12" cy="13" r="3" />
            </svg>
            <p>Belum ada foto dari photobooth</p>
        </div>
    {:else}
        <div class="photo-grid">
            {#each photos.data as photo}
                <div class="photo-card">
                    <button
                        class="photo-thumb-btn"
                        onclick={() => openLightbox(photo)}
                    >
                        <img
                            src={photo.image_path}
                            alt="Photobooth {photo.guest_name}"
                            class="photo-thumb"
                        />
                    </button>
                    <div class="photo-info">
                        <p class="photo-guest-name">{photo.guest_name}</p>
                        {#if photo.message}
                            <p class="photo-message" title={photo.message}>
                                💬 {photo.message}
                            </p>
                        {/if}
                        <p class="photo-time">{photo.created_at}</p>
                    </div>
                    <div class="photo-actions">
                        <button
                            class="icon-btn"
                            onclick={() => downloadPhoto(photo)}
                            title="Download"
                        >
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
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
                        </button>
                        <button
                            class="icon-btn icon-btn-danger"
                            onclick={() =>
                                handleDelete(photo.id, photo.guest_name)}
                            title="Hapus"
                        >
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <polyline points="3 6 5 6 21 6" /><path
                                    d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            {/each}
        </div>

        {#if photos.links && photos.links.length > 3}
            <div class="pagination">
                {#each photos.links as link}
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
    {/if}

    <!-- LIGHTBOX -->
    {#if selectedPhoto}
        <div class="lightbox-backdrop" onclick={closeLightbox}>
            <div class="lightbox-box" onclick={(e) => e.stopPropagation()}>
                <button class="lightbox-close" onclick={closeLightbox}>
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <line x1="18" y1="6" x2="6" y2="18" /><line
                            x1="6"
                            y1="6"
                            x2="18"
                            y2="18"
                        />
                    </svg>
                </button>
                <img
                    src={selectedPhoto.image_path}
                    alt="Photobooth {selectedPhoto.guest_name}"
                    class="lightbox-image"
                />
                <div class="lightbox-details">
                    <p class="lightbox-name">{selectedPhoto.guest_name}</p>
                    {#if selectedPhoto.message}
                        <p class="lightbox-message">
                            "{selectedPhoto.message}"
                        </p>
                    {/if}
                    <p class="lightbox-time">{selectedPhoto.created_at}</p>
                    <div class="lightbox-actions">
                        <button
                            class="btn btn-secondary"
                            onclick={() => downloadPhoto(selectedPhoto)}
                        >
                            ⬇ Download
                        </button>
                        <button
                            class="btn btn-danger-ghost"
                            onclick={() =>
                                handleDelete(
                                    selectedPhoto.id,
                                    selectedPhoto.guest_name,
                                )}
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    {/if}
</AdminLayout>

<style>
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
    }

    .search-input:focus {
        outline: none;
        border-color: var(--primary);
    }

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

    .btn-danger-ghost {
        background: var(--card);
        color: var(--danger);
        border: 1px solid #fecaca;
    }
    .btn-danger-ghost:hover {
        background: var(--danger-light);
    }

    /* GRID */
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }

    .photo-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    .photo-thumb-btn {
        display: block;
        width: 100%;
        padding: 0;
        border: none;
        cursor: pointer;
        background: #f3f4f6;
    }

    .photo-thumb {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        display: block;
    }

    .photo-info {
        padding: 12px 14px 8px;
    }

    .photo-guest-name {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 4px;
    }

    .photo-message {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 0 0 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-style: italic;
    }

    .photo-time {
        font-size: 11px;
        color: var(--text-muted);
        margin: 0;
    }

    .photo-actions {
        display: flex;
        gap: 6px;
        padding: 0 14px 12px;
    }

    .icon-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 30px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-secondary);
        cursor: pointer;
    }

    .icon-btn:hover {
        background: var(--bg);
        color: var(--text-primary);
    }

    .icon-btn-danger {
        color: var(--danger);
        border-color: #fecaca;
    }

    .icon-btn-danger:hover {
        background: var(--danger-light);
    }

    /* EMPTY STATE */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        padding: 80px 20px;
        color: var(--text-muted);
        font-size: 14px;
    }

    /* PAGINATION */
    .pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 24px 0;
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
        font-family: inherit;
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

    /* LIGHTBOX */
    .lightbox-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.75);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 200;
        padding: 20px;
    }

    .lightbox-box {
        background: white;
        border-radius: 16px;
        max-width: 420px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }

    .lightbox-close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.6);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
    }

    .lightbox-image {
        width: 100%;
        display: block;
    }

    .lightbox-details {
        padding: 20px;
    }

    .lightbox-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 8px;
    }

    .lightbox-message {
        font-size: 13.5px;
        color: var(--text-secondary);
        font-style: italic;
        line-height: 1.6;
        margin: 0 0 8px;
    }

    .lightbox-time {
        font-size: 12px;
        color: var(--text-muted);
        margin: 0 0 16px;
    }

    .lightbox-actions {
        display: flex;
        gap: 8px;
    }

    .lightbox-actions .btn {
        flex: 1;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .page-head {
            flex-direction: column;
            align-items: stretch;
        }
        .search-group {
            width: 100%;
        }
        .search-input {
            width: 100%;
        }
        .photo-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .toolbar-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
</style>
