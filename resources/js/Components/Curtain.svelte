<script>
    // P2-T2: the hashtag is client content, never a platform default.
    let {
        isOpen = $bindable(false),
        hashtag = "",
        onComplete = () => {},
    } = $props();

    let isVisible = $state(true);
    let isOpening = $state(false);

    function handleOpenClick() {
        if (isOpening) return;
        isOpening = true;

        setTimeout(() => {
            isVisible = false;
            isOpen = true;
            onComplete();
        }, 1600);
    }
</script>

{#if isVisible}
    <div class="curtain-container" class:opening={isOpening}>
        <!-- BACKGROUND: statis -->
        <div class="curtain-bg"></div>

        <!-- GUNUNGAN: 2 layer identik, cuma beda clip-path -->
        <div class="gunungan-stage" class:opening={isOpening} class:idle={!isOpening}>
            <div class="gunungan-half gunungan-left">
                <img src="/images/gunungan-solo.png" alt="" class="gunungan-img" />
            </div>
            <div class="gunungan-half gunungan-right">
                <img src="/images/gunungan-solo.png" alt="" class="gunungan-img" />
            </div>
        </div>

        <!-- Center Content -->
        {#if !isOpening}
    <div class="top-content">
        <div class="ornament-top">
            <div class="ornament-line"></div>
            <div class="ornament-diamond"></div>
            <div class="ornament-line"></div>
        </div>
        <p class="kingdom-label-jawa">ꦈꦤ꧀ꦝꦔꦤ꧀ꦥꦼꦂꦤꦶꦏꦲꦤ꧀</p>
        <!-- <p class="initials">M&amp;K</p> -->
    </div>

    <div class="bottom-content">
        <button class="open-button" onclick={handleOpenClick}>
            <span class="open-text">OPEN</span>
            <div class="button-ornament"></div>
        </button>
        <p class="tap-hint">Tap to enter</p>
        {#if hashtag}
            <p class="hashtag-label">{hashtag}</p>
        {/if}
    </div>
{/if}
    </div>
{/if}

<style>
    .curtain-container {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: #0a0a0a;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: curtainFadeIn 600ms ease-out;
    transition: opacity 700ms ease-in;
    transition-delay: 900ms; /* mulai fade setelah gunungan hampir terbang keluar */
}

.curtain-container.opening {
    opacity: 0;
}

    @keyframes curtainFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .curtain-bg {
        position: absolute;
        inset: 0;
        z-index: 1;
        background-color: #0a0a0a;
        background-image: url('/images/bg-mega-mendung.png');
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
         opacity: 0.9;
          /* filter: brightness(0.6); */
    }

    /* STAGE: area tempat gunungan tampil, ukuran mengikuti proporsi asli file */
    .gunungan-stage {
        position: relative;
        z-index: 10;
        width: min(60vw, 320px);
        height: min(80vh, 600px);
    }

    .gunungan-stage.idle {
        animation: floatGunungan 3.2s ease-in-out infinite;
    }

    @keyframes floatGunungan {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-14px); }
    }

    /* Tiap half: ukuran & posisi SAMA PERSIS (menutupi satu sama lain), cuma beda clip-path */
    .gunungan-half {
        position: absolute;
        inset: 0;
        transition: transform 1.4s cubic-bezier(0.76, 0, 0.24, 1);
    }

    .gunungan-left {
        clip-path: inset(0 50% 0 0); /* tampilkan cuma separuh kiri */
    }

    .gunungan-right {
        clip-path: inset(0 0 0 50%); /* tampilkan cuma separuh kanan */
    }

    /* Gambar: object-fit:contain menjaga rasio asli, tidak gepeng */
    .gunungan-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        opacity: 0.82;
        filter: brightness(0.8);
        display: block;
    }

    /* Saat OPEN diklik: kebelah + terbang ke atas */
    /* .gunungan-stage.opening .gunungan-left {
        transform: translate3d(-55%, -140vh, 0);
    }

    .gunungan-stage.opening .gunungan-right {
        transform: translate3d(55%, -140vh, 0);
    } */

    /* .gunungan-stage.opening {
    transform: translate3d(0, -140vh, 0);
    transition: transform 1.4s cubic-bezier(0.76, 0, 0.24, 1);
} */

