<script>
    import { onMount, onDestroy } from "svelte";
    import { fade, scale } from "svelte/transition";

    let { 
        scrollProgress = $bindable(0), 
        isLocked = $bindable(false),
        galleries = [] // Real data from Laravel backend
    } = $props();

    let items = $state([]);
    let viewport;
    let selectedImage = $state(null);

    // Map backend data structure to what the component needs
    // Backend sends: { id, filename, path, order }
    // path already includes '/storage/...' prefix
    const allPhotos = galleries.map(g => ({
        id: g.id,
        url: g.path,
        caption: g.filename?.replace(/\.[^/.]+$/, '') || '' // strip extension for cleaner caption
    }));

    // Group photos into grids of 3
    const photoGrids = [];
    for (let i = 0; i < allPhotos.length; i += 3) {
        photoGrids.push(allPhotos.slice(i, i + 3));
    }

    function update3D() {
        if (!viewport) return;

        const viewportRect = viewport.getBoundingClientRect();
        const viewportCenter = viewportRect.top + viewportRect.height / 2;

        const progress = (viewport.scrollTop / (viewport.scrollHeight - viewport.clientHeight)) * 100;
        scrollProgress = progress;
        isLocked = progress < 98;

        items.forEach((el, i) => {
            if (!el) return;

            const rect = el.getBoundingClientRect();
            const elementCenter = rect.top + rect.height / 2;
            const distance = elementCenter - viewportCenter;
            const normalized = distance / (viewportRect.height / 2);

            const z = Math.pow(1 - Math.abs(normalized), 2) * 600;
            const scale = 0.75 + Math.pow(1 - Math.abs(normalized), 2) * 0.25;
            const opacity = 1 - Math.abs(normalized) * 1.8;
            const blur = Math.abs(normalized) * 12;

            el.style.transform = `translateZ(${z}px) scale(${scale})`;
            el.style.opacity = opacity > 0 ? opacity : 0;
            el.style.filter = `blur(${blur}px)`;
            el.style.zIndex = Math.round((1 - Math.abs(normalized)) * 100);
        });
    }

    onMount(() => {
        update3D();
        viewport.addEventListener("scroll", update3D, { passive: true });
    });

    onDestroy(() => {
        if (viewport) viewport.removeEventListener("scroll", update3D);
    });

    function openLightbox(photo) {
        selectedImage = photo;
    }

    function closeLightbox() {
        selectedImage = null;
    }
</script>

