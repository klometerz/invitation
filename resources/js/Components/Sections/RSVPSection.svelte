<script>
    let { guestData = null } = $props();

    let attendance = $state(guestData?.is_rsvp ? guestData?.attendance : null);
    let message = $state("");
    let isSubmitting = $state(false);
    let isSubmitted = $state(!!guestData?.is_rsvp);

    async function handleAttendance(status) {
        if (isSubmitting || isSubmitted || !guestData?.guest_code) return;

        attendance = status;
        isSubmitting = true;

        try {
            const baseUrl = import.meta.env.VITE_APP_URL || "";
            const url = `${baseUrl}/api/rsvp/${guestData.guest_code}`;

            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    status: status,
                    message: message.trim(),
                }),
            });

            const result = await response.json();

            if (response.ok) {
                isSubmitting = false;
                isSubmitted = true;
            } else if (response.status === 409) {
                isSubmitting = false;
                isSubmitted = true;
                attendance = result.attendance || status;
            } else {
                console.error("Server error:", result);
                alert("Gagal mengirim: " + (result.message || "Coba lagi."));
                isSubmitting = false;
                attendance = null;
            }
        } catch (error) {
            console.error("Network error:", error);
            alert("Gagal terhubung ke server. Cek koneksi Anda.");
            isSubmitting = false;
            attendance = null;
        }
    }
</script>

