<script>
    // P2-T2: the event title comes from the presenter; never a fixed couple.
    let { guests = [], eventTitle = "", isSingle = false } = $props();
    const printNow = () => window.print();
</script>

<!-- SCREEN-ONLY HEADER (hidden on print) -->
<div class="screen-header no-print">
    <div class="header-brand">
        <div class="brand-mark">
            <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="white"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                />
            </svg>
        </div>
        <div>
            <p class="brand-title">Print Label Undangan</p>
            <p class="brand-sub">
                T&J 103 · 4 Kolom × 8 Baris (32 per lembar) / sticker paper A4
            </p>
        </div>
    </div>

    <div class="header-stats">
        <div class="stat-chip">
            <span class="stat-chip-num">{guests.length}</span>
            <span class="stat-chip-label">Stiker</span>
        </div>
        <div class="stat-chip">
            <span class="stat-chip-num">{Math.ceil(guests.length / 32)}</span>
            <span class="stat-chip-label">Lembar A4</span>
        </div>
    </div>

    <div class="header-actions">
        <a href="/admin/guests" class="btn-back">
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
                <line x1="19" y1="12" x2="5" y2="12" /><polyline
                    points="12 19 5 12 12 5"
                />
            </svg>
            Kembali
        </a>
        <button onclick={printNow} class="btn-print">
            <svg
                width="15"
                height="15"
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
            Cetak Sekarang
        </button>
    </div>
</div>

<!-- PRINTABLE AREA -->
<div class="printable-area">
    <div class="sticker-grid">
        {#each guests as guest}
            <div class="sticker-unit">
                <div class="qr-container">
                    {@html guest.qrcode}
                </div>
                <div class="info">
                    <p class="guest-name">{guest.name}</p>
                    <div class="divider"></div>
                    {#if eventTitle}
                        <p class="event-label">{eventTitle}</p>
                    {/if}
                </div>
            </div>
        {/each}
    </div>
</div>

<style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap");

    :global(body) {
        background: #f1f5f9;
        margin: 0;
        font-family: "Inter", sans-serif;
    }

    /* SCREEN HEADER */
    .screen-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: #111827;
        padding: 0 32px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .header-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .brand-mark {
        width: 32px;
        height: 32px;
        background: #2563eb;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .brand-title {
        font-size: 14px;
        font-weight: 600;
        color: white;
        margin: 0;
    }

    .brand-sub {
        font-size: 11.5px;
        color: #6b7280;
        margin: 0;
    }

    .header-stats {
        display: flex;
        gap: 12px;
    }

    .stat-chip {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 4px 16px;
    }

    .stat-chip-num {
        font-size: 17px;
        font-weight: 700;
        color: white;
        line-height: 1.2;
    }

    .stat-chip-label {
        font-size: 10px;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 14px;
        height: 36px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.08);
        color: #d1d5db;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.15s;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.14);
        color: white;
    }

    .btn-print {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 18px;
        height: 36px;
        border-radius: 8px;
        background: #2563eb;
        color: white;
        font-size: 13.5px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        font-family: inherit;
        transition: background 0.15s;
    }

    .btn-print:hover {
        background: #1d4ed8;
    }

    /* A4 PAPER */
    .printable-area {
        width: 210mm;
        padding: 2mm;
        margin: 24px auto;
        background: white;
        min-height: 297mm;
        box-shadow: 0 0 40px rgba(0, 0, 0, 0.12);
        border-radius: 4px;
    }

    /* STICKER GRID — unchanged */
    .sticker-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        column-gap: 2mm;
        row-gap: 0;
    }

    .sticker-unit {
        width: 48mm;
        height: 24mm;
        border: 1px dashed #e5e7eb;
        display: flex;
        align-items: center;
        padding: 0 6mm;
        box-sizing: border-box;
        overflow: hidden;
    }

    /* PRINT MEDIA */
    @media print {
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        .no-print,
        .screen-header {
            display: none !important;
        }

        :global(body) {
            background: white;
            margin: 0;
        }

        .printable-area {
            width: 100%;
            margin: 0;
            padding: 0;
            box-shadow: none;
            border-radius: 0;
        }

        .sticker-unit {
            border: 0.5pt dashed #999;
            page-break-inside: avoid;
        }

        .qr-container :global(svg) {
            width: 15mm !important;
            height: 15mm !important;
        }
    }

    /* STICKER CONTENT */
    .qr-container {
        flex-shrink: 0;
        margin-right: 3mm;
    }

    .qr-container :global(svg) {
        width: 14mm;
        height: 14mm;
        display: block;
    }

    .info {
        text-align: left;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .guest-name {
        font-weight: 800;
        font-size: 11px;
        margin: 0;
        color: #000;
        text-transform: uppercase;
        line-height: 1.2;
    }

    .divider {
        width: 20px;
        height: 1px;
        background: #000;
        margin: 3px 0;
    }

    .event-label {
        font-size: 7px;
        color: #666;
        margin: 0;
        letter-spacing: 0.5px;
    }

    /* RESPONSIVE HEADER */
    @media (max-width: 768px) {
        .screen-header {
            flex-wrap: wrap;
            height: auto;
            padding: 12px 16px;
            gap: 12px;
        }

        .header-stats {
            display: none;
        }
    }
</style>
