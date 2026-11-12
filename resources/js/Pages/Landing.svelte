<script>
    import { page } from "@inertiajs/svelte";

    let {
        platform = {},
        whatsappUrl = null,
        packages = [],
        demoUrl = null,
    } = $props();

    let scrolled = $state(false);

    function formatPrice(price) {
        return price === null || price === undefined
            ? "Hubungi kami"
            : `Rp ${Number(price).toLocaleString("id-ID")}`;
    }

    function go(e, id) {
        e.preventDefault();
        document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
    }

    // Reveal on scroll. Respects reduced motion via CSS.
    function reveal(node, delay = 0) {
        node.style.setProperty("--d", `${delay}ms`);
        if (typeof IntersectionObserver === "undefined") {
            node.classList.add("in");
            return;
        }
        const io = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    node.classList.add("in");
                    io.disconnect();
                }
            },
            { threshold: 0.15 },
        );
        io.observe(node);
        return { destroy: () => io.disconnect() };
    }

    const icons = {
        invitation: `<path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/>`,
        qr: `<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20h1"/>`,
        guestbook: `<path d="M9 3h6a1 1 0 011 1v1h2a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V6a1 1 0 011-1h2V4a1 1 0 011-1z"/><path d="M9 13l2 2 4-4"/>`,
        shield: `<path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/>`,
        camera: `<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>`,
        book: `<path d="M4 19.5A2.5 2.5 0 016.5 17H20V3H6.5A2.5 2.5 0 004 5.5z"/><path d="M4 19.5V22h16"/>`,
    };

    const features = [
        {
            icon: "invitation",
            title: "Undangan personal",
            text: "Tautan unik untuk setiap tamu, lengkap dengan nama tamu dan konfirmasi kehadiran dalam satu ketukan.",
        },
        {
            icon: "qr",
            title: "QR check-in",
            text: "Kartu undangan fisik ber-QR dipindai petugas di pintu masuk, status kehadiran langsung tercatat.",
        },
        {
            icon: "guestbook",
            title: "Buku tamu digital",
            text: "Pantau siapa yang sudah hadir secara langsung dan ekspor daftar tamu ke Excel kapan saja.",
        },
        {
            icon: "shield",
            title: "Anti pemindaian ganda",
            text: "QR yang sudah dipakai langsung terdeteksi, jadi souvenir terbagi dengan benar.",
        },
        {
            icon: "camera",
            title: "Virtual photobooth",
            text: "Terbuka untuk tamu yang sudah check-in. Pilih frame, tambah stiker, lalu unduh atau bagikan.",
        },
        {
            icon: "book",
            title: "Buku kenangan",
            text: "Foto dan ucapan tamu dirangkum menjadi PDF yang siap dicetak sebagai kenang-kenangan.",
        },
    ];

    const steps = [
        {
            title: "Pesan & konsultasi",
            text: "Hubungi kami untuk memilih paket dan jadwal acara Anda.",
        },
        {
            title: "Isi data undangan",
            text: "Kami menyiapkan undangan dari data yang Anda kirim.",
        },
        {
            title: "Bagikan ke tamu",
            text: "Setiap tamu mendapat tautan dan QR pribadi.",
        },
        {
            title: "Check-in di hari H",
            text: "Petugas memindai QR tamu dengan buku tamu digital.",
        },
        {
            title: "Photobooth & kenangan",
            text: "Photobooth terbuka untuk tamu yang sudah check-in.",
        },
        {
            title: "Buku kenangan",
            text: "Foto dan ucapan tamu dirangkum menjadi PDF siap cetak.",
        },
    ];

    const faq = [
        {
            q: "Bagaimana cara memesan?",
            a: "Hubungi kami lewat WhatsApp, ceritakan jadwal dan kebutuhan acara Anda. Kami yang menyiapkan dan menginput datanya.",
        },
        {
            q: "Apakah tamu perlu memasang aplikasi?",
            a: "Tidak. Undangan dibuka langsung di browser melalui tautan atau QR.",
        },
        {
            q: "Bagaimana jika tamu lupa membawa kartu undangan?",
            a: "Setelah mengonfirmasi kehadiran, tamu mendapat QR cadangan di halaman undangan yang bisa di-screenshot dan ditunjukkan ke petugas.",
        },
        {
            q: "Berapa lama undangan aktif?",
            a: "Masa aktif mengikuti paket yang dipilih dan dapat diperpanjang.",
        },
        {
            q: "Apakah data tamu aman?",
            a: "Data tamu hanya dapat diakses melalui akun pengelola acara tersebut.",
        },
    ];

    // Decorative mini QR for the hero mockup
    const miniQr = [
        "111010111",
        "101011101",
        "111010111",
        "000110000",
        "101001011",
        "010110100",
        "111001010",
        "101011101",
        "111010011",
    ];