<div class="rsvp-wrapper">
    {#if !isSubmitted}
        <div class="glass-card">
            <p class="subtitle">RESERVATION</p>
            <h2 class="title">RSVP</h2>
            <div class="ornament"><div class="diamond"></div></div>

            {#if guestData}
                <p class="welcome">Halo, <span>{guestData.name}</span></p>
            {/if}

            <p class="desc">
                Konfirmasi kehadiran Anda sangat berarti bagi kami.
            </p>

            <div class="message-field">
                <textarea
                    bind:value={message}
                    placeholder="Tuliskan ucapan atau doa untuk kedua mempelai... (opsional)"
                    rows="3"
                    disabled={isSubmitting}
                ></textarea>
            </div>

            <div class="btn-column">
                <button
                    class="rsvp-btn confirmed"
                    disabled={isSubmitting}
                    onclick={() => handleAttendance("confirmed")}
                >
                    {#if isSubmitting && attendance === "confirmed"}
                        MENGIRIM...
                    {:else}
                        SAYA AKAN HADIR
                    {/if}
                </button>

                <button
                    class="rsvp-btn declined"
                    disabled={isSubmitting}
                    onclick={() => handleAttendance("declined")}
                >
                    {#if isSubmitting && attendance === "declined"}
                        MENGIRIM...
                    {:else}
                        MOHON MAAF, TIDAK HADIR
                    {/if}
                </button>
            </div>
        </div>
    {:else}
        <div class="glass-card success-card">
            <div class="success-icon">
                <svg
                    width="28"
                    height="28"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                >
                    <path
                        d="M5 13l4 4L19 7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>
            <h3 class="title-small">Terima Kasih!</h3>
            <p class="desc">
                {#if attendance === "confirmed"}
                    Konfirmasi kehadiran Anda telah kami terima. Kami sangat
                    menantikan kehadiran Anda!
                {:else}
                    Terima kasih telah mengonfirmasi. Kami akan merindukan
                    kehadiran Anda.
                {/if}
            </p>
            {#if message}
                <div class="submitted-message">
                    <p class="submitted-message-label">Ucapan Anda:</p>
                    <p class="submitted-message-text">"{message}"</p>
                </div>
            {/if}

            {#if guestData?.qrcode}
                <div class="qr-backup">
                    <div class="qr-divider"></div>
                    <p class="qr-label">QR Code</p>
                    <p class="qr-hint">
                        Tunjukan QR Code ini pada saat check-in untuk
                        mendapatkan souvenir
                    </p>
                    <div class="qr-frame">
                        {@html guestData.qrcode}
                    </div>
                    <p class="qr-name">{guestData.name}</p>
                </div>
            {/if}
        </div>
    {/if}
</div>

<style>
    .rsvp-wrapper {
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
    }

    .glass-card {
        padding: 2.25rem 2rem;
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 20px;
        text-align: center;
    }

    .subtitle {
        font-size: 10px;
        letter-spacing: 0.5em;
        color: #d4af37;
        text-transform: uppercase;
        margin-bottom: 0.75rem;
    }

    .title {
        font-family: "Cormorant Garamond", serif;
        font-size: 2.4rem;
        font-weight: 300;
        color: white;
        margin: 0;
    }

    .ornament {
        display: flex;
        justify-content: center;
        margin: 1rem 0;
    }

    .diamond {
        width: 6px;
        height: 6px;
        background: #d4af37;
        transform: rotate(45deg);
    }

    .welcome {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.3rem;
        color: white;
        margin: 0.5rem 0;
    }

    .welcome span {
        color: #d4af37;
    }

    .desc {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.55);
        margin: 1rem 0 0;
        line-height: 1.6;
    }

    .btn-column {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1.75rem;
    }

    .message-field {
        margin-top: 1.5rem;
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
        line-height: 1.5;
    }

    .message-field textarea::placeholder {
        color: rgba(255, 255, 255, 0.35);
    }

    .message-field textarea:focus {
        outline: none;
        border-color: rgba(212, 175, 55, 0.5);
    }

    .message-field textarea:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .submitted-message {
        margin-top: 1.5rem;
        padding: 1rem 1.25rem;
        background: rgba(212, 175, 55, 0.06);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 12px;
        text-align: left;
    }

    .submitted-message-label {
        font-size: 10px;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: rgba(212, 175, 55, 0.7);
        margin: 0 0 0.4rem;
    }

    .submitted-message-text {
        font-family: "Cormorant Garamond", serif;
        font-style: italic;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.75);
        line-height: 1.6;
        margin: 0;
    }

    .qr-backup {
        margin-top: 1.75rem;
        text-align: center;
    }

    .qr-divider {
        width: 50px;
        height: 1px;
        background: rgba(212, 175, 55, 0.3);
        margin: 0 auto 1.25rem;
    }

    .qr-label {
        font-size: 10px;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #d4af37;
        margin: 0 0 0.5rem;
    }

    .qr-hint {
        font-size: 11.5px;
        color: rgba(255, 255, 255, 0.5);
        line-height: 1.6;
        margin: 0 0 1.25rem;
        max-width: 280px;
        margin-left: auto;
        margin-right: auto;
    }

    .qr-frame {
        width: fit-content;
        margin: 0 auto;
        padding: 12px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    .qr-frame :global(svg) {
        display: block;
        width: 160px;
        height: 160px;
    }

    .qr-name {
        font-family: "Cormorant Garamond", serif;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        margin-top: 0.75rem;
        letter-spacing: 0.05em;
    }

    .rsvp-btn {
        width: 100%;
        padding: 0.9rem;
        border-radius: 50px;
        font-size: 11px;
        letter-spacing: 0.1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s;
        border: 1px solid #d4af37;
        font-family: "Inter", sans-serif;
    }

    .confirmed {
        background: #d4af37;
        color: black;
    }

    .confirmed:hover:not(:disabled) {
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
    }

    .declined {
        background: transparent;
        color: #d4af37;
    }

    .declined:hover:not(:disabled) {
        background: rgba(212, 175, 55, 0.1);
    }

    .rsvp-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .success-icon {
        width: 56px;
        height: 56px;
        border: 1.5px solid #d4af37;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #d4af37;
        margin: 0 auto 1.25rem;
    }

    .title-small {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.9rem;
        color: white;
        font-weight: 300;
        margin-bottom: 0.75rem;
    }

    @media (max-width: 480px) {
        .glass-card {
            padding: 1.75rem 1.5rem;
        }
        .title {
            font-size: 2rem;
        }
    }
</style>
