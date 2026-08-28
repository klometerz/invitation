<script>
    import { router } from "@inertiajs/svelte";
    import { fade } from "svelte/transition";
    import { onMount } from "svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";
    import Toast from "../../Components/Admin/Toast.svelte";
    import ConfirmModal from "../../Components/Admin/ConfirmModal.svelte";
    import { toast } from "../../Components/Admin/toast.js";

    let { images } = $props();

    let files = $state([]);
    let previews = $state([]);
    let isUploading = $state(false);
    let isDragging = $state(false);

    // Confirm modal
    let confirmOpen = $state(false);
    let deleteTargetId = $state(null);

    // Preview modal
    let previewSrc = $state(null);

    function handleFileSelect(e) {
        const selectedFiles = Array.from(e.target.files);
        addFiles(selectedFiles);
    }

    function addFiles(selectedFiles) {
        const imageFiles = selectedFiles.filter((f) =>
            f.type.startsWith("image/"),
        );
        files = [...files, ...imageFiles];
        imageFiles.forEach((file) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                previews = [...previews, e.target.result];
            };
            reader.readAsDataURL(file);
        });
    }

    function handleDrop(e) {
        e.preventDefault();
        isDragging = false;
        const dropped = Array.from(e.dataTransfer.files);
        addFiles(dropped);
    }

    function handleDragOver(e) {
        e.preventDefault();
        isDragging = true;
    }

    function handleDragLeave() {
        isDragging = false;
    }

    function uploadImages() {
        if (files.length === 0) {
            toast("Pilih foto terlebih dahulu.", "warning");
            return;
        }
        isUploading = true;
        const formData = new FormData();
        files.forEach((file, i) => {
            formData.append(`images[${i}]`, file);
        });
        router.post("/admin/gallery", formData, {
            onSuccess: () => {
                isUploading = false;
                files = [];
                previews = [];
                toast(`Foto berhasil diupload!`, "success");
            },
            onError: () => {
                isUploading = false;
                toast(
                    "Upload gagal. Periksa ukuran file (maks 2MB per foto).",
                    "error",
                );
            },
        });
    }

    function removePreview(index) {
        files = files.filter((_, i) => i !== index);
        previews = previews.filter((_, i) => i !== index);
    }

    function confirmDelete(id) {
        deleteTargetId = id;
        confirmOpen = true;
    }

    function doDelete() {
        if (deleteTargetId !== null) {
            router.delete(`/admin/gallery/${deleteTargetId}`, {
                onSuccess: () => toast("Foto berhasil dihapus.", "success"),
                onError: () => toast("Gagal menghapus foto.", "error"),
            });
        }
        deleteTargetId = null;
    }
</script>

