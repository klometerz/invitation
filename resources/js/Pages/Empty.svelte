<script>
    // Shown on `/` when there is no invitation yet (fresh install).
    // P3-T6 replaces the whole landing route with the marketing page.
</script>

<main class="empty-page">
    <div class="card">
        <h1>Belum ada undangan</h1>
        <p>
            Belum ada undangan yang dibuat. Masuk ke panel admin untuk membuat
            undangan pertama.
        </p>
        <a class="btn" href="/login">Masuk ke Admin</a>
    </div>
</main>

<style>
    .empty-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #111827;
        padding: 24px;
        font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .card {
        background: #fff;
        border-radius: 16px;
        padding: 40px 32px;
        max-width: 420px;
        width: 100%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    }

    h1 {
        font-size: 20px;
        margin: 0 0 10px;
        color: #111827;
    }

    p {
        font-size: 14px;
        line-height: 1.6;
        color: #6b7280;
        margin: 0 0 22px;
    }

    .btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 10px;
        background: #2563eb;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
    }
</style>
