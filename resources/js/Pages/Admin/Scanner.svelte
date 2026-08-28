<script>
    import { onMount, onDestroy } from "svelte";
    import { fade, scale, fly } from "svelte/transition";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";
    import Toast from "../../Components/Admin/Toast.svelte";
    import { toast } from "../../Components/Admin/toast.js";

    let scannerContainer = $state();
    let html5QrCode;
    let isScanning = $state(false);
    let isProcessing = $state(false);

    let resultStatus = $state(null); // 'success' | 'duplicate' | 'invalid' | null
    let resultMessage = $state("");
    let resultGuest = $state(null);

    let manualCode = $state("");
    let showManualInput = $state(false);

    let todayCheckedIn = $state(0);

    async function startScanner() {
        const { Html5Qrcode } = await import("html5-qrcode");
        html5QrCode = new Html5Qrcode("qr-reader");
        try {
            await html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                onScanFailure,
            );
            isScanning = true;
        } catch (err) {
            console.error("Camera error:", err);
            resultStatus = "invalid";
            resultMessage =
                "Tidak bisa mengakses kamera. Gunakan input manual.";
            showManualInput = true;
        }
    }

    async function stopScanner() {
        if (html5QrCode && isScanning) {
            await html5QrCode.stop();
            isScanning = false;
        }
    }

    function onScanSuccess(decodedText) {
        let code = decodedText;
        try {
            const url = new URL(decodedText);
            code = url.searchParams.get("guest") || decodedText;
        } catch (e) {}
        processCheckin(code);
    }

    function onScanFailure(error) {
        // silent
    }

    async function processCheckin(code) {
        if (isProcessing) return;
        isProcessing = true;
        if (isScanning) await stopScanner();

        try {
            const response = await fetch("/admin/scanner/checkin", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({ code }),
            });
            const data = await response.json();
            resultStatus = data.status;
            resultMessage = data.message;
            resultGuest = data.guest || null;

            if (data.status === "success") {
                todayCheckedIn++;
                if (navigator.vibrate) navigator.vibrate([50, 50, 50]);
            } else {
                if (navigator.vibrate) navigator.vibrate(200);
            }
        } catch (err) {
            resultStatus = "invalid";
            resultMessage = "Terjadi kesalahan koneksi. Coba lagi.";
        }
        isProcessing = false;
    }

    function resetAndScanAgain() {
        resultStatus = null;
        resultMessage = "";
        resultGuest = null;
        manualCode = "";
        startScanner();
    }

    function submitManual() {
        if (!manualCode.trim()) return;
        processCheckin(manualCode.trim());
    }

    onMount(async () => {
        // Ambil jumlah check-in HARI INI dari database, bukan mulai dari 0
        try {
            const response = await fetch("/admin/scanner/stats");
            const data = await response.json();
            todayCheckedIn = data.count || 0;
        } catch (err) {
            console.error("Gagal ambil stats:", err);
        }

        startScanner();
    });
    onDestroy(() => stopScanner());
</script>

