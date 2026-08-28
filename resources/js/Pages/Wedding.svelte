<script>
    import { onMount } from "svelte";
    import { fade } from "svelte/transition";
    import Curtain from "../Components/Curtain.svelte";
    import LoadingScreen from "../Components/LoadingScreen.svelte";
    import EventSection from "../Components/Sections/EventSection.svelte";
    import InfoSection from "../Components/Sections/InfoSection.svelte";
    // import StorySection from "../Components/Sections/StorySection.svelte";
    import GratitudeSection from "../Components/Sections/GratitudeSection.svelte";
    import GallerySection from "../Components/Sections/GallerySection.svelte";
    import RSVPSection from "../Components/Sections/RSVPSection.svelte";
    import MusicPlayer from "../Components/MusicPlayer.svelte";
    import ThanksSection from "../Components/Sections/ThankSection.svelte";

    let { invitation, content, guestData, galleries } = $props();

    let appState = $state("curtain");
    // let appState = $state("loading");
    let musicRef = $state(null);
    let scrollContainer = $state(null);

    const sections = [
        "welcome",
        "story",
        "event",
        "info",
        "gallery",
        "rsvp",
        "thanks",
    ];
    //   const sections = ["welcome", "story", "event", "rsvp", "thanks"];
    let currentSectionIndex = $state(0);

    function onCurtainComplete() {
        appState = "loading";
        if (musicRef) musicRef.playMusic();
    }

    function onLoadingComplete() {
        appState = "sections";
    }

    //     function onLoadingComplete() {
    //     appState = "curtain";
    // }

    // function onCurtainComplete() {
    //     appState = "sections";
    //     if (musicRef) musicRef.playMusic();
    // }

    function scrollToSection(index) {
        const el = document.getElementById(`section-${sections[index]}`);
        el?.scrollIntoView({ behavior: "smooth" });
    }

    $effect(() => {
        if (appState !== "sections" || !scrollContainer) return;

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
                        const idx = sections.indexOf(
                            entry.target.dataset.section,
                        );
                        if (idx !== -1) currentSectionIndex = idx;
                    }
                });
            },
            { threshold: 0.5, root: scrollContainer },
        );

        const targets = scrollContainer.querySelectorAll(".page-section");
        targets.forEach((el) => observer.observe(el));

        return () => observer.disconnect();
    });
</script>

<Curtain onComplete={onCurtainComplete} />

