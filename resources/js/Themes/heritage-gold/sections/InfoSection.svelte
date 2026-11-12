<script>
    import { onMount, onDestroy } from "svelte";

    let { colors = [], announcements = [] } = $props();

    let itemRefs = $state([]);
    let visibleItems = $state(new Set());
    let observer;

    // Default color palette kalau belum di-set dari admin
    const defaultColors = [
        { hex: "#0A0A0A", name: "Deep Black" },
        { hex: "#D4AF37", name: "Royal Gold" },
        { hex: "#4A3728", name: "Heritage Brown" },
        { hex: "#F5F0E6", name: "Ivory" },
    ];

    const defaultAnnouncements = [
        {
            icon: "invitation",
            title: "Bawa Undangan Fisik Anda",
            description: "Mohon membawa undangan fisik (kartu nama) yang telah kami bagikan. Undangan ini akan digunakan untuk mengisi buku tamu digital sekaligus menukarkan souvenir.",
        },
        {
            icon: "qrcode",
            title: "QR Code Check-In",
            description: "Pada pintu masuk acara, petugas akan memindai (scan) QR code yang tercetak pada undangan Anda untuk proses registrasi kehadiran.",
        },
    ];

    const displayColors = colors.length > 0 ? colors : defaultColors;
    const displayAnnouncements = announcements.length > 0 ? announcements : defaultAnnouncements;

    onMount(() => {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const idx = parseInt(entry.target.dataset.idx);
                        visibleItems = new Set([...visibleItems, idx]);
                    }
                });
            },
            { threshold: 0.3 }
        );
        itemRefs.forEach((el) => el && observer.observe(el));
    });

    onDestroy(() => {
        if (observer) observer.disconnect();
    });

    function getIcon(type) {
        if (type === "invitation") {
            return `<path d="M21 5H3a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1z"/><path d="M3 6l9 7 9-7"/>`;
        }
        return `<path d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>`;
    }
</script>

<div class="info-wrapper">
    <div class="info-header">
        <!-- <p class="subtitle">GOOD TO KNOW</p> -->
        <h2 class="title">Informasi Acara</h2>
        <p class="description">
            Beberapa hal penting yang perlu Anda ketahui sebelum menghadiri acara kami.
        </p>
    </div>

    <!-- Color Palette -->
    <div class="palette-block">
        <p class="block-label">Dress Code</p>
        <div class="swatches">
            {#each displayColors as color}
                <div class="swatch-item">
                    <div class="swatch" style="background-color: {color.hex}"></div>
                    <span class="swatch-name">{color.name}</span>
                    <span class="swatch-hex">{color.hex}</span>
                </div>
            {/each}
        </div>
    </div>

    <!-- Announcements -->
    <div class="announcement-list">
        {#each displayAnnouncements as item, i}
            <div
                class="announcement-row"
                class:visible={visibleItems.has(i)}
                bind:this={itemRefs[i]}
                data-idx={i}
            >
                <div class="glass-card">
                    <div class="announcement-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="1.5">
                            {@html getIcon(item.icon)}
                        </svg>
                    </div>
                    <div class="announcement-text">
                        <h3 class="announcement-title">{item.title}</h3>
                        <p class="announcement-desc">{item.description}</p>
                    </div>
                </div>
            </div>
        {/each}
    </div>
</div>

<style>
    .info-wrapper {
        width: 100%;
        max-width: 640px;
        margin: 0 auto;
    }

    .info-header {
         font-family: "Cormorant Garamond", serif;
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .subtitle {
        letter-spacing: 0.5em;
        font-size: 10px;
        color: #d4af37;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }

    .title {
        font-family: "Cormorant Garamond", serif;
        font-size: 2.2rem;
        font-weight: 300;
        color: white;
        margin-bottom: 1rem;
    }

    .description {
         font-family: "Cormorant Garamond", serif;
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
        line-height: 1.6;
        max-width: 480px;
        margin: 0 auto;
    }

    /* Color Palette */
    .palette-block {
         font-family: "Cormorant Garamond", serif;
        margin-bottom: 3rem;
    }

    .block-label {
        
        text-align: center;
        font-size: 11px;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 1.25rem;
    }

    .swatches {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .swatch-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
    }

    .swatch {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        border: 1px solid rgba(212, 175, 55, 0.3);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .swatch-name {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.7);
    }

    .swatch-hex {
        font-size: 10px;
        color: rgba(255, 255, 255, 0.4);
        font-family: monospace;
    }

    /* Announcements */
    .announcement-list {
         font-family: "Cormorant Garamond", serif;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .announcement-row {
        opacity: 0;
        transform: translateY(25px);
        transition: opacity 0.7s ease, transform 0.7s ease;
    }

    .announcement-row.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .glass-card {
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 16px;
    }

    .announcement-icon {
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(212, 175, 55, 0.08);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 10px;
    }

    .announcement-text {
        flex: 1;
    }

    .announcement-title {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.2rem;
        color: white;
        font-weight: 400;
        margin: 0 0 0.5rem 0;
    }

    .announcement-desc {
        color: rgba(255, 255, 255, 0.6);
        font-size: 13px;
        line-height: 1.6;
        margin: 0;
    }

    @media (max-width: 640px) {
        .title { font-size: 1.8rem; }
        .swatches { gap: 1rem; }
        .swatch { width: 44px; height: 44px; }
        .glass-card { flex-direction: column; text-align: center; padding: 1.25rem; }
        .announcement-icon { margin: 0 auto; }
    }
</style>