<AdminLayout title="Scanner" breadcrumbs={["Scanner"]}>
    <Toast />

    <div class="scanner-layout">
        <!-- LEFT: SCANNER / RESULT -->
        <div class="scanner-main">
            <!-- Status Header -->
            <div class="status-header">
                <div class="status-dot-wrap">
                    <span
                        class="status-dot"
                        class:dot-active={isScanning}
                        class:dot-processing={isProcessing}
                        class:dot-idle={!isScanning &&
                            !isProcessing &&
                            !resultStatus}
                    ></span>
                    <span class="status-label">
                        {#if isProcessing}
                            Memproses...
                        {:else if isScanning}
                            Kamera Aktif
                        {:else if resultStatus}
                            Hasil Scan
                        {:else}
                            Menghidupkan Kamera...
                        {/if}
                    </span>
                </div>
                {#if isScanning || resultStatus}
                    <button
                        class="btn-manual-toggle"
                        onclick={() => (showManualInput = !showManualInput)}
                    >
                        {showManualInput ? "Tutup Manual" : "Input Manual"}
                    </button>
                {/if}
            </div>

            <!-- CAMERA VIEW -->
            {#if !resultStatus}
                <div class="camera-card" transition:fade={{ duration: 300 }}>
                    <div id="qr-reader" bind:this={scannerContainer}></div>

                    {#if isProcessing}
                        <div
                            class="processing-layer"
                            transition:fade={{ duration: 200 }}
                        >
                            <div class="proc-spinner"></div>
                            <p>Memproses check-in...</p>
                        </div>
                    {/if}

                    <p class="scan-hint">Arahkan kamera ke QR Code tamu</p>
                </div>
            {/if}

            <!-- RESULT CARD -->
            {#if resultStatus}
                <div
                    class="result-wrap"
                    transition:scale={{ duration: 350, start: 0.96 }}
                >
                    {#if resultStatus === "success"}
                        <div class="result-card result-success">
                            <div class="result-icon-wrap icon-success">
                                <svg
                                    width="28"
                                    height="28"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </div>
                            <h2 class="result-heading">Check-in Berhasil!</h2>
                            <div class="result-guest-box">
                                <p class="guest-big-name">
                                    {resultGuest?.name}
                                </p>
                                {#if resultGuest?.category}
                                    <span class="tag-category"
                                        >{resultGuest.category}</span
                                    >
                                {/if}
                                <div class="guest-tags">
                                    {#if resultGuest?.city}<span class="tag"
                                            >{resultGuest.city}</span
                                        >{/if}
                                    <span class="tag"
                                        >Quota: {resultGuest?.quota} orang</span
                                    >
                                </div>
                                <p class="guest-time">
                                    <svg
                                        width="13"
                                        height="13"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="10"
                                        /><polyline points="12 6 12 12 16 14" />
                                    </svg>
                                    {resultGuest?.checked_in_at}
                                </p>
                            </div>
                        </div>
                    {:else if resultStatus === "duplicate"}
                        <div class="result-card result-duplicate">
                            <div class="result-icon-wrap icon-warning">
                                <svg
                                    width="28"
                                    height="28"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                                    />
                                    <line x1="12" y1="9" x2="12" y2="13" /><line
                                        x1="12"
                                        y1="17"
                                        x2="12.01"
                                        y2="17"
                                    />
                                </svg>
                            </div>
                            <h2 class="result-heading">Sudah Check-in!</h2>
                            <div class="result-guest-box">
                                <p class="guest-big-name">
                                    {resultGuest?.name}
                                </p>
                                {#if resultGuest?.category}
                                    <span class="tag-category"
                                        >{resultGuest.category}</span
                                    >
                                {/if}
                                {#if resultGuest?.city}<p class="guest-city">
                                        {resultGuest.city}
                                    </p>{/if}
                                <p class="guest-time">
                                    <svg
                                        width="13"
                                        height="13"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="10"
                                        /><polyline points="12 6 12 12 16 14" />
                                    </svg>
                                    Check-in jam {resultGuest?.checked_in_at}
                                </p>
                            </div>
                            <div class="warning-banner">
                                ⚠️ Jangan berikan souvenir lagi
                            </div>
                        </div>
                    {:else}
                        <div class="result-card result-invalid">
                            <div class="result-icon-wrap icon-danger">
                                <svg
                                    width="28"
                                    height="28"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <line x1="18" y1="6" x2="6" y2="18" /><line
                                        x1="6"
                                        y1="6"
                                        x2="18"
                                        y2="18"
                                    />
                                </svg>
                            </div>
                            <h2 class="result-heading">QR Tidak Valid</h2>
                            <p class="result-msg">{resultMessage}</p>
                        </div>
                    {/if}

                    <button class="btn-scan-again" onclick={resetAndScanAgain}>
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="1 4 1 10 7 10" /><path
                                d="M3.51 15a9 9 0 1 0 .49-3.54"
                            />
                        </svg>
                        Scan Lagi
                    </button>
                </div>
            {/if}

            <!-- MANUAL INPUT -->
            {#if showManualInput}
                <div
                    class="manual-card"
                    transition:fly={{ y: 16, duration: 250 }}
                >
                    <p class="manual-label">Input Manual Kode Tamu</p>
                    <div class="manual-row">
                        <input
                            type="text"
                            class="manual-input"
                            bind:value={manualCode}
                            placeholder="Masukkan kode tamu..."
                            onkeydown={(e) =>
                                e.key === "Enter" && submitManual()}
                            autofocus
                        />
                        <button
                            class="btn-manual-submit"
                            onclick={submitManual}
                            disabled={isProcessing}
                        >
                            {isProcessing ? "..." : "Cek"}
                        </button>
                    </div>
                </div>
            {/if}
        </div>

        <!-- RIGHT: STATS SIDEBAR -->
        <div class="scanner-sidebar">
            <div class="stat-panel">
                <p class="stat-panel-label">Check-in Hari Ini</p>
                <p class="stat-panel-number">{todayCheckedIn}</p>
                <p class="stat-panel-sub">sejak halaman dibuka</p>
            </div>

            <div class="info-panel">
                <h3 class="info-panel-title">Panduan Scanner</h3>
                <ul class="info-list">
                    <li>Pastikan kamera menghadap QR Code dengan jelas</li>
                    <li>
                        Gunakan <strong>Input Manual</strong> jika kamera tidak tersedia
                    </li>
                    <li>
                        Tamu dengan status "Sudah Check-in" tidak perlu souvenir
                        lagi
                    </li>
                    <li>HTTPS diperlukan untuk akses kamera di mobile</li>
                </ul>
            </div>
        </div>
    </div>
</AdminLayout>

<style>
    .scanner-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 20px;
        align-items: start;
    }

    /* STATUS HEADER */
    .status-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 14px 18px;
        margin-bottom: 16px;
        box-shadow: var(--shadow);
    }

    .status-dot-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-primary);
    }

    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #d1d5db;
        flex-shrink: 0;
    }

    .status-dot.dot-active {
        background: var(--success);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        animation: pulse 2s infinite;
    }

    .status-dot.dot-processing {
        background: var(--warning);
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        animation: pulse 1s infinite;
    }

    .status-dot.dot-idle {
        background: #d1d5db;
    }

    @keyframes pulse {
        0%,
        100% {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        50% {
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.1);
        }
    }

    .status-label {
        color: var(--text-primary);
    }

    .btn-manual-toggle {
        background: var(--card);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        padding: 6px 14px;
        border-radius: 7px;
        font-size: 12.5px;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.15s;
    }

    .btn-manual-toggle:hover {
        background: var(--bg);
        color: var(--text-primary);
    }

    /* CAMERA CARD */
    .camera-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 20px;
        position: relative;
        overflow: hidden;
        margin-bottom: 16px;
    }

    :global(#qr-reader) {
        width: 100%;
        border-radius: 10px;
        overflow: hidden;
    }

    :global(#qr-reader video) {
        border-radius: 10px;
    }

    :global(#qr-reader__scan_region) {
        background: transparent !important;
    }

    .processing-layer {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.92);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 14px;
        border-radius: var(--radius);
        font-size: 14px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .proc-spinner {
        width: 36px;
        height: 36px;
        border: 3px solid #e5e7eb;
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .scan-hint {
        text-align: center;
        font-size: 13px;
        color: var(--text-muted);
        margin: 12px 0 0;
    }

    /* RESULT */
    .result-wrap {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 16px;
    }

    .result-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 28px 24px;
        text-align: center;
        box-shadow: var(--shadow);
    }

    .result-success {
        border-color: #a7f3d0;
        background: #f0fdf9;
    }
    .result-duplicate {
        border-color: #fde68a;
        background: #fffbeb;
    }
    .result-invalid {
        border-color: #fecaca;
        background: #fff1f1;
    }

    .result-icon-wrap {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }

    .icon-success {
        background: #d1fae5;
        color: #059669;
    }
    .icon-warning {
        background: #fef3c7;
        color: #d97706;
    }
    .icon-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .result-heading {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 16px;
        color: var(--text-primary);
    }

    .result-guest-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .guest-big-name {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .guest-city {
        font-size: 13.5px;
        color: var(--text-secondary);
        margin: 0;
    }

    .guest-tags {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .tag {
        background: white;
        border: 1px solid var(--border);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        color: var(--text-secondary);
    }
    .tag-category {
        display: inline-block;
        background: rgba(37, 99, 235, 0.08);
        color: var(--primary);
        border: 1px solid rgba(37, 99, 235, 0.2);
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .guest-time {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        color: var(--text-secondary);
        margin: 4px 0 0;
    }

    .warning-banner {
        margin-top: 16px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 13px;
        color: #92400e;
        font-weight: 500;
    }

    .result-msg {
        font-size: 13.5px;
        color: var(--text-secondary);
        margin: 0;
    }

    .btn-scan-again {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        height: 44px;
        border-radius: var(--radius);
        background: var(--primary);
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.15s;
        font-family: inherit;
    }

    .btn-scan-again:hover {
        background: var(--primary-hover);
    }

    /* MANUAL INPUT */
    .manual-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 18px 20px;
        box-shadow: var(--shadow);
    }

    .manual-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--text-secondary);
        margin: 0 0 10px;
    }

    .manual-row {
        display: flex;
        gap: 8px;
    }

    .manual-input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 14px;
        font-family: inherit;
        background: var(--card);
        color: var(--text-primary);
        transition: border-color 0.15s;
    }

    .manual-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    }

    .btn-manual-submit {
        padding: 10px 20px;
        border-radius: var(--radius-sm);
        background: var(--primary);
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        font-family: inherit;
        transition: background 0.15s;
    }

    .btn-manual-submit:hover:not(:disabled) {
        background: var(--primary-hover);
    }
    .btn-manual-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* RIGHT SIDEBAR */
    .scanner-sidebar {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .stat-panel {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 24px 20px;
        text-align: center;
        box-shadow: var(--shadow);
    }

    .stat-panel-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0 0 6px;
    }

    .stat-panel-number {
        font-size: 48px;
        font-weight: 700;
        color: var(--primary);
        line-height: 1;
        margin: 0 0 6px;
    }

    .stat-panel-sub {
        font-size: 11.5px;
        color: var(--text-muted);
        margin: 0;
    }

    .info-panel {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 18px 20px;
        box-shadow: var(--shadow);
    }

    .info-panel-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 12px;
    }

    .info-list {
        margin: 0;
        padding: 0 0 0 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .info-list li {
        font-size: 12.5px;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    /* RESPONSIVE */
    @media (max-width: 900px) {
        .scanner-layout {
            grid-template-columns: 1fr;
        }

        .scanner-sidebar {
            /* order: -1; */
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 560px) {
        .scanner-sidebar {
            grid-template-columns: 1fr;
        }
    }
</style>
