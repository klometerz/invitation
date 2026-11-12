<script>
    // P2-T2: bank accounts and QRIS come from the invitation content
    // (`content.thanks`), never from hardcoded values. When absent, the gift
    // block is hidden entirely.
    let {
        guestData = null,
        names = null,
        thanks = {},
        coupleNames = "",
    } = $props();

    let copiedIndex = $state(null);

    const bankAccounts = $derived(
        Array.isArray(thanks?.bank_accounts) ? thanks.bank_accounts : [],
    );
    const qrisImage = $derived(thanks?.qris_image_url || null);
    const hasGift = $derived(bankAccounts.length > 0 || Boolean(qrisImage));

    const signatureNames = $derived(
        coupleNames ||
            [names?.wanita?.nama_panggilan, names?.pria?.nama_panggilan]
                .filter(Boolean)
                .join(" & "),
    );

    function copyToClipboard(text, index) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard
                .writeText(text)
                .then(() => triggerSuccess(index))
                .catch((err) => {
                    console.error("Gagal copy: ", err);
                    fallbackCopy(text, index);
                });
        } else {
            fallbackCopy(text, index);
        }
    }

    function fallbackCopy(text, index) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        textArea.style.top = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand("copy");
            if (successful) triggerSuccess(index);
        } catch (err) {
            console.error("Fallback gagal: ", err);
            alert("Gagal menyalin, silakan salin manual Bre.");
        }

        document.body.removeChild(textArea);
    }

    function triggerSuccess(index) {
        copiedIndex = index;
        setTimeout(() => (copiedIndex = null), 2000);
    }
</script>

<div class="thanks-wrapper">
    <!-- Closing Message -->
    <div class="thanks-header">
        <p class="subtitle"></p>
        <h2 class="title">Terima Kasih</h2>
        <div class="ornament"><div class="diamond"></div></div>

        <p class="closing-text">
            Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila
            Bapak/Ibu/Saudara/i berkenan hadir untuk memberikan doa restu
            kepada kami.
        </p>

        <p class="final-wish">Sampai jumpa di hari bahagia kami!</p>
        {#if signatureNames}
            <p class="signature">{signatureNames}</p>
        {/if}
    </div>

    {#if hasGift}
        <!-- Gift Section -->
        <div class="glass-card">
            <p class="gift-label">WEDDING GIFT</p>
            <p class="gift-desc">
                Bagi keluarga dan kerabat yang ingin memberikan tanda kasih,
                dapat melalui saluran di bawah ini:
            </p>

            <div class="gift-grid">
                {#each bankAccounts as account, i}
                    <div class="gift-item">
                        <p class="bank-label">{account.bank}</p>
                        <p class="account-number">{account.number}</p>
                        {#if account.holder}
                            <p class="account-name">A/N {account.holder}</p>
                        {/if}
                        <button
                            type="button"
                            class="copy-btn"
                            class:copied={copiedIndex === i}
                            onclick={(e) => {
                                e.preventDefault();
                                copyToClipboard(account.number, i);
                            }}
                        >
                            {copiedIndex === i
                                ? "BERHASIL DISALIN ✓"
                                : "SALIN NOMOR REKENING"}
                        </button>
                    </div>

                    {#if i < bankAccounts.length - 1}
                        <div class="divider-gold"></div>
                    {/if}
                {/each}

                {#if qrisImage}
                    {#if bankAccounts.length > 0}
                        <div class="divider-gold"></div>
                    {/if}

                    <div class="gift-item">
                        <p class="bank-label">QRIS</p>
                        <div class="qr-frame">
                            <img src={qrisImage} alt="QRIS" />
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    {/if}
</div>

<style>
    .thanks-wrapper {
        width: 100%;
        max-width: 420px;
        margin: 0 auto;
    }

    .thanks-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .subtitle {
        font-size: 10px;
        letter-spacing: 0.5em;
        color: #d4af37;
        margin-bottom: 1rem;
        font-family: "Inter", sans-serif;
        text-transform: uppercase;
    }

    .title {
        font-family: "Cormorant Garamond", serif;
        font-size: 2.6rem;
        font-weight: 300;
        color: white;
        margin: 0;
    }

    .ornament {
        display: flex;
        justify-content: center;
        margin: 1.25rem 0;
    }

    .diamond {
        width: 6px;
        height: 6px;
        background: #d4af37;
        transform: rotate(45deg);
    }

    .closing-text {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.8;
        margin: 1.5rem 0;
        font-family: "Inter", sans-serif;
    }

    .final-wish {
        font-family: "Cormorant Garamond", serif;
        font-style: italic;
        font-size: 1.1rem;
        margin-top: 2rem;
        color: rgba(255, 255, 255, 0.7);
    }

    .signature {
        font-family: "Cormorant Garamond", serif;
        font-size: 2rem;
        color: #d4af37;
        margin-top: 0.5rem;
    }

    /* GIFT CARD */
    .glass-card {
        padding: 2rem;
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 20px;
        text-align: center;
    }

    .gift-label {
        font-size: 11px;
        color: #d4af37;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-bottom: 0.75rem;
        font-family: "Inter", sans-serif;
    }

    .gift-desc {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 2rem;
        font-family: "Inter", sans-serif;
        line-height: 1.6;
    }

    .gift-grid {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .bank-label {
        font-size: 10px;
        opacity: 0.6;
        letter-spacing: 0.2em;
        margin-bottom: 0.4rem;
        font-family: "Inter", sans-serif;
        color: rgba(255, 255, 255, 0.7);
    }

    .account-number {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.6rem;
        color: white;
        margin: 0;
        letter-spacing: 0.03em;
    }

    .account-name {
        font-size: 11px;
        color: #d4af37;
        letter-spacing: 0.1em;
        margin-top: 0.3rem;
    }

    .copy-btn {
        margin-top: 1rem;
        background: transparent;
        border: 1px solid #d4af37;
        color: #d4af37;
        padding: 8px 18px;
        font-size: 9px;
        letter-spacing: 0.1em;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s;
        font-family: "Inter", sans-serif;
    }

    .copy-btn:hover {
        background: rgba(212, 175, 55, 0.1);
    }

    .copy-btn.copied {
        background: #d4af37;
        color: black;
    }

    .qr-frame {
        background: white;
        padding: 8px;
        width: 130px;
        margin: 0.8rem auto;
        border-radius: 10px;
    }

    .qr-frame img {
        width: 100%;
        display: block;
    }

    .divider-gold {
        width: 40px;
        height: 1px;
        background: #d4af37;
        margin: 0.25rem auto;
        opacity: 0.25;
    }

    @media (max-width: 480px) {
        .title { font-size: 2.2rem; }
        .glass-card { padding: 1.5rem; }
        .account-number { font-size: 1.4rem; }
    }
</style>