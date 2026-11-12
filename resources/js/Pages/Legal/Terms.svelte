<script>
    let { title = "Syarat & Ketentuan", businessName = null } = $props();
</script>

<svelte:head><title>{title}</title></svelte:head>

<main class="legal">
    <a class="back" href="/">← Kembali</a>
    <h1>{title}</h1>

    <p class="notice">
        TODO(owner): legal review before publishing. Teks di halaman ini masih
        placeholder dan belum ditinjau secara hukum.
    </p>

    <h2>1. Layanan</h2>
    <p>
        {businessName ?? "Penyedia layanan"} menyediakan jasa pembuatan undangan
        pernikahan digital{` `}yang dapat diakses melalui tautan pribadi setiap tamu.
    </p>

    <h2>2. Pemesanan dan pembayaran</h2>
    <p>
        TODO(owner): ketentuan pemesanan, pembayaran, pembatalan, dan
        pengembalian dana.
    </p>

    <h2>3. Masa aktif undangan</h2>
    <p>
        TODO(owner): jelaskan masa aktif, perpanjangan, dan konsekuensi bila
        undangan berakhir.
    </p>

    <h2>4. Tanggung jawab</h2>
    <p>TODO(owner): batasan tanggung jawab dan hal-hal yang dikecualikan.</p>

    <h2>5. Kontak</h2>
    <p>TODO(owner): alamat email dan/atau nomor WhatsApp resmi.</p>
</main>

<style>
    .legal {
        max-width: 720px;
        margin: 0 auto;
        padding: 48px 24px 64px;
        font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
        color: #1f1f1f;
        line-height: 1.7;
        background: #fbfaf7;
        min-height: 100vh;
    }

    .back {
        font-size: 13px;
        color: #5a5a5a;
        text-decoration: none;
    }

    h1 {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: 2rem;
        font-weight: 500;
        margin: 18px 0 20px;
    }

    h2 {
        font-size: 15px;
        margin: 28px 0 8px;
    }

    p {
        font-size: 14px;
        color: #4a4a4a;
        margin: 0 0 10px;
    }

    .notice {
        background: #fff7e6;
        border: 1px solid #f0d9a8;
        color: #8a6a1f;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 13px;
    }
</style>
