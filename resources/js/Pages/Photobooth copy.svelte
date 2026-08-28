<script>
    import { onMount, onDestroy } from "svelte";
    import { fade, scale } from "svelte/transition";

    let {
        accessGranted = false,
        reason = null,
        guestName = null,
        guestData = null,
    } = $props();

    let videoEl = $state(null);
    let canvasEl = $state(null);
    let stream = null;

    let stage = $state("intro"); // intro | camera | countdown | preview | saving | success | error
    let countdownNumber = $state(3);
    let capturedImage = $state(null); // data URL hasil capture
    let message = $state("");
    let errorMsg = $state("");

    async function startCamera() {
        stage = "camera";
        errorMsg = "";

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user",
                    width: { ideal: 1080 },
                    height: { ideal: 1080 },
                },
                audio: false,
            });
            videoEl.srcObject = stream;
        } catch (err) {
            console.error("Camera error:", err);
            errorMsg =
                "Tidak bisa mengakses kamera. Pastikan Anda memberi izin akses kamera dan menggunakan koneksi HTTPS.";
            stage = "error";
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }
    }

    async function startCountdown() {
        stage = "countdown";
        countdownNumber = 3;

        for (let i = 3; i > 0; i--) {
            countdownNumber = i;
            await new Promise((resolve) => setTimeout(resolve, 800));
        }

        capturePhoto();
    }

    function capturePhoto() {
        const video = videoEl;
        const canvas = canvasEl;
        const size = 1000; // output square

        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext("2d");

        // Crop video jadi square, mirror biar natural (kayak selfie cam)
        const videoAspect = video.videoWidth / video.videoHeight;
        let sx, sy, sWidth, sHeight;

        if (videoAspect > 1) {
            sHeight = video.videoHeight;
            sWidth = sHeight;
            sx = (video.videoWidth - sWidth) / 2;
            sy = 0;
        } else {
            sWidth = video.videoWidth;
            sHeight = sWidth;
            sx = 0;
            sy = (video.videoHeight - sHeight) / 2;
        }

        ctx.save();
        ctx.scale(-1, 1); // mirror
        ctx.drawImage(video, sx, sy, sWidth, sHeight, -size, 0, size, size);
        ctx.restore();

        // ===== FRAME OVERLAY (Gold Border + Ornament) =====
        drawFrame(ctx, size);

        capturedImage = canvas.toDataURL("image/jpeg", 0.9);
        stopCamera();
        stage = "preview";
    }

    function drawFrame(ctx, size) {
        const borderWidth = size * 0.05;

        // Gold border
        ctx.strokeStyle = "#d4af37";
        ctx.lineWidth = borderWidth * 0.3;
        ctx.strokeRect(
            borderWidth * 0.5,
            borderWidth * 0.5,
            size - borderWidth,
            size - borderWidth,
        );

        // Corner ornaments (simple diamond)
        const drawDiamond = (x, y, r) => {
            ctx.save();
            ctx.translate(x, y);
            ctx.rotate(Math.PI / 4);
            ctx.fillStyle = "#d4af37";
            ctx.fillRect(-r / 2, -r / 2, r, r);
            ctx.restore();
        };

        const pad = borderWidth * 1.2;
        drawDiamond(pad, pad, 12);
        drawDiamond(size - pad, pad, 12);
        drawDiamond(pad, size - pad, 12);
        drawDiamond(size - pad, size - pad, 12);

        // Bottom label
        ctx.fillStyle = "#d4af37";
        ctx.font = `${size * 0.04}px 'Cormorant Garamond', serif`;
        ctx.textAlign = "center";
        ctx.fillText("Maulia & Kahfi", size / 2, size - borderWidth * 1.4);

        ctx.font = `${size * 0.022}px Inter, sans-serif`;
        ctx.fillStyle = "rgba(212, 175, 55, 0.8)";
        ctx.fillText("06 . 09 . 2026", size / 2, size - borderWidth * 0.7);
    }

    function retake() {
        capturedImage = null;
        message = "";
        startCamera();
    }

    async function savePhoto() {
        stage = "saving";

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                "";

            const response = await fetch("/photobooth/capture", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken, // ← tambahan ini
                },
                body: JSON.stringify({
                    guest_code: guestData.guest_code,
                    image: capturedImage,
                    message: message.trim(),
                }),
            });

            const result = await response.json();

            if (response.ok) {
                stage = "success";
            } else {
                errorMsg = result.message || "Gagal menyimpan foto.";
                stage = "error";
            }
        } catch (err) {
            console.error(err);
            errorMsg = "Gagal terhubung ke server.";
            stage = "error";
        }
    }

    function downloadPhoto() {
        const link = document.createElement("a");
        link.download = `photobooth-${guestData.name.replace(/\s+/g, "-")}.jpg`;
        link.href = capturedImage;
        link.click();
    }

    async function sharePhoto() {
        try {
            // Convert data URL jadi File object
            const res = await fetch(capturedImage);
            const blob = await res.blob();
            const file = new File(
                [blob],
                `photobooth-${guestData.name.replace(/\s+/g, "-")}.jpg`,
                { type: blob.type },
            );

            // Cek support share file (Android Chrome, iOS Safari 16+)
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({
                    files: [file],
                    title: "Photobooth - Maulia & Kahfi",
                    text: "Momen kami di pernikahan Maulia & Kahfi 💕",
                });
            } else if (navigator.share) {
                // Fallback: share teks aja (browser lama yang cuma support share text)
                await navigator.share({
                    title: "Photobooth - Maulia & Kahfi",
                    text: "Momen kami di pernikahan Maulia & Kahfi 💕",
                });
            } else {
                // Desktop browser biasanya gak support Web Share API sama sekali
                alert(
                    "Fitur share langsung tidak tersedia di browser ini. Silakan download foto dan bagikan secara manual.",
                );
            }
        } catch (err) {
            // AbortError = user batal share (klik cancel di sheet), ini normal, jangan dianggap error
            if (err.name !== "AbortError") {
                console.error("Share failed:", err);
            }
        }
    }

    onDestroy(() => {
        stopCamera();
    });
