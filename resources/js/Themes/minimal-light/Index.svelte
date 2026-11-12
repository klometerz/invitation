<script>
    // Theme: minimal-light (P2-T7).
    // Proves the theme contract: same props, same data, completely different
    // presentation. Visual polish is the owner's job.
    import MusicPlayer from "../../Components/MusicPlayer.svelte";

    let {
        invitation = null,
        content = {},
        sections = [],
        features = {},
        guestData = null,
        galleries = [],
        meta = {},
    } = $props();

    const DEFAULT_SECTIONS = ["welcome", "verses", "event", "info", "gallery", "rsvp", "thanks"];

    const activeSections = $derived(
        Array.isArray(sections) && sections.length > 0 ? sections : DEFAULT_SECTIONS,
    );

    const coupleNames = $derived(meta?.coupleNames || "");
    const dateText = $derived(meta?.dateText || "");
    const musicUrl = $derived(
        features?.music === false ? null : content?.hero?.music_url || null,
    );

    const verses = $derived(Array.isArray(content?.verses) ? content.verses : []);
    const events = $derived(Array.isArray(content?.acara) ? content.acara : []);
    const announcements = $derived(
        Array.isArray(content?.moodboard?.announcements)
            ? content.moodboard.announcements
            : [],
    );
    const bankAccounts = $derived(
        Array.isArray(meta?.thanks?.bank_accounts) ? meta.thanks.bank_accounts : [],
    );

    const wedding = $derived(content?.mempelai || {});

    let rsvpState = $state(guestData?.is_rsvp ? guestData?.attendance : null);
    let rsvpMessage = $state("");
    let submitting = $state(false);
    let submitted = $state(Boolean(guestData?.is_rsvp));

    async function sendRsvp(status) {
        if (submitting || submitted || !guestData?.guest_code) return;

        submitting = true;

        try {
            const baseUrl = import.meta.env.VITE_APP_URL || "";
            const response = await fetch(`${baseUrl}/api/rsvp/${guestData.guest_code}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({ status, message: rsvpMessage.trim() }),
            });

            const result = await response.json().catch(() => ({}));

            if (response.ok || response.status === 409) {
                submitted = true;
                rsvpState = result.attendance || status;
            } else {
                alert(result.message || "Gagal mengirim konfirmasi. Coba lagi.");
            }
        } catch {
            alert("Gagal terhubung ke server. Cek koneksi Anda.");
        } finally {
            submitting = false;
        }
    }
</script>

<div class="page">
    {#if musicUrl}
        <MusicPlayer url={musicUrl} />
    {/if}

    {#if activeSections.includes("welcome")}
        <section class="hero" id="section-welcome">
            {#if content?.hero?.background_url}
                <img class="hero-image" src={content.hero.background_url} alt="" />
            {/if}
            <div class="hero-inner">
                {#if content?.hero?.title}
                    <p class="eyebrow">{content.hero.title}</p>
                {/if}
                {#if coupleNames}
                    <h1 class="names">{coupleNames}</h1>
                {/if}
                {#if content?.hero?.date_formatted || dateText}
                    <p class="date">{content?.hero?.date_formatted || dateText}</p>
                {/if}
                {#if guestData?.name}
                    <p class="guest">Untuk: <strong>{guestData.name}</strong></p>
                {/if}
            </div>
        </section>
    {/if}

    {#if activeSections.includes("verses") && (verses.length > 0 || content?.gratitude_message)}
        <section class="section" id="section-verses">
            {#each verses as verse}
                {#if verse.arabic}<p class="arabic">{verse.arabic}</p>{/if}
                {#if verse.translation}<p class="quote">{verse.translation}</p>{/if}
                {#if verse.reference}<p class="cite">{verse.reference}</p>{/if}
            {/each}
            {#if content?.gratitude_message}
                <p class="body">{content.gratitude_message}</p>
            {/if}
        </section>
    {/if}

    {#if activeSections.includes("event") && events.length > 0}
        <section class="section" id="section-event">
            <h2 class="heading">Acara</h2>
            <div class="cards">
                {#each events as event}
                    <article class="card">
                        <p class="card-label">{event.tipe === "akad" ? "Akad Nikah" : "Resepsi"}</p>
                        {#if event.tanggal}<p class="card-line">{event.tanggal}</p>{/if}
                        {#if event.jam}<p class="card-line">{event.jam}</p>{/if}
                        {#if event.lokasi}<p class="card-strong">{event.lokasi}</p>{/if}
                        {#if event.alamat}<p class="card-line">{event.alamat}</p>{/if}
                        {#if event.map_url}
                            <a class="button" href={event.map_url} target="_blank" rel="noreferrer">
                                Lihat peta
                            </a>
                        {/if}
                    </article>
                {/each}
            </div>
        </section>
    {/if}

    {#if activeSections.includes("info") && announcements.length > 0}
        <section class="section" id="section-info">
            <h2 class="heading">Informasi</h2>
            <ul class="info-list">
                {#each announcements as item}
                    <li>
                        {#if item.title}<strong>{item.title}</strong>{/if}
                        {#if item.description}<span>{item.description}</span>{/if}
                    </li>
                {/each}
            </ul>
        </section>
    {/if}

    {#if activeSections.includes("gallery") && galleries.length > 0}
        <section class="section" id="section-gallery">
            <h2 class="heading">Galeri</h2>
            <div class="gallery">
                {#each galleries as photo (photo.id)}
                    <img src={photo.path} alt="Foto undangan" loading="lazy" />
                {/each}
            </div>
        </section>
    {/if}

    {#if activeSections.includes("rsvp")}
        <section class="section" id="section-rsvp">
            <h2 class="heading">Konfirmasi Kehadiran</h2>

            {#if !guestData}
                <p class="body">
                    Buka tautan undangan pribadi Anda untuk mengonfirmasi kehadiran.
                </p>
            {:else if submitted}
                <p class="body">
                    Terima kasih, {guestData.name}. Konfirmasi Anda sudah kami terima.
                </p>
            {:else}
                <p class="body">Halo {guestData.name}, mohon konfirmasi kehadiran Anda.</p>
                <textarea
                    class="textarea"
                    rows="3"
                    bind:value={rsvpMessage}
                    placeholder="Ucapan atau doa (opsional)"
                    disabled={submitting}
                ></textarea>
                <div class="row">
                    <button class="button" disabled={submitting} onclick={() => sendRsvp("confirmed")}>
                        Hadir
                    </button>
                    <button
                        class="button ghost"
                        disabled={submitting}
                        onclick={() => sendRsvp("declined")}
                    >
                        Tidak hadir
                    </button>
                </div>
            {/if}
        </section>
    {/if}

    {#if activeSections.includes("thanks")}
        <section class="section" id="section-thanks">
            <h2 class="heading">Terima Kasih</h2>
            <p class="body">
                Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila
                Bapak/Ibu/Saudara/i berkenan hadir.
            </p>
            {#if coupleNames}
                <p class="signature">{coupleNames}</p>
            {/if}

            {#if bankAccounts.length > 0 || meta?.thanks?.qris_image_url}
                <div class="gift">
                    <p class="card-label">Tanda kasih</p>
                    {#each bankAccounts as account}
                        <p class="card-line">
                            {account.bank} {account.number}
                            {#if account.holder}<span class="muted">a/n {account.holder}</span>{/if}
                        </p>
                    {/each}
                    {#if meta?.thanks?.qris_image_url}
                        <img
                            class="qris"
                            src={meta.thanks.qris_image_url}
                            alt="QRIS"
                        />
                    {/if}
                </div>
            {/if}
        </section>
    {/if}
</div>

<style>
    .page {
        min-height: 100vh;
        background: #fbfaf7;
        color: #2b2b2b;
        font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
        padding-bottom: 64px;
    }

    .hero {
        position: relative;
        padding: 96px 24px 64px;
        text-align: center;
        background: #f1ece2;
        overflow: hidden;
    }

    .hero-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.25;
    }

    .hero-inner {
        position: relative;
        max-width: 640px;
        margin: 0 auto;
    }

    .eyebrow {
        font-size: 11px;
        letter-spacing: 0.35em;
        text-transform: uppercase;
        color: #8a7a5c;
        margin: 0 0 18px;
    }

    .names {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: clamp(2.2rem, 7vw, 3.6rem);
        font-weight: 400;
        margin: 0 0 16px;
        color: #1f1f1f;
    }

    .date {
        font-size: 14px;
        letter-spacing: 0.2em;
        color: #6b6b6b;
        margin: 0;
    }

    .guest {
        margin-top: 28px;
        font-size: 13px;
        color: #6b6b6b;
    }

    .section {
        max-width: 720px;
        margin: 0 auto;
        padding: 44px 24px;
        border-bottom: 1px solid #e8e2d6;
    }

    .heading {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: 1.6rem;
        font-weight: 500;
        margin: 0 0 20px;
        color: #1f1f1f;
    }

    .body {
        font-size: 14.5px;
        line-height: 1.7;
        color: #4a4a4a;
        margin: 0 0 14px;
    }

    .arabic {
        font-size: 1.5rem;
        text-align: right;
        line-height: 2;
        margin: 0 0 16px;
    }

    .quote {
        font-style: italic;
        color: #4a4a4a;
        margin: 0 0 8px;
    }

    .cite {
        font-size: 12.5px;
        color: #8a7a5c;
        margin: 0 0 20px;
    }

    .cards {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    }

    .card {
        background: #fff;
        border: 1px solid #e8e2d6;
        border-radius: 14px;
        padding: 20px;
    }

    .card-label {
        font-size: 11px;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #8a7a5c;
        margin: 0 0 10px;
    }

    .card-strong {
        font-weight: 600;
        margin: 8px 0 2px;
    }

    .card-line {
        font-size: 13.5px;
        color: #4a4a4a;
        margin: 4px 0;
    }

    .muted {
        color: #8a8a8a;
        font-size: 12.5px;
        margin-left: 6px;
    }

    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 12px;
    }

    .info-list li {
        background: #fff;
        border: 1px solid #e8e2d6;
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 13.5px;
        color: #4a4a4a;
    }

    .gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }

    .gallery img {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        border-radius: 10px;
    }

    .textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #ddd6c8;
        border-radius: 10px;
        padding: 10px 12px;
        font-family: inherit;
        font-size: 13.5px;
        resize: vertical;
        margin-bottom: 12px;
    }

    .row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .button {
        display: inline-block;
        padding: 10px 18px;
        border-radius: 10px;
        border: 1px solid #1f1f1f;
        background: #1f1f1f;
        color: #fff;
        font-size: 13.5px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        font-family: inherit;
        margin-top: 12px;
    }

    .button.ghost {
        background: transparent;
        color: #1f1f1f;
    }

    .button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .signature {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: 1.4rem;
        margin: 18px 0 0;
    }

    .gift {
        margin-top: 24px;
        background: #fff;
        border: 1px solid #e8e2d6;
        border-radius: 12px;
        padding: 18px;
    }

    .qris {
        width: 160px;
        border-radius: 8px;
        margin-top: 10px;
    }
</style>
