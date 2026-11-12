<script>
    import { onMount } from "svelte";

    // K12: there is no bundled fallback track. Themes only render this
    // component when the invitation actually uploaded music.
    let { url = null, autoPlay = false } = $props();

    let audio = $state(null);
    let isPlaying = $state(false);

    // Fungsi Global untuk Play (bisa dipanggil dari Wedding.svelte)
    export const playMusic = () => {
        if (!url || !audio || isPlaying) return;

        audio
            .play()
            .then(() => {
                isPlaying = true;
            })
            .catch(() => {
                // Autoplay blocked by browser: the button still works.
            });
    };

    function toggleMusic(e) {
        e.stopPropagation();
        if (!url || !audio) return;

        if (isPlaying) {
            audio.pause();
            isPlaying = false;
        } else {
            audio.play().catch(() => {
                isPlaying = false;
            });
            isPlaying = true;
        }
    }

    onMount(() => {
        if (autoPlay) playMusic();
    });
</script>

<audio bind:this={audio} src={url} loop></audio>

<button class="music-btn" onclick={toggleMusic} aria-label="Toggle Music">
    <div class="visualizer" class:active={isPlaying}>
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
    </div>

    <span class="status-text">{isPlaying ? "MUTE" : "PLAY"}</span>
</button>

<style>
    .music-btn {
        position: fixed;
        bottom: 2rem;
        right: 1.5rem;
        z-index: 9999;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.8rem 1.2rem;
        border-radius: 50px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: 0.3s;
    }

    .music-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .status-text {
        color: white;
        font-size: 9px;
        letter-spacing: 0.2em;
        font-weight: bold;
    }

    /* Visualizer Animation */
    .visualizer {
        display: flex;
        align-items: flex-end;
        gap: 2px;
        height: 12px;
    }

    .bar {
        width: 2px;
        height: 100%;
        background: #d4af37; /* Gold color */
        border-radius: 1px;
    }

    .active .bar {
        animation: dance 1s ease-in-out infinite;
    }

    .active .bar:nth-child(2) {
        animation-delay: 0.2s;
    }
    .active .bar:nth-child(3) {
        animation-delay: 0.4s;
    }
    .active .bar:nth-child(4) {
        animation-delay: 0.6s;
    }

    @keyframes dance {
        0%,
        100% {
            height: 4px;
        }
        50% {
            height: 12px;
        }
    }
</style>