/* .gunungan-stage.opening {
    transform: translate3d(0, -140vh, 0);
    transition: transform 1.4s cubic-bezier(0.76, 0, 0.24, 1);
} */

.gunungan-stage.opening {
    animation: flyAwayAnticipate 1.5s cubic-bezier(0.76, 0, 0.24, 1) forwards;
}

@keyframes flyAwayAnticipate {
    0% { transform: translateY(0) scale(1); opacity: 1; }
    15% { transform: translateY(10px) scale(1.05); opacity: 1; }
    100% { transform: translateY(-140vh) scale(0.9); opacity: 0; }
}
    /* .center-content {
        position: absolute;
        z-index: 20;
        text-align: center;
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.2rem;
        padding: 2rem 3rem;
        border-radius: 12px;
        background: radial-gradient(ellipse at center, rgba(10,10,10,0.5) 0%, transparent 70%);
        pointer-events: none;
         transform: translateY(18vh);
    } */


    .top-content {
    position: absolute;
    top: 16vh; /* jarak dari atas layar, atur sesuai selera */
    left: 50%;
    transform: translateX(-50%);
    z-index: 20;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.6rem;
    pointer-events: none;
}

.bottom-content {
    position: absolute;
    top: 68vh; /* posisi tombol, ini kira-kira sama seperti translateY(18vh) sebelumnya */
    left: 50%;
    transform: translateX(-50%);
    z-index: 20;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.2rem;
    pointer-events: none;
}

.bottom-content .open-button {
    pointer-events: auto;
}

    .center-content .open-button {
        pointer-events: auto;
    }

    .ornament-top {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 0.5rem;
    }

    .ornament-line {
        width: 50px;
        height: 1px;
        background: linear-gradient(to right, transparent, #d4af37, transparent);
    }

    .ornament-diamond {
        width: 8px;
        height: 8px;
        background: #d4af37;
        transform: rotate(45deg);
    }

    .kingdom-label {
        font-size: 10px;
        letter-spacing: 0.4em;
        color: rgba(212, 175, 55, 0.8);
        margin: 0;
        text-shadow: 0 2px 12px rgba(0,0,0,0.9);
    }

    .kingdom-label-jawa {
    font-family: 'Noto Sans Javanese', serif;
    font-size: 1.1rem;
    letter-spacing: 0.05em;
    color: rgba(212, 175, 55, 0.85);
    margin: 0;
    text-shadow: 0 2px 12px rgba(0,0,0,0.9);
     opacity: 0.5;
}

    .initials {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem;
    font-weight: 400;
    letter-spacing: 0.15em;
    color: #d4af37;
    margin: 0;
    text-shadow: 0 2px 12px rgba(0,0,0,0.9);
    opacity: 0.5;
}

.hashtag-label {
    font-size: 10px;
    letter-spacing: 0.2em;
    color: rgba(212, 175, 55, 0.6);
    margin-top: 0.3rem;
    font-style: italic;
    text-shadow: 0 2px 12px rgba(0,0,0,0.9);
}

    .open-button {
        position: relative;
        background: transparent;
        border: 1.5px solid #d4af37;
        color: #d4af37;
        padding: 1rem 3rem;
        font-size: 13px;
        letter-spacing: 0.4em;
        cursor: pointer;
        margin-top: 1rem;
        transition: all 0.4s ease;
        font-family: 'Cormorant Garamond', serif;
        text-shadow: 0 2px 12px rgba(0,0,0,0.9);
    }

    .open-button:hover {
        background: rgba(212, 175, 55, 0.1);
        box-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
        letter-spacing: 0.5em;
    }

    .button-ornament {
        position: absolute;
        top: -4px;
        left: 50%;
        transform: translateX(-50%) rotate(45deg);
        width: 6px;
        height: 6px;
        background: #d4af37;
    }

    .tap-hint {
        font-size: 10px;
        letter-spacing: 0.2em;
        color: rgba(255, 255, 255, 0.35);
        margin-top: 0.5rem;
        text-shadow: 0 2px 12px rgba(0,0,0,0.9);
    }

    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,400&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,400&family=Noto+Sans+Javanese:wght@400;500&display=swap');
    @media (max-width: 640px) {
        .open-button { padding: 0.85rem 2.2rem; font-size: 11px; }
    }
</style>