{#if appState === "loading"}
    <LoadingScreen
        guestName={guestData?.name}
        logoUrl="/mk.png"
        onComplete={onLoadingComplete}
    />
{/if}

<!-- {#if appState === "loading"}
    <LoadingScreen
        guestName={guestData?.name}
        logoUrl="/mk.png"
        onComplete={onLoadingComplete}
    />
{/if}

{#if appState === "curtain"}
    <Curtain onComplete={onCurtainComplete} />
{/if} -->

{#if appState === "sections"}
    <!-- BACKGROUND TETAP (fixed), tidak ikut scroll -->
    <div class="fixed-backdrop">
        {#if content?.hero?.background_url}
            <div
                class="backdrop-image"
                style="background-image: url({content.hero.background_url})"
            ></div>
        {/if}
        <div class="backdrop-overlay"></div>
    </div>

    <div
        class="wedding-scroll-container"
        bind:this={scrollContainer}
        in:fade={{ duration: 500 }}
    >
        <div class="progress-bars">
            {#each sections as _, i}
                <button
                    class="progress-bar"
                    class:completed={i < currentSectionIndex}
                    class:active={i === currentSectionIndex}
                    onclick={() => scrollToSection(i)}
                    aria-label="Go to section {i + 1}"
                >
                    <div class="progress-fill"></div>
                </button>
            {/each}
        </div>

        <MusicPlayer
            bind:this={musicRef}
            url={content?.hero?.music_url || "/music/background.mp3"}
        />

        {#if guestData?.checkin_status === "arrived"}
            <a
                href="/photobooth?guest={guestData.guest_code}"
                class="photobooth-banner"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="banner-icon"
                >
                    <path
                        d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"
                    />
                    <circle cx="12" cy="13" r="3" />
                </svg>
                <span class="banner-text">Buka Photobooth</span>
            </a>
        {/if}

        {#if currentSectionIndex === 0}
            <div class="scroll-hint" transition:fade={{ duration: 400 }}>
                <div class="scroll-hint-line"></div>
                <span>SCROLL</span>
            </div>
        {/if}

        <!-- WELCOME -->
        <section
            id="section-welcome"
            data-section="welcome"
            class="page-section"
        >
            <div class="welcome-hero">
                <p class="subtitle">
                    {content?.hero?.title || "THE WEDDING CELEBRATION"}
                </p>
                <div class="names-box">
                    <!-- Mempelai Wanita -->
                    <div class="person-block">
                        <p class="full-name">
                            {content?.mempelai?.wanita?.nama_lengkap ||
                                "Maulia Nur Rahma"}
                        </p>
                        <h1 class="name-main">
                            {content?.mempelai?.wanita?.nama_panggilan ||
                                "Maul"}
                        </h1>
                        {#if content?.mempelai?.wanita?.ayah || content?.mempelai?.wanita?.ibu}
                            <p class="parents-text">
                                Putri dari Bapak {content?.mempelai?.wanita
                                    ?.ayah || "-"}
                                & Ibu {content?.mempelai?.wanita?.ibu || "-"}
                            </p>
                        {/if}
                    </div>

                    <div class="ampersand">
                        <div class="line"></div>
                        <span>&</span>
                        <div class="line"></div>
                    </div>

                    <!-- Mempelai Pria -->
                    <div class="person-block">
                        <p class="full-name">
                            {content?.mempelai?.pria?.nama_lengkap ||
                                "M Kahfi Kresnotutuko"}
                        </p>
                        <h1 class="name-main">
                            {content?.mempelai?.pria?.nama_panggilan || "Kahfi"}
                        </h1>
                        {#if content?.mempelai?.pria?.ayah || content?.mempelai?.pria?.ibu}
                            <p class="parents-text">
                                Putra dari Bapak {content?.mempelai?.pria
                                    ?.ayah || "-"}
                                & Ibu {content?.mempelai?.pria?.ibu || "-"}
                            </p>
                        {/if}
                    </div>
                </div>
                <div class="date-loc">
                    <p class="date">
                        {content?.hero?.date_formatted || "SABTU, 24 MEI 2026"}
                    </p>
                    <div class="ornament">
                        <div class="diamond"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- STORY -->
        <!-- <section id="section-story" data-section="story" class="page-section">
            <StorySection stories={content?.stories || []} />
        </section> -->

        <section id="section-story" data-section="story" class="page-section">
            <GratitudeSection
                verses={content?.verses || []}
                message={content?.gratitude_message || ""}
            />
        </section>

        <!-- EVENT -->
        <section id="section-event" data-section="event" class="page-section">
            <EventSection events={content?.acara || []} />
        </section>

        <!-- INFO / MOODBOARD -->
        <section id="section-info" data-section="info" class="page-section">
            <InfoSection
                colors={content?.moodboard?.colors || []}
                announcements={content?.moodboard?.announcements || []}
            />
        </section>

        <!-- GALLERY -->
        <section
            id="section-gallery"
            data-section="gallery"
            class="page-section"
        >
            <GallerySection galleries={galleries || []} />
        </section>

        <!-- RSVP -->
        <section id="section-rsvp" data-section="rsvp" class="page-section">
            <RSVPSection {guestData} invitationId={invitation?.id} />
        </section>

        <!-- THANKS -->
        <section id="section-thanks" data-section="thanks" class="page-section">
            <ThanksSection {guestData} names={content?.mempelai} />
        </section>
    </div>
{/if}

<style>
    @import url("https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Inter:wght@300;400&display=swap");

    :global(body) {
        margin: 0;
        font-family: "Inter", sans-serif;
        background: #0a0a0a;
        color: white;
    }

    /* ===== FIXED BACKGROUND LAYER (tidak ikut scroll) ===== */
    .fixed-backdrop {
        position: fixed;
        inset: 0;
        z-index: 0;
        overflow: hidden;
        background: #0a0a0a;
    }

    .backdrop-image {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0;
        animation:
            bgFadeIn 2.5s ease-out forwards,
            bgKenBurns 30s ease-out forwards;
    }

    @keyframes bgFadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes bgKenBurns {
        from {
            transform: scale(1.15);
        }
        to {
            transform: scale(1);
        }
    }

    .backdrop-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            to bottom,
            rgba(10, 10, 10, 0.55) 0%,
            rgba(10, 10, 10, 0.7) 50%,
            rgba(10, 10, 10, 0.85) 100%
        );
    }

    /* ===== SCROLL CONTAINER (transparan, background terlihat lewat) ===== */
    .wedding-scroll-container {
        position: relative;
        z-index: 1;
        height: 100vh;
        width: 100%;
        overflow-y: scroll;
        scroll-snap-type: y mandatory;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }

    .wedding-scroll-container::-webkit-scrollbar {
        display: none;
    }

    .page-section {
        position: relative;
        min-height: 100vh;
        width: 100%;
        scroll-snap-align: start;
        scroll-snap-stop: always;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    /* Progress Bars */
    .progress-bars {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        display: flex;
        gap: 6px;
        padding: 12px;
        z-index: 100;
        pointer-events: none;
    }
    .progress-bar {
        flex: 1;
        height: 2px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 2px;
        border: none;
        padding: 0;
        cursor: pointer;
        pointer-events: auto;
    }
    .progress-fill {
        height: 100%;
        background: #d4af37;
        width: 0%;
        transition: width 0.4s;
    }
    .progress-bar.active .progress-fill,
    .progress-bar.completed .progress-fill {
        width: 100%;
    }

    /* Scroll Hint */
    .scroll-hint {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 100;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.6rem;
        pointer-events: none;
    }

    .scroll-hint-line {
        width: 1px;
        height: 40px;
        background: rgba(212, 175, 55, 0.6);
        animation: scrollLineGrow 2s ease-in-out infinite;
    }

    .scroll-hint span {
        font-size: 9px;
        letter-spacing: 0.3em;
        color: rgba(255, 255, 255, 0.5);
    }

    @keyframes scrollLineGrow {
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

    /* Welcome content */
    .welcome-hero {
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .subtitle {
        font-size: 10px;
        letter-spacing: 0.5em;
        color: #d4af37;
        margin-bottom: 2rem;
    }
    .name-main {
        font-family: "Cormorant Garamond", serif;
        font-size: 4rem;
        font-weight: 300;
        margin: 0;
        line-height: 1;
        color: white;
    }

    .names-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .person-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .full-name {
        font-family: "Cormorant Garamond", serif;
        font-size: 11px;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.5);
        margin: 0;
    }

    .parents-text {
        font-family: "Cormorant Garamond", serif;
        font-style: italic;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.55);
        margin: 0.25rem 0 0;
        max-width: 320px;
        line-height: 1.5;
    }

    .ampersand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        margin: 1.5rem 0;
    }
    .ampersand .line {
        width: 30px;
        height: 1px;
        background: rgba(212, 175, 55, 0.3);
    }
    .ampersand span {
        font-family: "Cormorant Garamond", serif;
        font-style: italic;
        font-size: 2rem;
        opacity: 0.6;
        color: white;
    }

    .date-loc {
        margin-top: 3rem;
    }
    .date {
        font-size: 12px;
        letter-spacing: 0.3em;
        opacity: 0.8;
        color: white;
    }
    .ornament {
        display: flex;
        justify-content: center;
        margin: 1.5rem 0;
    }
    .diamond {
        width: 6px;
        height: 6px;
        background: #d4af37;
        transform: rotate(45deg);
    }

    @media (max-width: 768px) {
        .name-main {
            font-size: 3rem;
        }
    }

    .photobooth-banner {
        position: fixed;
        bottom: 2rem;
        left: 1.5rem;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        background: rgba(212, 175, 55, 0.95);
        backdrop-filter: blur(10px);
        color: #0a0a0a;
        text-decoration: none;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.03em;
        box-shadow: 0 8px 24px rgba(212, 175, 55, 0.4);
        animation: bannerPulse 2.5s ease-in-out infinite;
    }

    .banner-icon {
        flex-shrink: 0;
    }

    @keyframes bannerPulse {
        0%,
        100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    @media (max-width: 480px) {
        .photobooth-banner {
            bottom: 1.5rem;
            left: 1rem;
            padding: 0.65rem 1rem;
            font-size: 11px;
        }
    }
</style>
