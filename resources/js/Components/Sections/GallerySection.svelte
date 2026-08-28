<script>
    import { onMount, onDestroy } from "svelte";
    import { fade, scale } from "svelte/transition";

    let { galleries = [] } = $props();

    let itemRefs = $state([]);
    let visibleItems = $state(new Set());
    let observer;
    let selectedImage = $state(null);

    const photos = galleries.map((g) => ({
        id: g.id,
        url: g.path,
    }));

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
            { threshold: 0.25 },
        );
        itemRefs.forEach((el) => el && observer.observe(el));
    });

    onDestroy(() => {
        if (observer) observer.disconnect();
    });

    function openLightbox(photo) {
        selectedImage = photo;
    }

    function closeLightbox() {
        selectedImage = null;
    }
</script>

<div class="gallery-wrapper">
    <div class="gallery-header">
        <p class="subtitle">CAPTURED MEMORIES</p>
        <h2 class="title">Our Moments</h2>
        <p class="description">
            Setiap foto menceritakan kisah. Ini adalah momen-momen berharga yang
            kami bagikan bersama.
        </p>
    </div>

    {#if photos.length > 0}
        <div class="photo-list">
            {#each photos as photo, i}
                <button
                    class="photo-row"
                    class:visible={visibleItems.has(i)}
                    bind:this={itemRefs[i]}
                    data-idx={i}
                    onclick={() => openLightbox(photo)}
                >
                    <div class="frame">
                        <div class="corner corner-tl"></div>
                        <div class="corner corner-br"></div>
                        <div class="photo-wrapper">
                            <img
                                src={photo.url}
                                alt="Wedding moment"
                                class="photo"
                            />
                            <div class="photo-overlay"></div>
                        </div>
                    </div>
                </button>
            {/each}
        </div>
    {:else}
        <p class="empty-text">Galeri foto akan segera hadir.</p>
    {/if}

    {#if selectedImage}
        <div
            class="lightbox"
            onclick={closeLightbox}
            transition:fade={{ duration: 300 }}
        >
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
            <div class="lightbox-content" onclick={(e) => e.stopPropagation()}>
                <img
                    src={selectedImage.url}
                    alt="Wedding moment"
                    class="lightbox-image"
                    transition:scale={{ duration: 400, start: 0.9 }}
                />
            </div>
        </div>
    {/if}
</div>

<style>
    .gallery-wrapper {
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
    }

    .gallery-header {
        text-align: center;
        margin-bottom: 3rem;
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
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
        line-height: 1.6;
    }

    .photo-list {
        display: flex;
        flex-direction: column;
        gap: 2.5rem;
    }

    .photo-row {
        background: transparent;
        border: none;
        padding: 0;
        cursor: pointer;
        opacity: 0;
        transform: translateY(40px) scale(0.95);
        transition:
            opacity 0.7s ease,
            transform 0.7s ease;
        width: 100%;
    }

    .photo-row.visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    /* ===== Elegant Gold Frame ===== */
    .frame {
        position: relative;
        padding: 10px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.35);
        border-radius: 16px;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow:
            0 15px 35px rgba(0, 0, 0, 0.4),
            inset 0 0 0 1px rgba(212, 175, 55, 0.08);
        transition: border-color 0.3s ease;
    }

    .photo-row:hover .frame {
        border-color: rgba(212, 175, 55, 0.6);
    }

    .corner {
        position: absolute;
        width: 22px;
        height: 22px;
        border: 1.5px solid #d4af37;
        opacity: 0.75;
        z-index: 2;
    }

    .corner-tl {
        top: -1px;
        left: -1px;
        border-right: none;
        border-bottom: none;
        border-radius: 6px 0 0 0;
    }

    .corner-br {
        bottom: -1px;
        right: -1px;
        border-left: none;
        border-top: none;
        border-radius: 0 0 6px 0;
    }

    .photo-wrapper {
        position: relative;
        width: 100%;
        padding-bottom: 100%;
        overflow: hidden;
        border-radius: 8px;
        background: #1a1a1a;
    }

    .photo {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .photo-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            to top,
            rgba(10, 10, 10, 0.25) 0%,
            transparent 30%
        );
        pointer-events: none;
    }

    .empty-text {
        text-align: center;
        color: rgba(255, 255, 255, 0.4);
        font-size: 13px;
    }

    /* Lightbox */
    .lightbox {
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(0, 0, 0, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        cursor: pointer;
    }

    .lightbox-close {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        z-index: 10001;
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(212, 175, 55, 0.3);
        color: white;
        cursor: pointer;
        border-radius: 8px;
    }

    .lightbox-content {
        max-width: 90%;
        max-height: 90%;
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: default;
    }

    .lightbox-image {
        max-width: 100%;
        max-height: 85vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    @import url("https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400&display=swap");

    @media (max-width: 480px) {
        .title {
            font-size: 1.8rem;
        }
        .photo-list {
            gap: 2rem;
        }
        .corner {
            width: 18px;
            height: 18px;
        }
    }
</style>