</script>

<svelte:window onscroll={() => (scrolled = window.scrollY > 24)} />

<svelte:head>
    <title>{$page.props.seo?.title ?? "Undangan Pernikahan Digital"}</title>
    <meta
        name="description"
        content="Undangan pernikahan digital dengan QR personal, buku tamu, photobooth, dan buku kenangan."
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    />
</svelte:head>

<div class="landing">
    <!-- NAV -->
    <header class="nav" class:scrolled>
        <div class="nav-inner">
            <a class="brand" href="/">
                {#if platform.logo_url}
                    <img src={platform.logo_url} alt={platform.business_name ?? "Logo"} class="brand-logo" />
                {:else}
                    <span class="brand-mark">
                        {(platform.business_name ?? "Undangan").charAt(0).toUpperCase()}
                    </span>
                {/if}
                <span class="brand-name">{platform.business_name ?? "Undangan Digital"}</span>
            </a>

            <nav class="nav-links" aria-label="Navigasi utama">
                <a href="#fitur" onclick={(e) => go(e, "fitur")}>Fitur</a>
                <a href="#alur" onclick={(e) => go(e, "alur")}>Alur</a>
                <a href="#paket" onclick={(e) => go(e, "paket")}>Paket</a>
                <a href="#faq" onclick={(e) => go(e, "faq")}>FAQ</a>
            </nav>

            <div class="nav-actions">
                <a class="link-quiet" href="/login">Masuk</a>
                {#if whatsappUrl}
                    <a class="btn gold small" href={whatsappUrl} target="_blank" rel="noreferrer">
                        Konsultasi
                    </a>
                {/if}
            </div>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="glow glow-a"></div>
        <div class="glow glow-b"></div>

        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">
                    <span class="diamond"></span>
                    UNDANGAN PERNIKAHAN DIGITAL
                </p>
                <h1 class="hero-title">
                    Undangan yang terasa <em>personal</em> untuk setiap tamu
                </h1>
                <p class="hero-text">
                    Satu tautan pribadi per tamu, QR check-in, buku tamu digital,
                    photobooth, dan buku kenangan yang siap dicetak.
                </p>

                <div class="hero-actions">
                    {#if whatsappUrl}
                        <a class="btn gold" href={whatsappUrl} target="_blank" rel="noreferrer">
                            Konsultasi via WhatsApp
                        </a>
                    {/if}
                    {#if demoUrl}
                        <a class="btn outline" href={demoUrl} target="_blank" rel="noreferrer">
                            Lihat demo
                        </a>
                    {/if}
                </div>

                <ul class="chips">
                    <li>Tanpa aplikasi</li>
                    <li>QR pribadi per tamu</li>
                    <li>Buku tamu digital</li>
                    <li>Photobooth</li>
                </ul>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="phone">
                    <div class="screen">
                        <div class="notch"></div>
                        <div class="mini-progress">
                            <span class="on"></span><span class="on"></span><span></span><span></span><span></span><span></span>
                        </div>
                        <p class="m-label">THE WEDDING OF</p>
                        <p class="m-names">Rina</p>
                        <p class="m-amp">&amp;</p>
                        <p class="m-names">Dimas</p>
                        <div class="m-orn"><i></i><b></b><i></i></div>
                        <p class="m-date">12 . 12 . 2026</p>
                        <span class="m-pill">Buka Undangan</span>
                    </div>
                </div>

                <div class="float-chip chip-a">
                    <span class="chip-ico">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span>Check-in berhasil</span>
                </div>

                <div class="float-chip chip-b">
                    <div class="mini-qr">
                        {#each miniQr as row}
                            {#each row.split("") as cell}
                                <span class:filled={cell === "1"}></span>
                            {/each}
                        {/each}
                    </div>
                    <span>QR tamu</span>
                </div>

                <p class="mock-note">Contoh tampilan</p>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="section" id="fitur">
        <div class="section-head" use:reveal>
            <p class="eyebrow center"><span class="diamond"></span>FITUR</p>
            <h2 class="section-title">Dari undangan sampai kenangan, <em>dalam satu sistem</em></h2>
        </div>

        <div class="bento">
            {#each features as f, i}
                <article class="card reveal" use:reveal={i * 70}>
                    <span class="icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            {@html icons[f.icon]}
                        </svg>
                    </span>
                    <h3>{f.title}</h3>
                    <p>{f.text}</p>
                </article>
            {/each}
        </div>
    </section>

    <!-- FLOW -->
    <section class="section alt" id="alur">
        <div class="section-head" use:reveal>
            <p class="eyebrow center"><span class="diamond"></span>CARA KERJA</p>
            <h2 class="section-title">Enam langkah, <em>tanpa repot</em></h2>
        </div>

        <ol class="timeline">
            {#each steps as step, i}
                <li class="t-row reveal" use:reveal={i * 60}>
                    <span class="t-no">{String(i + 1).padStart(2, "0")}</span>
                    <div class="t-body">
                        <strong>{step.title}</strong>
                        <p>{step.text}</p>
                    </div>
                </li>
            {/each}
        </ol>
    </section>

    <!-- DEMO CTA -->
    {#if demoUrl}
        <section class="section tight">
            <div class="demo-band reveal" use:reveal>
                <div>
                    <p class="eyebrow"><span class="diamond"></span>COBA SENDIRI</p>
                    <h2 class="band-title">Lihat bagaimana tamu Anda akan merasakannya</h2>
                </div>
                <a class="btn gold" href={demoUrl} target="_blank" rel="noreferrer">Buka undangan demo</a>
            </div>
        </section>
    {/if}

    <!-- PACKAGES -->
    <section class="section" id="paket">
        <div class="section-head" use:reveal>
            <p class="eyebrow center"><span class="diamond"></span>PAKET</p>
            <h2 class="section-title">Pilih yang sesuai <em>acara Anda</em></h2>
        </div>

        {#if packages.length === 0}
            <p class="muted center">Paket belum tersedia. Silakan hubungi kami.</p>
        {:else}
            <div class="packages">
                {#each packages as pkg, i (pkg.id)}
                    <article
                        class="package reveal"
                        class:featured={packages.length === 3 && i === 1}
                        use:reveal={i * 90}
                    >
                        <h3>{pkg.name}</h3>
                        <p class="price">{formatPrice(pkg.price)}</p>
                        {#if pkg.description}
                            <p class="pkg-desc">{pkg.description}</p>
                        {/if}
                        <ul>
                            {#each pkg.features ?? [] as feature}
                                <li>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                                    <span>{feature}</span>
                                </li>
                            {/each}
                        </ul>
                        {#if whatsappUrl}
                            <a class="btn outline block" href={whatsappUrl} target="_blank" rel="noreferrer">
                                Tanya paket ini
                            </a>
                        {/if}
                    </article>
                {/each}
            </div>
        {/if}
    </section>

    <!-- FAQ -->
    <section class="section alt" id="faq">
        <div class="section-head" use:reveal>
            <p class="eyebrow center"><span class="diamond"></span>FAQ</p>
            <h2 class="section-title">Pertanyaan <em>umum</em></h2>
        </div>

        <div class="faq">
            {#each faq as item, i}
                <details class="reveal" use:reveal={i * 50}>
                    <summary>
                        <span>{item.q}</span>
                        <i class="plus"></i>
                    </summary>
                    <p>{item.a}</p>
                </details>
            {/each}
        </div>
    </section>

    <!-- FINAL CTA -->
    {#if whatsappUrl}
        <section class="final">
            <div class="reveal" use:reveal>
                <div class="orn"><i></i><b></b><i></i></div>
                <h2 class="final-title">Siap menyiapkan <em>undangan Anda?</em></h2>
                <p class="final-text">Ceritakan jadwal acara Anda, kami bantu dari awal sampai hari H.</p>
                <a class="btn gold big" href={whatsappUrl} target="_blank" rel="noreferrer">
                    Mulai konsultasi
                </a>
            </div>
        </section>
    {/if}

    <!-- FOOTER -->
    <footer class="footer">
        <p>{platform.footer_text ?? `© ${new Date().getFullYear()} ${platform.business_name ?? "Undangan Digital"}`}</p>
        <div class="footer-links">
            <a href="/terms">Syarat &amp; Ketentuan</a>
            <a href="/privacy">Kebijakan Privasi</a>
            {#if platform.instagram_url}
                <a href={platform.instagram_url} target="_blank" rel="noreferrer">Instagram</a>
            {/if}
        </div>
    </footer>

    <!-- FLOATING WHATSAPP -->
    {#if whatsappUrl}
        <a class="wa-float" href={whatsappUrl} target="_blank" rel="noreferrer" aria-label="Chat WhatsApp">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.01 2C6.47 2 2 6.48 2 12.04c0 1.99.52 3.84 1.42 5.45L2 22l4.69-1.23A9.99 9.99 0 0 0 12.01 22c5.54 0 10.01-4.48 10.01-9.96C22.02 6.48 17.55 2 12.01 2Zm5.03 13.14c-.26.73-1.53 1.42-2.14 1.51-.57.09-1.25.12-2.95-.56-2.36-.95-3.88-3.33-4.01-3.49-.14-.16-1.14-1.31-1.14-2.5 0-1.19.64-1.77.87-2.01.23-.22.51-.28.69-.28.17 0 .34 0 .49 0 .16.01.37.02.56.27.19.24.63.82.69.88.06.06.11.13.02.25-.08.12-.12.2-.24.31-.12.11-.25.24-.35.32-.12.09-.25.19-.11.37.14.18.62.99 1.33 1.6.92.78 1.69 1.02 1.93 1.14.25.12.4.1.55-.06.16-.16.67-.78.84-.99.16-.2.32-.17.55-.1.18.05 1.16.55 1.36.65.2.1.33.16.38.25.05.09.05.52-.21 1.25Z"/>
            </svg>
        </a>
    {/if}
</div>

<style>
    .landing {
        --gold: #d4af37;
        --gold-soft: #ecd98b;
        --ink: #0a0a0a;
        --ink-2: #100d09;
        --line: rgba(212, 175, 55, 0.2);
        --text: rgba(255, 255, 255, 0.92);
        --muted: rgba(255, 255, 255, 0.62);

        min-height: 100vh;
        background: var(--ink);
        color: var(--text);
        font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
        overflow-x: hidden;
        position: relative;
    }

    a { color: inherit; }

    em {
        font-style: italic;
        background: linear-gradient(120deg, var(--gold-soft), var(--gold) 60%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    /* ===== NAV ===== */
    .nav {
        position: sticky;
        top: 0;
        z-index: 50;
        transition: background 0.3s, border-color 0.3s, backdrop-filter 0.3s;
        border-bottom: 1px solid transparent;
    }

    .nav.scrolled {
        background: rgba(10, 10, 10, 0.78);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-bottom-color: var(--line);
    }

    .nav-inner {
        max-width: 1120px;
        margin: 0 auto;
        padding: 14px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }

    .brand-logo {
        width: 32px;
        height: 32px;
        object-fit: contain;
        border-radius: 6px;
    }

    .brand-mark {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--gold-soft), var(--gold));
        color: #0a0a0a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
    }

    .brand-name {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.15rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }

    .nav-links {
        display: none;
        gap: 28px;
    }

    .nav-links a {
        font-size: 13px;
        color: var(--muted);
        text-decoration: none;
        transition: color 0.2s;
    }

    .nav-links a:hover { color: var(--gold); }

    .nav-actions {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .link-quiet {
        font-size: 13px;
        color: var(--muted);
        text-decoration: none;
    }

    .link-quiet:hover { color: var(--text); }

    /* ===== BUTTONS ===== */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 13px 24px;
        border-radius: 999px;
        font-size: 13.5px;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.25s, background 0.25s, border-color 0.25s;
        cursor: pointer;
    }

    .btn.gold {
        background: linear-gradient(135deg, var(--gold-soft), var(--gold));
        color: #0a0a0a;
        box-shadow: 0 8px 28px rgba(212, 175, 55, 0.25);
    }

    .btn.gold:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 34px rgba(212, 175, 55, 0.38);
    }

    .btn.outline {
        border: 1px solid rgba(212, 175, 55, 0.45);
        color: var(--gold);
    }

    .btn.outline:hover {
        background: rgba(212, 175, 55, 0.1);
        border-color: var(--gold);
    }

    .btn.small { padding: 9px 18px; font-size: 12.5px; }
    .btn.big { padding: 16px 34px; font-size: 14.5px; }
    .btn.block { width: 100%; margin-top: 20px; }

    a:focus-visible,
    summary:focus-visible,
    .btn:focus-visible {
        outline: 2px solid var(--gold);
        outline-offset: 3px;
    }

    /* ===== HERO ===== */
    .hero {
        position: relative;
        padding: 40px 24px 80px;
        overflow: hidden;
    }

    .glow {
        position: absolute;
        border-radius: 50%;
        filter: blur(90px);
        pointer-events: none;
    }

    .glow-a {
        width: 420px;
        height: 420px;
        background: rgba(212, 175, 55, 0.16);
        top: -120px;
        right: -80px;
    }

    .glow-b {
        width: 360px;
        height: 360px;
        background: rgba(120, 70, 20, 0.2);
        bottom: -140px;
        left: -100px;
    }

    .hero-grid {
        position: relative;
        max-width: 1120px;
        margin: 0 auto;
        display: grid;
        gap: 56px;
        align-items: center;
    }

    .eyebrow {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 10.5px;
        letter-spacing: 0.34em;
        color: var(--gold);
        margin: 0 0 20px;
    }

    .eyebrow.center { justify-content: center; }

    .diamond {
        width: 6px;
        height: 6px;
        background: var(--gold);
        transform: rotate(45deg);
        flex-shrink: 0;
    }

    .hero-title {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: clamp(2.3rem, 7.5vw, 4rem);
        font-weight: 500;
        line-height: 1.08;
        margin: 0 0 20px;
    }

    .hero-text {
        font-size: 15.5px;
        line-height: 1.75;
        color: var(--muted);
        max-width: 520px;
        margin: 0 0 30px;
    }

    .hero-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 28px;
    }

    .chips {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .chips li {
        font-size: 11.5px;
        color: var(--muted);
        border: 1px solid var(--line);
        border-radius: 999px;
        padding: 6px 14px;
        background: rgba(255, 255, 255, 0.03);
    }

    /* ===== PHONE MOCKUP ===== */
    .hero-visual {
        position: relative;
        display: flex;
        justify-content: center;
        padding: 10px 0 30px;
    }

    .phone {
        width: 250px;
        aspect-ratio: 9 / 18.5;
        border-radius: 38px;
        padding: 9px;
        background: #0c0c0c;
        border: 1px solid rgba(212, 175, 55, 0.4);
        box-shadow:
            0 40px 80px rgba(0, 0, 0, 0.6),
            0 0 90px rgba(212, 175, 55, 0.14);
        transform: rotate(-3deg);
    }

    .screen {
        position: relative;
        height: 100%;
        border-radius: 30px;
        overflow: hidden;
        background:
            radial-gradient(circle at 50% 0%, rgba(212, 175, 55, 0.2), transparent 60%),
            linear-gradient(180deg, #17120a, #0a0a0a);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 20px;
    }

    .notch {
        position: absolute;
        top: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 70px;
        height: 18px;
        border-radius: 12px;
        background: #000;
    }

    .mini-progress {
        position: absolute;
        top: 34px;
        left: 18px;
        right: 18px;
        display: flex;
        gap: 4px;
    }

    .mini-progress span {
        flex: 1;
        height: 2px;
        border-radius: 2px;
        background: rgba(255, 255, 255, 0.15);
    }

    .mini-progress span.on { background: var(--gold); }

    .m-label {
        font-size: 7px;
        letter-spacing: 0.4em;
        color: var(--gold);
        margin: 0 0 14px;
    }

    .m-names {
        font-family: "Cormorant Garamond", serif;
        font-size: 2.4rem;
        font-weight: 400;
        line-height: 1;
        margin: 0;
        color: #fff;
    }

    .m-amp {
        font-family: "Cormorant Garamond", serif;
        font-style: italic;
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.5);
        margin: 6px 0;
    }

    .m-orn,
    .orn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin: 16px 0 12px;
    }

    .m-orn i,
    .orn i {
        width: 26px;
        height: 1px;
        background: linear-gradient(to right, transparent, var(--gold), transparent);
    }

    .m-orn b,
    .orn b {
        width: 5px;
        height: 5px;
        background: var(--gold);
        transform: rotate(45deg);
    }

    .m-date {
        font-size: 8px;
        letter-spacing: 0.3em;
        color: rgba(255, 255, 255, 0.65);
        margin: 0 0 20px;
    }

    .m-pill {
        font-size: 7.5px;
        letter-spacing: 0.25em;
        text-transform: uppercase;
        color: #0a0a0a;
        background: var(--gold);
        padding: 8px 16px;
        border-radius: 999px;
        font-weight: 600;
    }

    .float-chip {
        position: absolute;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-radius: 14px;
        background: rgba(20, 17, 12, 0.85);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(212, 175, 55, 0.35);
        font-size: 12px;
        color: var(--text);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
        animation: float 6s ease-in-out infinite;
    }

    .chip-a { top: 14%; left: -2%; }
    .chip-b { bottom: 16%; right: -2%; animation-delay: -3s; }

    .chip-ico {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(34, 197, 94, 0.18);
        color: #4ade80;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mini-qr {
        display: grid;
        grid-template-columns: repeat(9, 1fr);
        width: 34px;
        height: 34px;
        padding: 3px;
        background: #fff;
        border-radius: 4px;
        gap: 0;
    }

    .mini-qr span { background: transparent; }
    .mini-qr span.filled { background: #0a0a0a; }

    .mock-note {
        position: absolute;
        bottom: 0;
        font-size: 10px;
        letter-spacing: 0.2em;
        color: rgba(255, 255, 255, 0.3);
        margin: 0;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    /* ===== SECTIONS ===== */
    .section {
        padding: 80px 24px;
        border-top: 1px solid var(--line);
    }

    .section.alt { background: var(--ink-2); }
    .section.tight { padding: 40px 24px; }

    .section-head {
        max-width: 720px;
        margin: 0 auto 48px;
        text-align: center;
    }

    .section-title {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: clamp(1.8rem, 5vw, 2.7rem);
        font-weight: 500;
        line-height: 1.15;
        margin: 0;
    }

    /* ===== BENTO ===== */
    .bento {
        max-width: 1120px;
        margin: 0 auto;
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }

    .card {
        position: relative;
        padding: 28px;
        border-radius: 20px;
        background: linear-gradient(160deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.015));
        border: 1px solid var(--line);
        transition: border-color 0.3s, transform 0.3s;
    }

    .card:hover {
        border-color: rgba(212, 175, 55, 0.5);
        transform: translateY(-4px);
    }

    .icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gold);
        background: rgba(212, 175, 55, 0.08);
        border: 1px solid rgba(212, 175, 55, 0.25);
        margin-bottom: 18px;
    }

    .card h3 {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.35rem;
        font-weight: 600;
        margin: 0 0 8px;
    }

    .card p {
        font-size: 13.5px;
        line-height: 1.7;
        color: var(--muted);
        margin: 0;
    }

    /* ===== TIMELINE ===== */
    .timeline {
        list-style: none;
        max-width: 720px;
        margin: 0 auto;
        padding: 0 0 0 26px;
        position: relative;
    }

    .timeline::before {
        content: "";
        position: absolute;
        left: 0;
        top: 8px;
        bottom: 8px;
        width: 1px;
        background: linear-gradient(to bottom, transparent, var(--gold), transparent);
        opacity: 0.5;
    }

    .t-row {
        position: relative;
        display: flex;
        gap: 20px;
        padding: 14px 0 26px;
    }

    .t-row::before {
        content: "";
        position: absolute;
        left: -30px;
        top: 24px;
        width: 9px;
        height: 9px;
        background: var(--gold);
        transform: rotate(45deg);
        box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.14);
    }

    .t-no {
        font-family: "Cormorant Garamond", serif;
        font-size: 2.2rem;
        line-height: 1;
        color: rgba(212, 175, 55, 0.5);
        min-width: 48px;
    }

    .t-body strong {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.3rem;
        font-weight: 600;
    }

    .t-body p {
        font-size: 13.5px;
        line-height: 1.7;
        color: var(--muted);
        margin: 6px 0 0;
    }

    /* ===== DEMO BAND ===== */
    .demo-band {
        max-width: 1120px;
        margin: 0 auto;
        padding: 36px;
        border-radius: 24px;
        border: 1px solid rgba(212, 175, 55, 0.4);
        background:
            radial-gradient(circle at 0% 0%, rgba(212, 175, 55, 0.14), transparent 55%),
            rgba(255, 255, 255, 0.02);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }

    .band-title {
        font-family: "Cormorant Garamond", serif;
        font-size: clamp(1.4rem, 4vw, 2rem);
        font-weight: 500;
        margin: 0;
        max-width: 520px;
        line-height: 1.2;
    }

    /* ===== PACKAGES ===== */
    .packages {
        max-width: 1120px;
        margin: 0 auto;
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
        align-items: stretch;
    }

    .package {
        display: flex;
        flex-direction: column;
        padding: 30px 26px;
        border-radius: 22px;
        background: linear-gradient(170deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.012));
        border: 1px solid var(--line);
    }

    .package.featured {
        border-color: rgba(212, 175, 55, 0.7);
        background:
            radial-gradient(circle at 50% 0%, rgba(212, 175, 55, 0.14), transparent 60%),
            linear-gradient(170deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.015));
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5), 0 0 60px rgba(212, 175, 55, 0.1);
    }

    .package h3 {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 8px;
    }

    .price {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.9rem;
        font-weight: 500;
        color: var(--gold);
        margin: 0 0 10px;
    }

    .pkg-desc {
        font-size: 13px;
        line-height: 1.6;
        color: var(--muted);
        margin: 0 0 16px;
    }

    .package ul {
        list-style: none;
        padding: 18px 0 0;
        margin: 0;
        border-top: 1px solid var(--line);
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
    }

    .package li {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        font-size: 13px;
        line-height: 1.5;
        color: rgba(255, 255, 255, 0.78);
    }

    .package li svg {
        color: var(--gold);
        flex-shrink: 0;
        margin-top: 3px;
    }

    /* ===== FAQ ===== */
    .faq {
        max-width: 720px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    details {
        border: 1px solid var(--line);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.025);
        transition: border-color 0.25s;
    }

    details[open] { border-color: rgba(212, 175, 55, 0.5); }

    summary {
        list-style: none;
        cursor: pointer;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        font-size: 14.5px;
        font-weight: 500;
    }

    summary::-webkit-details-marker { display: none; }

    .plus {
        position: relative;
        width: 14px;
        height: 14px;
        flex-shrink: 0;
    }

    .plus::before,
    .plus::after {
        content: "";
        position: absolute;
        background: var(--gold);
        top: 50%;
        left: 0;
        width: 100%;
        height: 1.5px;
        transition: transform 0.3s;
    }

    .plus::after { transform: rotate(90deg); }
    details[open] .plus::after { transform: rotate(0deg); }

    details p {
        font-size: 13.5px;
        line-height: 1.75;
        color: var(--muted);
        margin: 0;
        padding: 0 20px 20px;
    }

    /* ===== FINAL CTA ===== */
    .final {
        position: relative;
        padding: 100px 24px;
        text-align: center;
        border-top: 1px solid var(--line);
        background: radial-gradient(circle at 50% 100%, rgba(212, 175, 55, 0.14), transparent 60%);
    }

    .final-title {
        font-family: "Cormorant Garamond", serif;
        font-size: clamp(2rem, 6vw, 3.2rem);
        font-weight: 500;
        line-height: 1.15;
        margin: 0 0 14px;
    }

    .final-text {
        font-size: 14.5px;
        color: var(--muted);
        margin: 0 0 30px;
    }

    /* ===== FOOTER ===== */
    .footer {
        max-width: 1120px;
        margin: 0 auto;
        padding: 28px 24px 90px;
        border-top: 1px solid var(--line);
        display: flex;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 12.5px;
        color: rgba(255, 255, 255, 0.45);
    }

    .footer p { margin: 0; }

    .footer-links { display: flex; gap: 18px; }

    .footer a {
        text-decoration: none;
        color: rgba(255, 255, 255, 0.55);
    }

    .footer a:hover { color: var(--gold); }

    .muted { color: rgba(255, 255, 255, 0.45); font-size: 13px; }
    .center { text-align: center; }

    /* ===== FLOATING WHATSAPP ===== */
    .wa-float {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 60;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #25d366;
        color: #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
        transition: transform 0.2s;
    }

    .wa-float:hover { transform: scale(1.08); }

    /* ===== REVEAL ===== */
    .reveal {
        opacity: 0;
        transform: translateY(26px);
        transition:
            opacity 0.8s ease var(--d, 0ms),
            transform 0.8s ease var(--d, 0ms);
    }

    .reveal:global(.in) {
        opacity: 1;
        transform: translateY(0);
    }

    /* ===== RESPONSIVE ===== */
    @media (min-width: 900px) {
        .nav-links { display: flex; }

        .hero { padding: 72px 24px 110px; }

        .hero-grid { grid-template-columns: 1.1fr 0.9fr; }

        .phone { width: 290px; }

        .package.featured { transform: translateY(-10px); }

        .package.featured:global(.in) { transform: translateY(-10px); }
    }

    @media (max-width: 899px) {
        .hero-copy { text-align: center; }
        .eyebrow { justify-content: center; }
        .hero-text { margin-left: auto; margin-right: auto; }
        .hero-actions, .chips { justify-content: center; }
        .chip-a { left: 2%; }
        .chip-b { right: 2%; }
    }

    @media (max-width: 520px) {
        .nav-inner { padding: 12px 16px; }
        .section { padding: 64px 18px; }
        .demo-band { padding: 26px 22px; }
        .t-no { font-size: 1.8rem; min-width: 40px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .reveal {
            opacity: 1;
            transform: none;
            transition: none;
        }

        .float-chip { animation: none; }
    }
</style>