<AdminLayout title="Gallery" breadcrumbs={["Gallery"]}>
    <Toast />
    <ConfirmModal
        bind:open={confirmOpen}
        title="Hapus Foto?"
        message="Foto ini akan dihapus permanen dari gallery dan tidak bisa dikembalikan."
        variant="danger"
        confirmLabel="Ya, Hapus"
        onConfirm={doDelete}
    />

    <!-- Preview Modal -->
    {#if previewSrc}
        <div
            class="preview-modal"
            role="button"
            tabindex="0"
            aria-label="Close preview"
            onclick={() => (previewSrc = null)}
            onkeydown={(e) => e.key === "Escape" && (previewSrc = null)}
        >
            <img src={previewSrc} alt="Preview" class="preview-modal-img" />
        </div>
    {/if}

    <!-- PAGE HEADER -->
    <div class="page-head">
        <div class="page-title-wrap">
            <h1 class="page-title">Photo Gallery</h1>
            <p class="page-sub">{images.length} foto tersimpan</p>
        </div>
        {#if files.length > 0}
            <button
                class="btn btn-primary"
                onclick={uploadImages}
                disabled={isUploading}
            >
                {#if isUploading}
                    <span class="spinner-sm"></span>
                    Uploading...
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
                    Upload {files.length} Foto
                {/if}
            </button>
        {/if}
    </div>

    <!-- UPLOAD ZONE -->
    <div
        class="upload-zone"
        class:drag-active={isDragging}
        ondragover={handleDragOver}
        ondragleave={handleDragLeave}
        ondrop={handleDrop}
        role="button"
        tabindex="0"
        aria-label="Upload area"
    >
        <input
            type="file"
            id="photo-upload"
            multiple
            accept="image/*"
            hidden
            onchange={handleFileSelect}
        />
        <div class="upload-zone-content">
            <div class="upload-icon">
                <svg
                    width="28"
                    height="28"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
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
            </div>
            <p class="upload-title">Drop foto di sini, atau</p>
            <label for="photo-upload" class="btn btn-secondary">
                Pilih dari komputer
            </label>
            <p class="upload-hint">PNG, JPG, WEBP · Maks 2MB per foto</p>
        </div>
    </div>

    <!-- UPLOAD QUEUE (selected but not yet uploaded) -->
    {#if previews.length > 0}
        <div class="section-card" transition:fade>
            <div class="section-card-header">
                <h2 class="section-card-title">
                    Antrian Upload ({files.length} foto)
                </h2>
                <button
                    class="btn-text-danger"
                    onclick={() => {
                        files = [];
                        previews = [];
                    }}
                >
                    Hapus Semua
                </button>
            </div>
            <div class="preview-grid">
                {#each previews as src, i}
                    <div class="preview-item">
                        <img {src} alt="Preview {i + 1}" />
                        <button
                            class="remove-preview-btn"
                            onclick={() => removePreview(i)}
                            aria-label="Remove"
                        >
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3"
                                stroke-linecap="round"
                            >
                                <line x1="18" y1="6" x2="6" y2="18" /><line
                                    x1="6"
                                    y1="6"
                                    x2="18"
                                    y2="18"
                                />
                            </svg>
                        </button>
                    </div>
                {/each}
            </div>
        </div>
    {/if}

    <!-- GALLERY GRID -->
    <div class="section-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Gallery ({images.length})</h2>
        </div>

        {#if images.length === 0}
            <div class="gallery-empty">
                <svg
                    width="48"
                    height="48"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#D1D5DB"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect
                        x="3"
                        y="3"
                        width="18"
                        height="18"
                        rx="2"
                        ry="2"
                    /><circle cx="8.5" cy="8.5" r="1.5" /><polyline
                        points="21 15 16 10 5 21"
                    />
                </svg>
                <p>Gallery masih kosong. Upload foto sekarang!</p>
            </div>
        {:else}
            <div class="gallery-grid">
                {#each images as img (img.id)}
                    <div class="gallery-item" transition:fade>
                        <div class="gallery-img-wrap">
                            <img
                                src={img.path}
                                alt={img.filename}
                                loading="lazy"
                            />
                            <div class="gallery-overlay">
                                <button
                                    class="overlay-btn"
                                    onclick={() => (previewSrc = img.path)}
                                    aria-label="Preview"
                                >
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                        /><circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                                <button
                                    class="overlay-btn overlay-btn-danger"
                                    onclick={() => confirmDelete(img.id)}
                                    aria-label="Delete"
                                >
                                    <svg
                                        width="18"
                                        height="18"
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
                                </button>
                            </div>
                        </div>
                        <p class="img-filename" title={img.filename}>
                            {img.filename}
                        </p>
                    </div>
                {/each}
            </div>
        {/if}
    </div>
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

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 16px;
        height: 38px;
        border-radius: var(--radius-sm);
        font-size: 13.5px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.15s;
        text-decoration: none;
        font-family: inherit;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }
    .btn-primary:hover:not(:disabled) {
        background: var(--primary-hover);
    }
    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-secondary {
        background: var(--card);
        color: var(--text-primary);
        border: 1px solid var(--border);
        cursor: pointer;
    }
    .btn-secondary:hover {
        background: var(--bg);
    }

    /* UPLOAD ZONE */
    .upload-zone {
        border: 2px dashed var(--border);
        border-radius: var(--radius);
        background: var(--card);
        padding: 40px 20px;
        margin-bottom: 20px;
        transition: all 0.2s;
        text-align: center;
    }

    .upload-zone.drag-active {
        border-color: var(--primary);
        background: var(--primary-light);
    }

    .upload-zone-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }

    .upload-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--bg);
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
    }

    .drag-active .upload-icon {
        background: var(--primary-light);
        border-color: var(--primary);
        color: var(--primary);
    }

    .upload-title {
        font-size: 14.5px;
        font-weight: 500;
        color: var(--text-primary);
        margin: 0;
    }

    .upload-hint {
        font-size: 12px;
        color: var(--text-muted);
        margin: 0;
    }

    /* SECTION CARD */
    .section-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 20px 24px;
        margin-bottom: 20px;
    }

    .section-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .section-card-title {
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .btn-text-danger {
        background: none;
        border: none;
        color: var(--danger);
        font-size: 12.5px;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 5px;
        font-family: inherit;
    }

    .btn-text-danger:hover {
        background: var(--danger-light);
    }

    /* PREVIEW QUEUE */
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 12px;
    }

    .preview-item {
        position: relative;
        aspect-ratio: 1/1;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--border);
    }

    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .remove-preview-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
    }

    .remove-preview-btn:hover {
        background: #dc2626;
    }

    /* SPINNER */
    .spinner-sm {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        flex-shrink: 0;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* GALLERY GRID */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
    }

    .gallery-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .gallery-img-wrap {
        position: relative;
        aspect-ratio: 1/1;
        border-radius: 10px;
        overflow: hidden;
        background: var(--bg);
        border: 1px solid var(--border);
    }

    .gallery-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .gallery-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        opacity: 0;
        transition: opacity 0.25s;
    }

    .gallery-img-wrap:hover .gallery-overlay {
        opacity: 1;
    }
    .gallery-img-wrap:hover img {
        transform: scale(1.06);
    }

    .overlay-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
        backdrop-filter: blur(4px);
    }

    .overlay-btn:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    .overlay-btn-danger:hover {
        background: rgba(239, 68, 68, 0.7);
        border-color: transparent;
    }

    .img-filename {
        font-size: 11.5px;
        color: var(--text-secondary);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: center;
    }

    /* GALLERY EMPTY */
    .gallery-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        padding: 60px 20px;
        color: var(--text-muted);
        font-size: 13.5px;
    }

    .gallery-empty p {
        margin: 0;
    }

    /* PREVIEW MODAL */
    .preview-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.85);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: zoom-out;
        padding: 24px;
    }

    .preview-modal-img {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        object-fit: contain;
    }

    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .page-head {
            flex-direction: column;
        }
    }

    @media (max-width: 768px) {
        /* Header jadi stack vertikal */
        .page-head {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 19px;
        }

        .page-head .btn-primary {
            width: 100%;
            justify-content: center;
        }

        /* Upload zone lebih ringkes */
        .upload-zone {
            padding: 28px 16px;
            margin-bottom: 16px;
        }

        .upload-icon {
            width: 48px;
            height: 48px;
        }

        .upload-title {
            font-size: 13.5px;
        }

        /* Section card padding dikecilin */
        .section-card {
            padding: 16px;
            margin-bottom: 16px;
        }

        .section-card-header {
            flex-wrap: wrap;
            gap: 8px;
        }

        .section-card-title {
            font-size: 13.5px;
        }

        /* Grid preview & gallery jadi lebih rapat */
        .preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
            gap: 8px;
        }

        .gallery-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .img-filename {
            font-size: 10.5px;
        }

        /* Overlay tombol di gallery: karena hover gak ada konsep di HP,
       tampilkan overlay selalu semi-visible biar tombolnya kepencet */
        .gallery-overlay {
            opacity: 1;
            background: rgba(0, 0, 0, 0.25);
        }

        .overlay-btn {
            width: 34px;
            height: 34px;
        }

        /* Preview modal: kurangi padding biar gambar lebih besar di layar kecil */
        .preview-modal {
            padding: 12px;
        }
    }
</style>
