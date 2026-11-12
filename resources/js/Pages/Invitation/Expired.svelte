<script>
    let { invitation = null } = $props();
</script>

<svelte:head>
    <title>Undangan sudah berakhir</title>
</svelte:head>

<main class="expired">
    <div class="card">
        <p class="eyebrow">UNDANGAN BERAKHIR</p>
        <h1>Undangan sudah berakhir</h1>
        <p class="body">
            Masa berlaku undangan{invitation?.title ? ` "${invitation.title}"` : ""}
            telah selesai. Terima kasih atas perhatian dan doa restunya.
        </p>
        <p class="hint">
            Jika Anda merasa ini keliru, silakan hubungi kami melalui kontak
            yang tertera pada undangan.
        </p>
    </div>
</main>

<style>
    .expired {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0a0a0a;
        padding: 24px;
        font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .card {
        background: #141414;
        border: 1px solid rgba(212, 175, 55, 0.25);
        border-radius: 16px;
        padding: 40px 32px;
        max-width: 440px;
        width: 100%;
        text-align: center;
        color: #e5e5e5;
    }

    .eyebrow {
        font-size: 10px;
        letter-spacing: 0.35em;
        color: #d4af37;
        margin: 0 0 14px;
    }

    h1 {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: 1.9rem;
        font-weight: 500;
        margin: 0 0 14px;
        color: #fff;
    }

    .body {
        font-size: 14px;
        line-height: 1.7;
        color: #a3a3a3;
        margin: 0 0 18px;
    }

    .hint {
        font-size: 12.5px;
        color: #6b6b6b;
        margin: 0;
    }
</style>
