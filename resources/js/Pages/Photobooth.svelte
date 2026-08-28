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
    let frameWrapperEl = $state(null);
    let frameWrapperWidth = $state(400);
    let stream = null;

    let currentFacingMode = $state("user");
    let stage = $state("intro");
    let countdownNumber = $state(3);
    let capturedImage = $state(null);
    let rawPhotoDataUrl = $state(null);
    let selectedFrame = $state("square");
    let message = $state("");
    let errorMsg = $state("");

    // ===== STICKER STATE =====
    const stickerOptions = [
        "💍",
        "💕",
        "😍",
        "🎉",
        "👰",
        "🤵",
        "💐",
        "✨",
        "❤️",
        "🥂",
        "🕊️",
        "🌹",
    ];
    let placedStickers = $state([]); // { id, emoji, xPercent, yPercent, sizePercent }
    let activeStickerId = $state(null);
    let draggingId = null;
    let dragOffsetX = 0;
    let dragOffsetY = 0;

    function addSticker(emoji) {
        const id = crypto.randomUUID
            ? crypto.randomUUID()
            : Date.now().toString();
        placedStickers = [
            ...placedStickers,
            { id, emoji, xPercent: 50, yPercent: 50, sizePercent: 14 },
        ];
        activeStickerId = id;
    }

    function removeSticker(id) {
        placedStickers = placedStickers.filter((s) => s.id !== id);
        if (activeStickerId === id) activeStickerId = null;
    }

    function resizeSticker(id, delta) {
        placedStickers = placedStickers.map((s) =>
            s.id === id
                ? {
                      ...s,
                      sizePercent: Math.max(
                          6,
                          Math.min(40, s.sizePercent + delta),
                      ),
                  }
                : s,
        );
    }

    function selectSticker(id) {
        activeStickerId = id;
    }

    function handlePointerDown(e, sticker) {
        e.stopPropagation();
        selectSticker(sticker.id);
        draggingId = sticker.id;

        const rect = frameWrapperEl.getBoundingClientRect();
        const stickerX = (sticker.xPercent / 100) * rect.width;
        const stickerY = (sticker.yPercent / 100) * rect.height;

        dragOffsetX = e.clientX - rect.left - stickerX;
        dragOffsetY = e.clientY - rect.top - stickerY;

        e.currentTarget.setPointerCapture(e.pointerId);
    }

    function handlePointerMove(e) {
        if (!draggingId || !frameWrapperEl) return;

        const rect = frameWrapperEl.getBoundingClientRect();
        let x = e.clientX - rect.left - dragOffsetX;
        let y = e.clientY - rect.top - dragOffsetY;

        let xPercent = (x / rect.width) * 100;
        let yPercent = (y / rect.height) * 100;

        xPercent = Math.max(2, Math.min(98, xPercent));
        yPercent = Math.max(2, Math.min(98, yPercent));

        placedStickers = placedStickers.map((s) =>
            s.id === draggingId ? { ...s, xPercent, yPercent } : s,
        );
    }

    function handlePointerUp() {
        draggingId = null;
    }

    function clearStickerSelection() {
        activeStickerId = null;
    }

    // ===== CAMERA =====
    async function startCamera() {
        stage = "camera";
        errorMsg = "";
        placedStickers = [];

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: currentFacingMode,
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

    async function switchCamera() {
        currentFacingMode =
            currentFacingMode === "user" ? "environment" : "user";
        stopCamera();
        await startCamera();
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
        const size = 1080;

        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext("2d");

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
        if (currentFacingMode === "user") {
            ctx.scale(-1, 1);
            ctx.drawImage(video, sx, sy, sWidth, sHeight, -size, 0, size, size);
        } else {
            ctx.drawImage(video, sx, sy, sWidth, sHeight, 0, 0, size, size);
        }
        ctx.restore();

        rawPhotoDataUrl = canvas.toDataURL("image/jpeg", 0.92);
        stopCamera();
        stage = "preview";
        applyFrame(selectedFrame);
    }

    function drawFrame(ctx, size) {
        const borderWidth = size * 0.05;

        ctx.strokeStyle = "#d4af37";
        ctx.lineWidth = borderWidth * 0.3;
        ctx.strokeRect(
            borderWidth * 0.5,
            borderWidth * 0.5,
            size - borderWidth,
            size - borderWidth,
        );

        const pad = borderWidth * 1.2;
        drawDiamond(ctx, pad, pad, 12);
        drawDiamond(ctx, size - pad, pad, 12);
        drawDiamond(ctx, pad, size - pad, 12);
        drawDiamond(ctx, size - pad, size - pad, 12);

        ctx.fillStyle = "#d4af37";
        ctx.font = `${size * 0.04}px 'Cormorant Garamond', serif`;
        ctx.textAlign = "center";
        ctx.fillText("Maulia & Kahfi", size / 2, size - borderWidth * 3.2);

        ctx.font = `${size * 0.022}px Inter, sans-serif`;
        ctx.fillStyle = "rgba(212, 175, 55, 0.8)";
        ctx.fillText("06 . 09 . 2026", size / 2, size - borderWidth * 1.8);
    }

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }

    function drawImageCover(ctx, img, dx, dy, dWidth, dHeight) {
        const imgRatio = img.width / img.height;
        const targetRatio = dWidth / dHeight;
        let sx, sy, sWidth, sHeight;

        if (imgRatio > targetRatio) {
            sHeight = img.height;
            sWidth = sHeight * targetRatio;
            sx = (img.width - sWidth) / 2;
            sy = 0;
        } else {
            sWidth = img.width;
            sHeight = sWidth / targetRatio;
            sx = 0;
            sy = (img.height - sHeight) / 2;
        }

        ctx.drawImage(img, sx, sy, sWidth, sHeight, dx, dy, dWidth, dHeight);
    }

    function drawDiamond(ctx, x, y, r) {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(Math.PI / 4);
        ctx.fillStyle = "#d4af37";
        ctx.fillRect(-r / 2, -r / 2, r, r);
        ctx.restore();
    }

    async function applyFrame(frameType) {
        if (!rawPhotoDataUrl) return;
        selectedFrame = frameType;

        const img = await loadImage(rawPhotoDataUrl);
        const canvas = canvasEl;
        const ctx = canvas.getContext("2d");

        if (frameType === "4r") {
            render4RFrame(ctx, canvas, img);
        } else if (frameType === "polaroid") {
            renderPolaroidFrame(ctx, canvas, img);
        } else {
            renderSquareFrame(ctx, canvas, img);
        }

        capturedImage = canvas.toDataURL("image/jpeg", 0.92);
    }

    function renderSquareFrame(ctx, canvas, img) {
        const size = 1000;
        canvas.width = size;
        canvas.height = size;
        drawImageCover(ctx, img, 0, 0, size, size);
        drawFrame(ctx, size);
    }

    function render4RFrame(ctx, canvas, img) {
        const width = 1000;
        const height = 1500;
        canvas.width = width;
        canvas.height = height;

        const margin = width * 0.06;
        const captionSpace = height * 0.09;
        const photoW = width - margin * 2;
        const photoH = height - margin * 2 - captionSpace;

        ctx.fillStyle = "#0a0a0a";
        ctx.fillRect(0, 0, width, height);

        drawImageCover(ctx, img, margin, margin, photoW, photoH);

        ctx.strokeStyle = "#d4af37";
        ctx.lineWidth = margin * 0.12;
        ctx.strokeRect(
            margin * 0.5,
            margin * 0.5,
            width - margin,
            height - margin,
        );

        const pad = margin * 1.1;
        drawDiamond(ctx, pad, pad, 11);
        drawDiamond(ctx, width - pad, pad, 11);

        const captionBaseY = margin + photoH; // posisi tepat di bawah foto

        ctx.fillStyle = "#d4af37";
        ctx.textAlign = "center";
        ctx.font = `${width * 0.045}px 'Cormorant Garamond', serif`;
        ctx.fillText(
            "Maulia & Kahfi",
            width / 2,
            captionBaseY + captionSpace * 0.4,
        );

        ctx.font = `${width * 0.022}px Inter, sans-serif`;
        ctx.fillStyle = "rgba(212, 175, 55, 0.8)";
        ctx.fillText(
            "06 . 09 . 2026",
            width / 2,
            captionBaseY + captionSpace * 0.85,
        );
    }

    function renderPolaroidFrame(ctx, canvas, img) {
        const width = 900;
        const sideMargin = 40;
        const topMargin = 40;
        const photoSize = width - sideMargin * 2;
        const bottomMargin = 220;
        const height = topMargin + photoSize + bottomMargin;

        canvas.width = width;
        canvas.height = height;

        ctx.fillStyle = "#f5f0e6";
        ctx.fillRect(0, 0, width, height);

        drawImageCover(ctx, img, sideMargin, topMargin, photoSize, photoSize);

        ctx.strokeStyle = "rgba(0,0,0,0.15)";
        ctx.lineWidth = 2;
        ctx.strokeRect(sideMargin, topMargin, photoSize, photoSize);

        ctx.fillStyle = "#2a2a2a";
        ctx.textAlign = "center";
        ctx.font = `italic ${width * 0.06}px 'Cormorant Garamond', serif`;
        ctx.fillText("Maulia & Kahfi", width / 2, topMargin + photoSize + 90);

        ctx.font = `${width * 0.03}px Inter, sans-serif`;
        ctx.fillStyle = "#8a7a5c";
        ctx.fillText("06 . 09 . 2026", width / 2, topMargin + photoSize + 130);
    }

    function retake() {
        capturedImage = null;
        rawPhotoDataUrl = null;
        message = "";
        placedStickers = [];
        startCamera();
    }

    // Bake stiker (HTML overlay) jadi permanen ke pixel canvas
    function bakeStickersIntoCanvas() {
        if (placedStickers.length === 0) return;

        const canvas = canvasEl;
        const ctx = canvas.getContext("2d");

        placedStickers.forEach((sticker) => {
            const x = (sticker.xPercent / 100) * canvas.width;
            const y = (sticker.yPercent / 100) * canvas.height;
            const fontSize = (sticker.sizePercent / 100) * canvas.width;

            ctx.font = `${fontSize}px sans-serif`;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(sticker.emoji, x, y);
        });

        capturedImage = canvas.toDataURL("image/jpeg", 0.92);
    }

    async function savePhoto() {
        bakeStickersIntoCanvas();
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
                    "X-CSRF-TOKEN": csrfToken,
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
            const res = await fetch(capturedImage);
            const blob = await res.blob();
            const file = new File(
                [blob],
                `photobooth-${guestData.name.replace(/\s+/g, "-")}.jpg`,
                { type: blob.type },
            );

            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({
                    files: [file],
                    title: "Photobooth - Maulia & Kahfi",
                    text: "Momen kami di pernikahan Maulia & Kahfi 💕",
                });
            } else if (navigator.share) {
                await navigator.share({
                    title: "Photobooth - Maulia & Kahfi",
                    text: "Momen kami di pernikahan Maulia & Kahfi 💕",
                });
            } else {
                alert(
                    "Fitur share langsung tidak tersedia di browser ini. Silakan download foto dan bagikan secara manual.",
                );
            }
        } catch (err) {
            if (err.name !== "AbortError") {
                console.error("Share failed:", err);
            }
        }
    }

    onDestroy(() => {
        stopCamera();
    });
