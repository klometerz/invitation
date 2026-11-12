<script>
    import { onMount, onDestroy } from "svelte";

    let { verses = [], message = "" } = $props();

    let itemRefs = $state([]);
    let visibleItems = $state(new Set());
    let observer;

    const defaultVerses = [
        {
            arabic: "وَمِنْ آيَاتِهِ أَنْ خَلَقَ لَكُمْ مِّنْ أَنْفُسِكُمْ أَزْوَاجًا لِّتَسْكُنُوْآ إِلَيْهَا وَجَعَلَ بَيْنَكُمْ مَّوَدَّةً وَّرَحْمَةً",
            translation:
                "Dan di antara tanda-tanda kekuasaan-Nya ialah Dia menciptakan untukmu pasangan hidup dari jenismu sendiri, agar kamu merasa tenang bersamanya, dan Dia menjadikan di antaramu rasa kasih dan sayang.",
            reference: "QS. Ar-Rum : 21",
        },
    ];

    const displayVerses = verses.length > 0 ? verses : defaultVerses;
    const displayMessage =
        message ||
        "Dengan mengucap syukur kepada Allah SWT, kami memohon doa restu atas pernikahan yang akan kami langsungkan.";

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
            { threshold: 0.3 },
        );
        itemRefs.forEach((el) => el && observer.observe(el));
    });

    onDestroy(() => {
        if (observer) observer.disconnect();
    });
</script>

<div class="gratitude-wrapper">
    <div class="gratitude-header">
        <!-- <p class="subtitle">WITH GRATITUDE</p>
        <h2 class="title">Ucapan Syukur</h2> -->
        <p class="message-text">{displayMessage}</p>
    </div>

    <div class="verse-list">
        {#each displayVerses as verse, i}
            <div
                class="verse-row"
                class:visible={visibleItems.has(i)}
                bind:this={itemRefs[i]}
                data-idx={i}
            >
                <div class="glass-card">
                    <div class="ornament-top">
                        <div class="line"></div>
                        <div class="diamond"></div>
                        <div class="line"></div>
                    </div>

                    <p class="arabic-text">{verse.arabic}</p>

                    <p class="translation-text">"{verse.translation}"</p>

                    <p class="verse-reference">{verse.reference}</p>
                </div>
            </div>
        {/each}
    </div>
</div>

<style>
    .gratitude-wrapper {
        width: 100%;
        max-width: 640px;
        margin: 0 auto;
    }

    .gratitude-header {
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
        font-size: 2.4rem;
        font-weight: 300;
        color: white;
        margin-bottom: 1.5rem;
    }

    .message-text {
        color: rgba(255, 255, 255, 0.65);
        font-size: 14px;
        line-height: 1.8;
        max-width: 480px;
        margin: 0 auto;
        font-style: italic;
        font-family: "Cormorant Garamond", serif;
    }

    .verse-list {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .verse-row {
        opacity: 0;
        transform: translateY(30px);
        transition:
            opacity 0.8s ease,
            transform 0.8s ease;
    }

    .verse-row.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .glass-card {
        text-align: center;
        padding: 2.5rem 2rem;
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 20px;
    }

    .ornament-top {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-bottom: 1.75rem;
    }

    .ornament-top .line {
        width: 40px;
        height: 1px;
        background: linear-gradient(
            to right,
            transparent,
            rgba(212, 175, 55, 0.5),
            transparent
        );
    }

    .ornament-top .diamond {
        width: 6px;
        height: 6px;
        background: #d4af37;
        transform: rotate(45deg);
        flex-shrink: 0;
    }

    .arabic-text {
        font-family: "Amiri", "Scheherazade New", serif;
        font-size: 1.85rem;
        line-height: 2.1;
        color: #d4af37;
        direction: rtl;
        margin-bottom: 1.75rem;
        font-weight: 400;
    }

    .translation-text {
        font-family: "Cormorant Garamond", serif;
        font-style: italic;
        font-size: 15px;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.75);
        margin-bottom: 1.25rem;
    }

    .verse-reference {
        font-size: 11px;
        letter-spacing: 0.15em;
        color: rgba(255, 255, 255, 0.45);
        text-transform: uppercase;
    }

    @import url("https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap");

    @media (max-width: 640px) {
        .title {
            font-size: 2rem;
        }
        .arabic-text {
            font-size: 1.5rem;
            line-height: 1.9;
        }
        .glass-card {
            padding: 2rem 1.5rem;
        }
    }
</style>
