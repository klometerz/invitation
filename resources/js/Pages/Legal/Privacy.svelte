<script>
    let { title = "Kebijakan Privasi", businessName = null } = $props();
</script>

<svelte:head><title>{title}</title></svelte:head>

<main class="legal">
    <a class="back" href="/">← Kembali</a>
    <h1>{title}</h1>

    <p class="notice">
        TODO(owner): legal review before publishing. Teks di halaman ini masih
        placeholder dan belum ditinjau secara hukum.
    </p>

    <h2>1. Data yang dikumpulkan</h2>
    <p>
        Nama tamu, kota, kategori, konfirmasi kehadiran, ucapan, dan foto
        photobooth yang diunggah oleh tamu.
    </p>

    <h2>2. Tujuan penggunaan</h2>
    <p>
        Data dipakai untuk menampilkan undangan, mengelola buku tamu, dan
        menyiapkan buku kenangan acara.
    </p>

    <h2>3. Penyimpanan dan retensi</h2>
    <p>
        TODO(owner): jelaskan periode penyimpanan dan mekanisme penghapusan
        data acara yang sudah diarsipkan.
    </p>

    <h2>4. Berbagi data</h2>
    <p>
        Data hanya dapat diakses oleh pengelola acara terkait dan
        {businessName ?? "penyedia layanan"}.
    </p>

    <h2>5. Hak tamu</h2>
    <p>TODO(owner): cara tamu meminta koreksi atau penghapusan data.</p>
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