</script>

<svelte:window
    on:pointermove={handlePointerMove}
    on:pointerup={handlePointerUp}
/>

<div class="photobooth-page">
    {#if !accessGranted}
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
                            class:mirrored={currentFacingMode === "user"}
                        ></video>

                        {#if stage === "camera"}
                            <button
                                class="btn-switch-camera"
                                onclick={switchCamera}
                                title="Ganti kamera"
                            >
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M23 4v6h-6" /><path
                                        d="M1 20v-6h6"
                                    />
                                    <path
                                        d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"
                                    />
                                </svg>
                            </button>
                        {/if}

                        {#if stage === "countdown"}
                            <div class="countdown-overlay">
                                {#key countdownNumber}
                                    <span
                                        class="countdown-number"
                                        transition:scale={{ duration: 400 }}
                                        >{countdownNumber}</span
                                    >
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
                    <!-- FRAME + STICKER OVERLAY LAYER -->
                    <div
                        class="frame-wrapper"
                        bind:this={frameWrapperEl}
                        bind:clientWidth={frameWrapperWidth}
                        onclick={clearStickerSelection}
                    >
                        <img
                            src={capturedImage}
                            alt="Hasil foto"
                            class="preview-image"
                        />

                        {#each placedStickers as sticker (sticker.id)}
                            <div
                                class="sticker-item"
                                class:active={activeStickerId === sticker.id}
                                style="
                                    left: {sticker.xPercent}%;
                                    top: {sticker.yPercent}%;
                                    font-size: {(sticker.sizePercent / 100) *
                                    frameWrapperWidth}px;
                                "
                                onpointerdown={(e) =>
                                    handlePointerDown(e, sticker)}
                            >
                                <span class="sticker-emoji"
                                    >{sticker.emoji}</span
                                >

                                {#if activeStickerId === sticker.id}
                                    <div
                                        class="sticker-controls"
                                        onpointerdown={(e) =>
                                            e.stopPropagation()}
                                    >
                                        <button
                                            class="sticker-btn"
                                            onclick={() =>
                                                resizeSticker(sticker.id, -2)}
                                            >−</button
                                        >
                                        <button
                                            class="sticker-btn sticker-btn-remove"
                                            onclick={() =>
                                                removeSticker(sticker.id)}
                                            >×</button
                                        >
                                        <button
                                            class="sticker-btn"
                                            onclick={() =>
                                                resizeSticker(sticker.id, 2)}
                                            >+</button
                                        >
                                    </div>
                                {/if}
                            </div>
                        {/each}
                    </div>

                    <!-- STICKER PALETTE -->
                    <div class="sticker-palette">
                        {#each stickerOptions as emoji}
                            <button
                                class="sticker-palette-btn"
                                onclick={() => addSticker(emoji)}
                            >
                                {emoji}
                            </button>
                        {/each}
                    </div>

                    <div class="frame-picker">
                        <button
                            class="frame-option"
                            class:active={selectedFrame === "square"}
                            onclick={() => applyFrame("square")}>Klasik</button
                        >
                        <button
                            class="frame-option"
                            class:active={selectedFrame === "4r"}
                            onclick={() => applyFrame("4r")}>4R</button
                        >
                        <button
                            class="frame-option"
                            class:active={selectedFrame === "polaroid"}
                            onclick={() => applyFrame("polaroid")}
                            >Polaroid</button
                        >
                    </div>

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
                                <path
                                    d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                />
                                <polyline points="7 10 12 15 17 10" /><line
                                    x1="12"
                                    y1="15"
                                    x2="12"
                                    y2="3"
                                />
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
                                <circle cx="18" cy="5" r="3" /><circle
                                    cx="6"
                                    cy="12"
                                    r="3"
                                /><circle cx="18" cy="19" r="3" />
                                <line
                                    x1="8.59"
                                    y1="13.51"
                                    x2="15.42"
                                    y2="17.49"
                                /><line
                                    x1="15.41"
                                    y1="6.51"
                                    x2="8.59"
                                    y2="10.49"
                                />
                            </svg>
                            <span>Share</span>
                        </button>
                    </div>
                    <button
                        class="btn-primary btn-full"
                        onclick={() => {
                            capturedImage = null;
                            message = "";
                            placedStickers = [];
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
    }

    .camera-video.mirrored {
        transform: scaleX(-1);
    }

    .btn-switch-camera {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 5;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(212, 175, 55, 0.4);
        color: #d4af37;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
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
        display: block;
        pointer-events: none;
        user-select: none;
    }

    /* ===== FRAME + STICKER OVERLAY ===== */
    .frame-wrapper {
        position: relative;
        width: 100%;
        margin-bottom: 1rem;
        touch-action: none;
    }

    .sticker-item {
        position: absolute;
        transform: translate(-50%, -50%);
        cursor: grab;
        line-height: 1;
        touch-action: none;
        user-select: none;
    }

    .sticker-item.active .sticker-emoji {
        filter: drop-shadow(0 0 0 transparent);
        outline: 2px dashed rgba(212, 175, 55, 0.8);
        outline-offset: 4px;
        border-radius: 8px;
    }

    .sticker-controls {
        position: absolute;
        top: -34px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 4px;
        background: rgba(0, 0, 0, 0.75);
        padding: 3px;
        border-radius: 8px;
    }

    .sticker-btn {
        width: 22px;
        height: 22px;
        border-radius: 5px;
        border: none;
        background: rgba(212, 175, 55, 0.2);
        color: #d4af37;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .sticker-btn-remove {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }

    /* ===== STICKER PALETTE ===== */
    .sticker-palette {
        display: flex;
        gap: 0.4rem;
        overflow-x: auto;
        padding: 0.25rem 0 0.75rem;
        margin-bottom: 0.25rem;
    }

    .sticker-palette::-webkit-scrollbar {
        display: none;
    }

    .sticker-palette-btn {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid rgba(212, 175, 55, 0.25);
        background: rgba(255, 255, 255, 0.03);
        font-size: 19px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sticker-palette-btn:hover {
        border-color: rgba(212, 175, 55, 0.5);
        background: rgba(212, 175, 55, 0.08);
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

    .frame-picker {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }

    .frame-option {
        flex: 1;
        padding: 0.6rem 0.5rem;
        background: transparent;
        border: 1px solid rgba(212, 175, 55, 0.3);
        color: rgba(255, 255, 255, 0.6);
        border-radius: 10px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s;
    }

    .frame-option:hover {
        border-color: rgba(212, 175, 55, 0.6);
        color: white;
    }

    .frame-option.active {
        background: #d4af37;
        color: black;
        border-color: #d4af37;
        font-weight: 600;
    }
</style>