</script>

<div class="photobooth-page">
    {#if !accessGranted}
        <!-- ACCESS DENIED -->
        <div class="denied-card" transition:fade={{ duration: 400 }}>
            {#if reason === "not_checked_in"}
                <svg
                    width="48"
                    height="48"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#d4af37"
                    stroke-width="1.5"
                >
                    <path d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
                </svg>
                <h2>Belum Check-in</h2>
                <p>
                    Halo {guestName}, silakan melakukan check-in di meja
                    registrasi terlebih dahulu untuk membuka Photobooth.
                </p>
            {:else}
                <svg
                    width="48"
                    height="48"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#d4af37"
                    stroke-width="1.5"
                >
                    <path
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                    />
                </svg>
                <h2>Link Tidak Valid</h2>
                <p>
                    Silakan gunakan link/QR code yang tercetak pada undangan
                    Anda.
                </p>
            {/if}
        </div>
    {:else}
        <!-- MAIN PHOTOBOOTH FLOW -->
        <div class="booth-container">
            {#if stage === "intro"}
                <div class="intro-card" transition:fade={{ duration: 400 }}>
                    <p class="subtitle">WELCOME</p>
                    <h1 class="title">Virtual Photobooth</h1>
                    <p class="welcome-text">
                        Halo, <strong>{guestData.name}</strong>!
                    </p>
                    <p class="desc">
                        Abadikan momen kehadiran Anda di pernikahan kami. Klik
                        tombol di bawah untuk mulai.
                    </p>
                    <button class="btn-primary" onclick={startCamera}>
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
                        >
                            <path
                                d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"
                            />
                            <circle cx="12" cy="13" r="3" />
                        </svg>
                        <span>Mulai Foto</span>
                    </button>
                </div>
            {/if}

            {#if stage === "camera" || stage === "countdown"}
                <div class="camera-view" transition:fade={{ duration: 300 }}>
                    <div class="video-frame">
                        <!-- svelte-ignore a11y_media_has_caption -->
                        <video
                            bind:this={videoEl}
                            autoplay
                            playsinline
                            muted
                            class="camera-video"
                        ></video>

                        {#if stage === "countdown"}
                            <div class="countdown-overlay">
                                {#key countdownNumber}
                                    <span
                                        class="countdown-number"
                                        transition:scale={{ duration: 400 }}
                                    >
                                        {countdownNumber}
                                    </span>
                                {/key}
                            </div>
                        {/if}
                    </div>

                    {#if stage === "camera"}
                        <button class="btn-capture" onclick={startCountdown}>
                            <span class="capture-dot"></span>
                        </button>
                    {/if}
                </div>
            {/if}

            {#if stage === "preview"}
                <div class="preview-card" transition:fade={{ duration: 400 }}>
                    <img
                        src={capturedImage}
                        alt="Hasil foto"
                        class="preview-image"
                    />

                    <div class="message-field">
                        <textarea
                            bind:value={message}
                            placeholder="Tulis ucapan untuk pengantin... (opsional)"
                            rows="2"
                        ></textarea>
                    </div>

                    <div class="preview-actions">
                        <button class="btn-secondary" onclick={retake}
                            >Ambil Ulang</button
                        >
                        <button class="btn-primary" onclick={savePhoto}
                            >Simpan Foto</button
                        >
                    </div>
                </div>
            {/if}

            {#if stage === "saving"}
                <div class="loading-card" transition:fade={{ duration: 300 }}>
                    <div class="spinner"></div>
                    <p>Menyimpan foto...</p>
                </div>
            {/if}

            {#if stage === "success"}
                <div class="success-card" transition:scale={{ duration: 400 }}>
                    <img
                        src={capturedImage}
                        alt="Hasil foto"
                        class="preview-image"
                    />
                    <div class="success-icon">
                        <svg
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#d4af37"
                            stroke-width="3"
                        >
                            <path
                                d="M5 13l4 4L19 7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <h3>Foto Tersimpan!</h3>
                    <div class="success-actions">
                        <button class="btn-secondary" onclick={downloadPhoto}>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            <span>Download</span>
                        </button>
                        <button class="btn-secondary" onclick={sharePhoto}>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="18" cy="5" r="3" />
                                <circle cx="6" cy="12" r="3" />
                                <circle cx="18" cy="19" r="3" />
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                            </svg>
                            <span>Share</span>
                        </button>
                    </div>
                    <button
                        class="btn-primary btn-full"
                        onclick={() => {
                            capturedImage = null;
                            message = "";
                            stage = "intro";
                        }}
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"
                            />
                            <circle cx="12" cy="13" r="3" />
                        </svg>
                        <span>Foto Lagi</span>
                    </button>
                </div>
            {/if}

            {#if stage === "error"}
                <div class="denied-card" transition:fade={{ duration: 300 }}>
                    <svg
                        width="48"
                        height="48"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="#d4af37"
                        stroke-width="1.5"
                    >
                        <path
                            d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"
                        />
                    </svg>
                    <h2>Terjadi Kesalahan</h2>
                    <p>{errorMsg}</p>
                    <button
                        class="btn-primary"
                        onclick={() => (stage = "intro")}>Coba Lagi</button
                    >
                </div>
            {/if}

            <canvas bind:this={canvasEl} style="display: none;"></canvas>
        </div>
    {/if}
</div>

<style>
    @import url("https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Inter:wght@300;400;500&display=swap");

    :global(body) {
        margin: 0;
        font-family: "Inter", sans-serif;
        background: #0a0a0a;
    }

    .photobooth-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .booth-container {
        width: 100%;
        max-width: 420px;
    }

    /* Intro */
    .intro-card,
    .denied-card,
    .loading-card {
        text-align: center;
        padding: 2.5rem 2rem;
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 20px;
    }

    .subtitle {
        letter-spacing: 0.4em;
        font-size: 10px;
        color: #d4af37;
        margin-bottom: 0.75rem;
    }

    .title {
        font-family: "Cormorant Garamond", serif;
        font-size: 2rem;
        color: white;
        font-weight: 300;
        margin-bottom: 1.5rem;
    }

    .welcome-text {
        color: rgba(255, 255, 255, 0.8);
        font-size: 15px;
        margin-bottom: 0.5rem;
    }

    .welcome-text strong {
        color: #d4af37;
    }

    .desc {
        color: rgba(255, 255, 255, 0.55);
        font-size: 13px;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    .denied-card h2 {
        font-family: "Cormorant Garamond", serif;
        color: white;
        font-size: 1.6rem;
        margin: 1rem 0 0.75rem;
    }

    .denied-card p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 13px;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .btn-primary {
        width: 100%;
        padding: 0.9rem;
        background: #d4af37;
        color: black;
        border: none;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.05em;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-secondary {
        width: 100%;
        padding: 0.9rem;
        background: transparent;
        color: #d4af37;
        border: 1px solid #d4af37;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    /* Camera */
    .camera-view {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.5rem;
    }

    .video-frame {
        position: relative;
        width: 100%;
        aspect-ratio: 1;
        border-radius: 20px;
        overflow: hidden;
        border: 2px solid rgba(212, 175, 55, 0.4);
        background: #000;
    }

    .camera-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
    }

    .countdown-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.3);
    }

    .countdown-number {
        font-family: "Cormorant Garamond", serif;
        font-size: 6rem;
        color: white;
        font-weight: 300;
    }

    .btn-capture {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        border: 3px solid #d4af37;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .capture-dot {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: #d4af37;
    }

    /* Preview */
    .preview-card,
    .success-card {
        text-align: center;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 20px;
    }

    .preview-image {
        width: 100%;
        border-radius: 12px;
        margin-bottom: 1.25rem;
    }

    .message-field textarea {
        width: 100%;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.2);
        color: white;
        font-size: 13px;
        border-radius: 12px;
        resize: none;
        font-family: inherit;
        box-sizing: border-box;
        margin-bottom: 1.25rem;
    }

    .message-field textarea::placeholder {
        color: rgba(255, 255, 255, 0.35);
    }

    .preview-actions,
    .success-actions {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .btn-full {
        width: 100%;
    }

    /* Success */
    .success-icon {
        width: 44px;
        height: 44px;
        border: 1.5px solid #d4af37;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .success-card h3 {
        font-family: "Cormorant Garamond", serif;
        color: white;
        font-size: 1.5rem;
        font-weight: 300;
        margin-bottom: 1.25rem;
    }

    /* Loading */
    .spinner {
        width: 32px;
        height: 32px;
        border: 3px solid rgba(212, 175, 55, 0.2);
        border-top-color: #d4af37;
        border-radius: 50%;
        margin: 0 auto 1rem;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .loading-card p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 13px;
    }
</style>
