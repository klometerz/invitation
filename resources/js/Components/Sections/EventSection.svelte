<script>
    import { onMount, onDestroy } from "svelte";

    let { events = [] } = $props();

    const firstEventDate = events.length > 0
        ? new Date(`${events[0].tanggal}T${(events[0].jam || '00:00').split(' ')[0]}:00`)
        : new Date();

    let timeLeft = $state({ days: 0, hours: 0, minutes: 0, seconds: 0 });
    let itemRefs = $state([]);
    let visibleItems = $state(new Set());
    let observer;
    let countdownInterval;

    function updateCountdown() {
        const now = new Date();
        const diff = firstEventDate - now;
        if (diff > 0) {
            timeLeft = {
                days: Math.floor(diff / (1000 * 60 * 60 * 24)),
                hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
                minutes: Math.floor((diff / 1000 / 60) % 60),
                seconds: Math.floor((diff / 1000) % 60),
            };
        }
    }

    onMount(() => {
        updateCountdown();
        countdownInterval = setInterval(updateCountdown, 1000);

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
        clearInterval(countdownInterval);
        if (observer) observer.disconnect();
    });

    function formatTanggal(tanggal) {
        if (!tanggal) return '';
        return new Date(tanggal).toLocaleDateString('id-ID', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    }

    function tipeLabel(tipe) {
        return tipe === 'akad' ? 'Akad Nikah' : 'Resepsi';
    }
</script>

<div class="event-wrapper">
    <div class="countdown-block">
        <p class="subtitle">COUNTING DOWN TO</p>
        <h2 class="title">The Big Day</h2>

        <div class="countdown-grid">
            <div class="countdown-item">
                <div class="countdown-number">{timeLeft.days.toString().padStart(2, "0")}</div>
                <div class="countdown-unit">Days</div>
            </div>
            <div class="countdown-sep">:</div>
            <div class="countdown-item">
                <div class="countdown-number">{timeLeft.hours.toString().padStart(2, "0")}</div>
                <div class="countdown-unit">Hours</div>
            </div>
            <div class="countdown-sep">:</div>
            <div class="countdown-item">
                <div class="countdown-number">{timeLeft.minutes.toString().padStart(2, "0")}</div>
                <div class="countdown-unit">Minutes</div>
            </div>
            <div class="countdown-sep">:</div>
            <div class="countdown-item">
                <div class="countdown-number">{timeLeft.seconds.toString().padStart(2, "0")}</div>
                <div class="countdown-unit">Seconds</div>
            </div>
        </div>
    </div>

    {#if events.length > 0}
        <div class="event-list">
            {#each events as event, i}
                <div
                    class="event-row"
                    class:visible={visibleItems.has(i)}
                    bind:this={itemRefs[i]}
                    data-idx={i}
                >
                    <div class="glass-card">
                        <h3 class="event-title">{tipeLabel(event.tipe)}</h3>
                        <p class="event-date">{formatTanggal(event.tanggal)}</p>
                        <p class="event-time">{event.jam}</p>
                        <div class="divider"></div>
                        <p class="event-location">{event.lokasi}</p>
                        <p class="event-address">{event.alamat}</p>
                        {#if event.map_url}
                            <a
                                href={event.map_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                class="map-button"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Open in Maps
                            </a>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    {:else}
        <p class="empty-text">Detail acara akan segera diinformasikan.</p>
    {/if}
</div>

<style>
    .event-wrapper {
        width: 100%;
        max-width: 640px;
        margin: 0 auto;
        font-family: "Cormorant Garamond", serif;
    }

    .countdown-block {
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
        margin-bottom: 2rem;
    }

    .countdown-grid {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.6rem;
    }

    .countdown-item {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .countdown-number {
        font-size: 1.8rem;
        color: white;
        font-weight: 300;
        min-width: 56px;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(212, 175, 55, 0.25);
        border-radius: 8px;
        backdrop-filter: blur(10px);
    }

    .countdown-unit {
        font-size: 9px;
        color: rgba(255, 255, 255, 0.55);
        letter-spacing: 0.15em;
        text-transform: uppercase;
        margin-top: 0.5rem;
    }

    .countdown-sep {
        color: #d4af37;
        font-size: 1.5rem;
        opacity: 0.5;
    }

    .event-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .event-row {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.7s ease, transform 0.7s ease;
    }

    .event-row.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .glass-card {
        text-align: center;
        padding: 2rem;
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 20px;
    }

    .event-title {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.7rem;
        color: white;
        font-weight: 400;
        margin-bottom: 1rem;
    }

    .event-date {
        color: rgba(255, 255, 255, 0.85);
        font-size: 14px;
        margin: 0;
    }

    .event-time {
        color: #d4af37;
        font-size: 13px;
        margin: 0.3rem 0 0;
    }

    .divider {
        width: 50px;
        height: 1px;
        background: rgba(212, 175, 55, 0.3);
        margin: 1rem auto;
    }

    .event-location {
        color: white;
        font-size: 15px;
        margin: 0;
    }

    .event-address {
        color: rgba(255, 255, 255, 0.55);
        font-size: 12.5px;
        margin: 0.4rem 0 1.25rem;
        line-height: 1.5;
    }

    .map-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.5rem;
        background: rgba(212, 175, 55, 0.1);
        border: 1px solid rgba(212, 175, 55, 0.35);
        color: #d4af37;
        font-size: 11px;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        text-decoration: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.25s;
    }

    .map-button:hover {
        background: rgba(212, 175, 55, 0.2);
        color: white;
    }

    .empty-text {
        text-align: center;
        color: rgba(255, 255, 255, 0.4);
        font-size: 13px;
    }

    @media (max-width: 640px) {
        .title { font-size: 1.8rem; }
        .countdown-number { font-size: 1.4rem; min-width: 46px; padding: 0.6rem; }
        .glass-card { padding: 1.5rem; }
    }
</style>