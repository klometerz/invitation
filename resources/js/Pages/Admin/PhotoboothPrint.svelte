<script>
    // P2-T4: the memory book cover and page headers use the presenter output.
    let {
        photos = [],
        total = 0,
        meta = {},
    } = $props();

    const coupleNames = $derived(meta?.coupleNames || "");
    const dateText = $derived(meta?.dateText || "");

    function handlePrint() {
        window.print();
    }

    // Bagi foto jadi grup 4 per halaman (2x2 grid)
    const photoPages = [];
    for (let i = 0; i < photos.length; i += 4) {
        photoPages.push(photos.slice(i, i + 4));
    }
</script>

<svelte:head>
    <title>Momen Photobooth - Print</title>
</svelte:head>

<div class="print-page">
    <!-- Toolbar (hilang saat print) -->
    <div class="toolbar no-print">
        <div class="toolbar-info">
            <span>{total} foto akan dicetak</span>
        </div>
        <button class="btn-print" onclick={handlePrint}>
            🖨 Print / Save as PDF
        </button>
    </div>

    <!-- COVER PAGE -->
    <div class="page cover-page">
        <div class="cover-content">
            <div class="cover-ornament-top">
                <div class="line"></div>
                <div class="diamond"></div>
                <div class="line"></div>
            </div>
            <p class="cover-subtitle">A COLLECTION OF MOMENTS</p>
            <h1 class="cover-title">Photobooth Memories</h1>
            {#if coupleNames}
                <div class="cover-names">{coupleNames}</div>
            {/if}
            {#if dateText}
                <p class="cover-date">{dateText}</p>
            {/if}
            <div class="cover-ornament-bottom">
                <div class="diamond"></div>
            </div>
            <p class="cover-count">{total} kenangan bersama tamu undangan</p>
        </div>
    </div>

    <!-- PHOTO PAGES -->
    {#each photoPages as pagePhotos, pageIndex}
        <div class="page photo-page">
            <div class="page-header">
                {#if coupleNames}
                    <span class="page-header-text">{coupleNames}</span>
                {/if}
                <span class="page-number"
                    >{String(pageIndex + 1).padStart(2, "0")}</span
                >
            </div>

            <div class="photo-grid">
                {#each pagePhotos as photo}
                    <div class="photo-card">
                        <div class="photo-frame">
                            <img
                                src={photo.image_path}
                                alt={photo.guest_name}
                                class="photo-img"
                            />
                        </div>
                        <div class="photo-caption">
                            <p class="caption-name">{photo.guest_name}</p>
                            {#if photo.message}
                                <p class="caption-message">"{photo.message}"</p>
                            {/if}
                            <p class="caption-date">{photo.created_at}</p>
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    {/each}

    {#if photos.length === 0}
        <div class="page empty-page">
            <p>Belum ada foto photobooth untuk dicetak.</p>
        </div>
    {/if}
</div>

<style>
    @import url("https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Inter:wght@300;400;500&display=swap");

    :global(*) {
        box-sizing: border-box;
    }

    :global(body) {
        margin: 0;
        font-family: "Inter", sans-serif;
        background: #e5e5e5;
    }

    .print-page {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px 0;
    }

    /* TOOLBAR */
    .toolbar {
        position: sticky;
        top: 0;
        z-index: 100;
        width: 100%;
        max-width: 850px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 20px;
        background: #111827;
        color: white;
        border-radius: 12px;
        margin-bottom: 24px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }

    .toolbar-info {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
    }

    .btn-print {
        background: #d4af37;
        color: #000;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
    }

    .btn-print:hover {
        background: #e5c14f;
    }

    /* PAGE (A4-ish ratio, 210mm x 297mm) */
    .page {
        width: 210mm;
        min-height: 297mm;
        background: #0a0a0a;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        padding: 20mm 15mm;
        position: relative;
        overflow: hidden;
    }

    /* COVER PAGE */
    .cover-page {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(160deg, #0a0a0a 0%, #1a1410 100%);
    }

    .cover-content {
        text-align: center;
        color: white;
    }

    .cover-ornament-top {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        margin-bottom: 2rem;
    }

    .cover-ornament-top .line {
        width: 60px;
        height: 1px;
        background: linear-gradient(
            to right,
            transparent,
            #d4af37,
            transparent
        );
    }

    .cover-ornament-top .diamond,
    .cover-ornament-bottom .diamond {
        width: 8px;
        height: 8px;
        background: #d4af37;
        transform: rotate(45deg);
    }

    .cover-subtitle {
        font-size: 11px;
        letter-spacing: 0.5em;
        color: #d4af37;
        margin-bottom: 1rem;
    }

    .cover-title {
        font-family: "Cormorant Garamond", serif;
        font-size: 3.2rem;
        font-weight: 300;
        margin: 0 0 1.5rem;
    }

    .cover-names {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.8rem;
        font-style: italic;
        color: #d4af37;
        margin-bottom: 0.5rem;
    }

    .cover-date {
        font-size: 12px;
        letter-spacing: 0.3em;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 2.5rem;
    }

    .cover-ornament-bottom {
        margin-bottom: 1.5rem;
    }

    .cover-count {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.4);
        font-style: italic;
        font-family: "Cormorant Garamond", serif;
    }

    /* PHOTO PAGES */
    .photo-page {
        background: #0a0a0a;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 10mm;
        border-bottom: 1px solid rgba(212, 175, 55, 0.25);
        margin-bottom: 10mm;
    }

    .page-header-text {
        font-family: "Cormorant Garamond", serif;
        font-style: italic;
        font-size: 13px;
        color: #d4af37;
        letter-spacing: 0.05em;
    }

    .page-number {
        font-size: 10px;
        color: rgba(255, 255, 255, 0.4);
        letter-spacing: 0.2em;
    }

    .photo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8mm;
    }

    .photo-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .photo-frame {
        position: relative;
        padding: 3mm;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 3mm;
    }

    .photo-img {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        border-radius: 1.5mm;
        display: block;
    }

    .photo-caption {
        text-align: center;
        margin-top: 3mm;
        padding: 0 2mm;
    }

    .caption-name {
        font-family: "Cormorant Garamond", serif;
        font-size: 13px;
        font-weight: 600;
        color: #d4af37;
        margin: 0 0 1.5mm;
    }

    .caption-message {
        font-family: "Cormorant Garamond", serif;
        font-style: italic;
        font-size: 10.5px;
        color: rgba(255, 255, 255, 0.65);
        line-height: 1.5;
        margin: 0 0 1.5mm;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .caption-date {
        font-size: 8px;
        color: rgba(255, 255, 255, 0.3);
        letter-spacing: 0.1em;
        margin: 0;
    }

    .empty-page {
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.4);
    }

    /* ===== PRINT STYLES ===== */
    @media print {
        .no-print {
            display: none !important;
        }

        :global(body) {
            background: white;
        }

        .print-page {
            padding: 0;
        }

        .page {
            width: 100%;
            min-height: 100vh;
            margin: 0;
            box-shadow: none;
            page-break-after: always;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page:last-child {
            page-break-after: auto;
        }
    }

    @page {
        size: A4;
        margin: 0;
    }
</style>