<div class="gallery-viewport" bind:this={viewport}>
    <!-- Header Section -->
    <section class="snap-section">
        <div class="header-content" style="opacity: {1 - (scrollProgress * 5) / 100}">
            <p class="subtitle">CAPTURED MEMORIES</p>
            <h1 class="title">Our Moments</h1>
            <p class="description">
                Setiap foto menceritakan kisah. Ini adalah momen-momen berharga yang kami bagikan bersama.
            </p>
            <div class="scroll-instruction">
                <div class="line"></div>
                <span>SCROLL DOWN</span>
            </div>
        </div>
    </section>

    <!-- Photo Grids -->
    {#each photoGrids as grid, gridIndex}
        <section class="snap-section">
            <div class="gallery-item" bind:this={items[gridIndex]}>
                <div class="photo-grid">
                    {#each grid as photo}
                        <button class="polaroid" on:click={() => openLightbox(photo)}>
                            <div class="polaroid-frame">
                                <div class="photo-wrapper">
                                    <img src={photo.url} alt={photo.caption} class="photo" />
                                </div>
                                <div class="polaroid-caption">{photo.caption}</div>
                            </div>
                        </button>
                    {/each}
                </div>
            </div>
        </section>
    {/each}

    <!-- Lightbox Modal -->
    {#if selectedImage}
        <div class="lightbox" on:click={closeLightbox} transition:fade={{ duration: 300 }}>
            <button class="lightbox-close" on:click={closeLightbox}>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="lightbox-content" on:click={(e) => e.stopPropagation()}>
                <img 
                    src={selectedImage.url} 
                    alt={selectedImage.caption}
                    class="lightbox-image"
                    transition:scale={{ duration: 400, start: 0.9 }}
                />
                <p class="lightbox-caption">{selectedImage.caption}</p>
            </div>
        </div>
    {/if}
</div>

<style>
    .gallery-viewport {
        position: absolute;
        inset: 0;
        overflow-y: auto;
        overflow-x: hidden;
        scroll-snap-type: y mandatory;
        perspective: 1200px;
        perspective-origin: center center;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    
    .gallery-viewport::-webkit-scrollbar {
        display: none;
    }

    .snap-section {
        height: 100vh;
        width: 100%;
        scroll-snap-align: center;
        scroll-snap-stop: always;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    /* Header Section */
    .header-content {
        text-align: center;
        color: white;
        padding: 2rem;
        transition: opacity 0.3s ease;
        max-width: 600px;
    }

    .subtitle {
        letter-spacing: 0.5em;
        font-size: 10px;
        opacity: 0.5;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }

    .title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3rem;
        font-weight: 300;
        margin-bottom: 1.5rem;
        color: rgba(255, 255, 255, 0.95);
    }

    .description {
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 3rem;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    .scroll-instruction {
        margin-top: 3rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.8rem;
        opacity: 0.3;
    }

    .scroll-instruction .line {
        width: 1px;
        height: 50px;
        background: white;
        animation: grow 2.5s infinite;
    }

    .scroll-instruction span {
        font-size: 8px;
        letter-spacing: 0.3em;
    }

    @keyframes grow {
        0% {
            transform: scaleY(0);
            transform-origin: top;
        }
        50% {
            transform: scaleY(1);
            transform-origin: top;
        }
        51% {
            transform: scaleY(1);
            transform-origin: bottom;
        }
        100% {
            transform: scaleY(0);
            transform-origin: bottom;
        }
    }

    /* Gallery Items */
    .gallery-item {
        width: 100%;
        display: flex;
        justify-content: center;
        padding: 0 2rem;
        transform-style: preserve-3d;
        will-change: transform, opacity, filter;
        transition:
            transform 0.8s cubic-bezier(0.23, 1, 0.32, 1),
            opacity 0.8s ease,
            filter 0.8s ease;
    }

    .photo-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        width: 100%;
        max-width: 900px;
    }

    /* Polaroid Cards */
    .polaroid {
        background: transparent;
        border: none;
        padding: 0;
        cursor: pointer;
        transition: transform 0.3s ease;
        width: 100%;
    }

    .polaroid:hover {
        transform: translateY(-8px) rotate(2deg);
    }

    .polaroid:nth-child(even):hover {
        transform: translateY(-8px) rotate(-2deg);
    }

    .polaroid-frame {
        background: #fff;
        padding: 0.75rem;
        padding-bottom: 2.5rem;
        box-shadow: 
            0 4px 6px rgba(0, 0, 0, 0.3),
            0 10px 20px rgba(0, 0, 0, 0.2);
        transition: box-shadow 0.3s ease;
    }

    .polaroid:hover .polaroid-frame {
        box-shadow: 
            0 8px 12px rgba(0, 0, 0, 0.4),
            0 16px 32px rgba(0, 0, 0, 0.3);
    }

    .photo-wrapper {
        position: relative;
        width: 100%;
        padding-bottom: 125%;
        overflow: hidden;
        background: #f5f5f5;
    }

    .photo {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: grayscale(100%);
        transition: filter 0.3s ease;
    }

    .polaroid:hover .photo {
        filter: grayscale(0%);
    }

    .polaroid-caption {
        text-align: center;
        margin-top: 0.75rem;
        font-family: 'Caveat', cursive;
        font-size: 14px;
        color: #333;
        letter-spacing: 0.05em;
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
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        cursor: pointer;
        transition: all 0.3s;
    }

    .lightbox-close:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.4);
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
        max-height: 80vh;
        object-fit: contain;
        filter: grayscale(100%);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .lightbox-caption {
        color: rgba(255, 255, 255, 0.8);
        font-size: 16px;
        margin-top: 1.5rem;
        text-align: center;
        letter-spacing: 0.1em;
    }

    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Inter:wght@300;400;600&family=Caveat:wght@400;700&display=swap');

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .title {
            font-size: 2.5rem;
        }

        .photo-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .gallery-item {
            padding: 0 1rem;
        }
    }

    @media (max-width: 480px) {
        .title {
            font-size: 2rem;
        }

        .photo-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .polaroid-frame {
            padding: 0.5rem;
            padding-bottom: 2rem;
        }

        .polaroid-caption {
            font-size: 13px;
        }
    }
</style>