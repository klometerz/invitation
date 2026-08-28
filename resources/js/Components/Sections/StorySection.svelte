<script>
    import { onMount, onDestroy } from "svelte";

    let { stories = [] } = $props();

    let itemRefs = $state([]);
    let visibleItems = $state(new Set());
    let observer;

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
</script>

<div class="story-wrapper">
    <div class="story-header">
        <p class="subtitle">HOW IT ALL BEGAN</p>
        <h2 class="title">Our Love Story</h2>
        <p class="description">
            Setiap cerita cinta memiliki awalnya sendiri. Ini adalah perjalanan kami dari dua orang asing menjadi soulmates.
        </p>
    </div>

    {#if stories.length > 0}
        <div class="timeline">
            {#each stories as item, i}
                <div
                    class="timeline-row"
                    class:visible={visibleItems.has(i)}
                    bind:this={itemRefs[i]}
                    data-idx={i}
                >
                    <div class="timeline-marker">
                        <div class="marker-dot"></div>
                        {#if i < stories.length - 1}
                            <div class="marker-line"></div>
                        {/if}
                    </div>

                    <div class="glass-card">
                        {#if item.foto_url}
                            <div class="story-photo">
                                <img src={item.foto_url} alt={item.judul} />
                            </div>
                        {:else}
                            <div class="story-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="1.5">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </div>
                        {/if}
                        <div class="card-text">
                            <span class="year">{item.tahun}</span>
                            <h3 class="card-title">{item.judul}</h3>
                            <p class="desc">{item.deskripsi}</p>
                        </div>
                    </div>
                </div>
            {/each}
        </div>

        <div class="quote-block">
            <div class="quote-mark">"</div>
            <p class="quote-text">
                Love is not about how many days, months, or years you have been together.
                It's all about how much you love each other every single day.
            </p>
        </div>
    {:else}
        <p class="empty-text">Cerita akan segera hadir di sini.</p>
    {/if}
</div>

<style>
    .story-wrapper {
        width: 100%;
        max-width: 640px;
        margin: 0 auto;
    }

    .story-header {
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
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        font-weight: 300;
        margin-bottom: 1rem;
        color: white;
    }

    .description {
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
        line-height: 1.6;
        max-width: 480px;
        margin: 0 auto;
    }

    .timeline {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .timeline-row {
        display: flex;
        gap: 1.25rem;
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.7s ease, transform 0.7s ease;
    }

    .timeline-row.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .timeline-marker {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
        padding-top: 8px;
    }

    .marker-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #d4af37;
        box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
    }

    .marker-line {
        width: 2px;
        flex: 1;
        min-height: 60px;
        background: linear-gradient(to bottom, rgba(212,175,55,0.4), rgba(212,175,55,0.05));
        margin-top: 4px;
    }

    .glass-card {
        flex: 1;
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 16px;
    }

    .story-photo {
        width: 64px;
        height: 64px;
        border-radius: 10px;
        overflow: hidden;
        flex-shrink: 0;
        border: 1px solid rgba(212, 175, 55, 0.3);
    }

    .story-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .story-icon {
        width: 64px;
        height: 64px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(212, 175, 55, 0.08);
        border: 1px solid rgba(212, 175, 55, 0.2);
    }

    .card-text {
        flex: 1;
    }

    .year {
        color: #d4af37;
        font-weight: 600;
        letter-spacing: 0.2em;
        font-size: 11px;
        display: block;
        margin-bottom: 0.4rem;
    }

    .card-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.4rem;
        color: white;
        font-weight: 400;
        margin: 0 0 0.5rem 0;
    }

    .desc {
        color: rgba(255, 255, 255, 0.65);
        font-size: 13.5px;
        line-height: 1.6;
        margin: 0;
    }

    .quote-block {
        text-align: center;
        margin-top: 3rem;
        padding: 2rem 1rem;
        border-top: 1px solid rgba(212, 175, 55, 0.15);
    }

    .quote-mark {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: rgba(212, 175, 55, 0.3);
        line-height: 0.5;
        margin-bottom: 0.5rem;
    }

    .quote-text {
        font-family: 'Cormorant Garamond', serif;
        font-style: italic;
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.7;
        max-width: 480px;
        margin: 0 auto;
    }

    .empty-text {
        text-align: center;
        color: rgba(255, 255, 255, 0.4);
        font-size: 13px;
    }

    @media (max-width: 640px) {
        .title { font-size: 2rem; }
        .glass-card { padding: 1.2rem; gap: 0.85rem; }
        .story-photo, .story-icon { width: 48px; height: 48px; }
        .card-title { font-size: 1.2rem; }
    }
</style>