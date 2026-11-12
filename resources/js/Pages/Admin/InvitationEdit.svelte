<script>
    import { router } from "@inertiajs/svelte";
    import AdminLayout from "../../Components/Admin/AdminLayout.svelte";
    import Toast from "../../Components/Admin/Toast.svelte";
    import { toast } from "../../Components/Admin/toast.js";

    let { invitation } = $props();
    const content = invitation.content || {};

    let hero = $state({
        title: content.hero?.title || "THE WEDDING CELEBRATION",
        date_formatted: content.hero?.date_formatted || "",
    });

    let heroBackgroundFile = $state(null);
    let heroMusicFile = $state(null);
    let currentBackgroundUrl = $state(content.hero?.background_url || null);
    let currentMusicUrl = $state(content.hero?.music_url || null);

    let mempelai = $state({
        pria: {
            nama_lengkap: content.mempelai?.pria?.nama_lengkap || "",
            nama_panggilan: content.mempelai?.pria?.nama_panggilan || "",
            ayah: content.mempelai?.pria?.ayah || "",
            ibu: content.mempelai?.pria?.ibu || "",
        },
        wanita: {
            nama_lengkap: content.mempelai?.wanita?.nama_lengkap || "",
            nama_panggilan: content.mempelai?.wanita?.nama_panggilan || "",
            ayah: content.mempelai?.wanita?.ayah || "",
            ibu: content.mempelai?.wanita?.ibu || "",
        },
    });

    let priaFotoFile = $state(null);
    let wanitaFotoFile = $state(null);
    let currentPriaFoto = $state(content.mempelai?.pria?.foto_url || null);
    let currentWanitaFoto = $state(content.mempelai?.wanita?.foto_url || null);

    let acaraList = $state(
        content.acara?.length > 0
            ? [...content.acara]
            : [
                  {
                      tipe: "akad",
                      tanggal: "",
                      jam: "",
                      lokasi: "",
                      alamat: "",
                      map_url: "",
                  },
              ],
    );

    function addAcara() {
        acaraList = [
            ...acaraList,
            {
                tipe: "resepsi",
                tanggal: "",
                jam: "",
                lokasi: "",
                alamat: "",
                map_url: "",
            },
        ];
    }

    function removeAcara(index) {
        acaraList = acaraList.filter((_, i) => i !== index);
    }

    // let storiesList = $state(
    //     content.stories?.length > 0
    //         ? [...content.stories]
    //         : [{ tahun: "", judul: "", deskripsi: "" }],
    // );

    let gratitudeMessage = $state(content.gratitude_message || "");

    // Verses - repeatable
    let versesList = $state(
        content.verses?.length > 0
            ? [...content.verses]
            : [{ arabic: "", translation: "", reference: "" }],
    );

    function addVerse() {
        versesList = [
            ...versesList,
            { arabic: "", translation: "", reference: "" },
        ];
    }

    function removeVerse(index) {
        versesList = versesList.filter((_, i) => i !== index);
    }
    // Moodboard Colors - repeatable
    let colorsList = $state(
        content.moodboard?.colors?.length > 0
            ? [...content.moodboard.colors]
            : [{ hex: "#D4AF37", name: "Royal Gold" }],
    );

    function addColor() {
        colorsList = [...colorsList, { hex: "#000000", name: "" }];
    }

    function removeColor(index) {
        colorsList = colorsList.filter((_, i) => i !== index);
    }

    // Moodboard Announcements - repeatable
    let announcementsList = $state(
        content.moodboard?.announcements?.length > 0
            ? [...content.moodboard.announcements]
            : [{ icon: "invitation", title: "", description: "" }],
    );

    function addAnnouncement() {
        announcementsList = [
            ...announcementsList,
            { icon: "invitation", title: "", description: "" },
        ];
    }

    function removeAnnouncement(index) {
        announcementsList = announcementsList.filter((_, i) => i !== index);
    }

    // ===== P2-T2 / P2-T8: branding, tips, invite template, gift =====
    let hashtag = $state(content.hero?.hashtag || "");
    let logoFile = $state(null);
    let currentLogo = $state(content.logo_url || null);
    let loadingTips = $state((content.loading_tips || []).join("\n"));
    let inviteTemplate = $state(content.invite_message_template || "");
    let giftAccounts = $state(
        content.thanks?.bank_accounts?.length
            ? [...content.thanks.bank_accounts]
            : [],
    );
    let qrisFile = $state(null);
    let currentQris = $state(content.thanks?.qris_image_url || null);

    function addGiftAccount() {
        giftAccounts = [...giftAccounts, { bank: "", number: "", holder: "" }];
    }

    function removeGiftAccount(index) {
        giftAccounts = giftAccounts.filter((_, i) => i !== index);
    }

    function previewMessage() {
        const couple = [
            mempelai.wanita.nama_panggilan,
            mempelai.pria.nama_panggilan,
        ]
            .filter(Boolean)
            .join(" & ");

        return (inviteTemplate || "")
            .replaceAll("{name}", "Budi Santoso")
            .replaceAll("{link}", "https://contoh.id/nama-pasangan?guest=budi-abcde")
            .replaceAll("{quota}", "2")
            .replaceAll("{couple}", couple || "Nama & Pasangan");
    }

    // function addStory() {
    //     storiesList = [...storiesList, { tahun: "", judul: "", deskripsi: "" }];
    // }

    // function removeStory(index) {
    //     storiesList = storiesList.filter((_, i) => i !== index);
    // }

    let isSaving = $state(false);

    function handleSubmit() {
        isSaving = true;
        const formData = new FormData();
        formData.append("hero[title]", hero.title);
        formData.append("hero[date_formatted]", hero.date_formatted);
        formData.append(
            "mempelai[pria][nama_lengkap]",
            mempelai.pria.nama_lengkap,
        );
        formData.append(
            "mempelai[pria][nama_panggilan]",
            mempelai.pria.nama_panggilan,
        );
        formData.append("mempelai[pria][ayah]", mempelai.pria.ayah);
        formData.append("mempelai[pria][ibu]", mempelai.pria.ibu);
        formData.append(
            "mempelai[wanita][nama_lengkap]",
            mempelai.wanita.nama_lengkap,
        );
        formData.append(
            "mempelai[wanita][nama_panggilan]",
            mempelai.wanita.nama_panggilan,
        );
        formData.append("mempelai[wanita][ayah]", mempelai.wanita.ayah);
        formData.append("mempelai[wanita][ibu]", mempelai.wanita.ibu);
        formData.append("acara", JSON.stringify(acaraList));
        formData.append("hero[hashtag]", hashtag);
        formData.append("loading_tips", loadingTips);
        formData.append("invite_message_template", inviteTemplate);
        formData.append("thanks_accounts", JSON.stringify(giftAccounts));
        // formData.append("stories", JSON.stringify(storiesList));
        formData.append("gratitude_message", gratitudeMessage);
        formData.append("verses", JSON.stringify(versesList));
        formData.append("moodboard_colors", JSON.stringify(colorsList));
        formData.append(
            "moodboard_announcements",
            JSON.stringify(announcementsList),
        );
        if (heroBackgroundFile)
            formData.append("hero_background", heroBackgroundFile);
        if (heroMusicFile) formData.append("hero_music", heroMusicFile);
        if (logoFile) formData.append("logo", logoFile);
        if (qrisFile) formData.append("qris_image", qrisFile);
        if (priaFotoFile) formData.append("mempelai_pria_foto", priaFotoFile);
        if (wanitaFotoFile)
            formData.append("mempelai_wanita_foto", wanitaFotoFile);

        router.post("/admin/invitation", formData, {
            onSuccess: () => {
                isSaving = false;
                toast("Perubahan berhasil disimpan!", "success");
            },
            onError: (errors) => {
                isSaving = false;
                console.error(errors);
                toast("Gagal menyimpan. Periksa konsol untuk detail.", "error");
            },
        });
    }
</script>

<AdminLayout title="Edit Invitation" breadcrumbs={["Invitation"]}>
    <Toast />

    <!-- STICKY SAVE BAR -->
    <div class="save-bar">
        <div class="save-bar-inner">
            <div>
                <h1 class="page-title">Edit Invitation Content</h1>
                <p class="page-sub">
                    Perubahan akan langsung tampil di halaman undangan.
                </p>
            </div>
            <button class="btn-save" onclick={handleSubmit} disabled={isSaving}>
                {#if isSaving}
                    <span class="spinner-sm"></span>
                    Menyimpan...
                {:else}
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
                        <path
                            d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"
                        /><polyline points="17 21 17 13 7 13 7 21" /><polyline
                            points="7 3 7 8 15 8"
                        />
                    </svg>
                    Simpan Semua
                {/if}
            </button>
        </div>
    </div>

    <!-- SECTION: HERO -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon">
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
                    <path
                        d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                    /><polyline points="9 22 9 12 15 12 15 22" />
                </svg>
            </div>
            <div>
                <h2 class="form-card-title">Hero / Welcome Screen</h2>
                <p class="form-card-sub">
                    Teks dan media yang muncul pertama saat halaman dibuka.
                </p>
            </div>
        </div>
        <div class="form-body">
            <div class="form-grid form-grid-2">
                <div class="field">
                    <label class="label">Subtitle Text</label>
                    <input
                        type="text"
                        class="input"
                        bind:value={hero.title}
                        placeholder="THE WEDDING CELEBRATION"
                    />
                </div>
                <div class="field">
                    <label class="label">Tanggal (format tampilan)</label>
                    <input
                        type="text"
                        class="input"
                        bind:value={hero.date_formatted}
                        placeholder="SABTU, 24 MEI 2026"
                    />
                </div>
            </div>

            <div class="form-grid form-grid-2">
                <div class="field">
                    <label class="label">Background Image</label>
                    {#if currentBackgroundUrl}
                        <img
                            src={currentBackgroundUrl}
                            alt="Current background"
                            class="media-preview"
                        />
                    {/if}
                    <div class="file-input-wrap">
                        <label class="file-label">
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                /><polyline points="17 8 12 3 7 8" /><line
                                    x1="12"
                                    y1="3"
                                    x2="12"
                                    y2="15"
                                />
                            </svg>
                            {heroBackgroundFile
                                ? heroBackgroundFile.name
                                : "Pilih gambar baru..."}
                            <input
                                type="file"
                                accept="image/*"
                                onchange={(e) =>
                                    (heroBackgroundFile = e.target.files[0])}
                                hidden
                            />
                        </label>
                    </div>
                </div>

                <div class="field">
                    <label class="label">Background Music (MP3)</label>
                    {#if currentMusicUrl}
                        <audio
                            controls
                            src={currentMusicUrl}
                            class="audio-preview"
                        ></audio>
                    {/if}
                    <div class="file-input-wrap">
                        <label class="file-label">
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M9 18V5l12-2v13" /><circle
                                    cx="6"
                                    cy="18"
                                    r="3"
                                /><circle cx="18" cy="16" r="3" />
                            </svg>
                            {heroMusicFile
                                ? heroMusicFile.name
                                : "Pilih audio baru..."}
                            <input
                                type="file"
                                accept="audio/*"
                                onchange={(e) =>
                                    (heroMusicFile = e.target.files[0])}
                                hidden
                            />
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: MEMPELAI -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon">
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
                    <path
                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                    />
                </svg>
            </div>
            <div>
                <h2 class="form-card-title">Data Mempelai</h2>
                <p class="form-card-sub">
                    Informasi nama dan foto kedua mempelai.
                </p>
            </div>
        </div>
        <div class="form-body">
            <div class="form-grid form-grid-2">
                <!-- Pria -->
                <div class="mempelai-block">
                    <h3 class="mempelai-heading">Mempelai Pria</h3>
                    {#if currentPriaFoto}
                        <img
                            src={currentPriaFoto}
                            alt="Foto pria"
                            class="avatar-preview"
                        />
                    {/if}
                    <div class="file-input-wrap" style="margin-bottom:12px">
                        <label class="file-label">
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="3"
                                    y="3"
                                    width="18"
                                    height="18"
                                    rx="2"
                                /><circle cx="8.5" cy="8.5" r="1.5" /><polyline
                                    points="21 15 16 10 5 21"
                                />
                            </svg>
                            {priaFotoFile
                                ? priaFotoFile.name
                                : "Ganti foto pria..."}
                            <input
                                type="file"
                                accept="image/*"
                                onchange={(e) =>
                                    (priaFotoFile = e.target.files[0])}
                                hidden
                            />
                        </label>
                    </div>
                    <div class="field">
                        <label class="label">Nama Lengkap</label><input
                            type="text"
                            class="input"
                            bind:value={mempelai.pria.nama_lengkap}
                            placeholder="Nama Lengkap"
                        />
                    </div>
                    <div class="field">
                        <label class="label">Nama Panggilan</label><input
                            type="text"
                            class="input"
                            bind:value={mempelai.pria.nama_panggilan}
                            placeholder="Nama Panggilan"
                        />
                    </div>
                    <div class="field">
                        <label class="label">Nama Ayah</label><input
                            type="text"
                            class="input"
                            bind:value={mempelai.pria.ayah}
                            placeholder="Nama Ayah"
                        />
                    </div>
                    <div class="field">
                        <label class="label">Nama Ibu</label><input
                            type="text"
                            class="input"
                            bind:value={mempelai.pria.ibu}
                            placeholder="Nama Ibu"
                        />
                    </div>
                </div>

                <!-- Wanita -->
                <div class="mempelai-block">
                    <h3 class="mempelai-heading">Mempelai Wanita</h3>
                    {#if currentWanitaFoto}
                        <img
                            src={currentWanitaFoto}
                            alt="Foto wanita"
                            class="avatar-preview"
                        />
                    {/if}
                    <div class="file-input-wrap" style="margin-bottom:12px">
                        <label class="file-label">
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="3"
                                    y="3"
                                    width="18"
                                    height="18"
                                    rx="2"
                                /><circle cx="8.5" cy="8.5" r="1.5" /><polyline
                                    points="21 15 16 10 5 21"
                                />
                            </svg>
                            {wanitaFotoFile
                                ? wanitaFotoFile.name
                                : "Ganti foto wanita..."}
                            <input
                                type="file"
                                accept="image/*"
                                onchange={(e) =>
                                    (wanitaFotoFile = e.target.files[0])}
                                hidden
                            />
                        </label>
                    </div>
                    <div class="field">
                        <label class="label">Nama Lengkap</label><input
                            type="text"
                            class="input"
                            bind:value={mempelai.wanita.nama_lengkap}
                            placeholder="Nama Lengkap"
                        />
                    </div>
                    <div class="field">
                        <label class="label">Nama Panggilan</label><input
                            type="text"
                            class="input"
                            bind:value={mempelai.wanita.nama_panggilan}
                            placeholder="Nama Panggilan"
                        />
                    </div>
                    <div class="field">
                        <label class="label">Nama Ayah</label><input
                            type="text"
                            class="input"
                            bind:value={mempelai.wanita.ayah}
                            placeholder="Nama Ayah"
                        />
                    </div>
                    <div class="field">
                        <label class="label">Nama Ibu</label><input
                            type="text"
                            class="input"
                            bind:value={mempelai.wanita.ibu}
                            placeholder="Nama Ibu"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION: ACARA -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon">
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
                    <rect
                        x="3"
                        y="4"
                        width="18"
                        height="18"
                        rx="2"
                        ry="2"
                    /><line x1="16" y1="2" x2="16" y2="6" /><line
                        x1="8"
                        y1="2"
                        x2="8"
                        y2="6"
                    /><line x1="3" y1="10" x2="21" y2="10" />
                </svg>
            </div>
            <div>
                <h2 class="form-card-title">Detail Acara</h2>
                <p class="form-card-sub">
                    Tambahkan sesi akad nikah dan resepsi.
                </p>
            </div>
            <button class="btn-add" onclick={addAcara}>
                <svg
                    width="13"
                    height="13"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    ><line x1="12" y1="5" x2="12" y2="19" /><line
                        x1="5"
                        y1="12"
                        x2="19"
                        y2="12"
                    /></svg
                >
                Tambah Acara
            </button>
        </div>
        <div class="form-body">
            {#each acaraList as acara, i}
                <div class="repeater-item">
                    <div class="repeater-item-head">
                        <span class="repeater-num">Acara #{i + 1}</span>
                        <button
                            class="btn-remove"
                            onclick={() => removeAcara(i)}
                        >
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                ><line x1="18" y1="6" x2="6" y2="18" /><line
                                    x1="6"
                                    y1="6"
                                    x2="18"
                                    y2="18"
                                /></svg
                            >
                            Hapus
                        </button>
                    </div>
                    <div class="form-grid form-grid-3">
                        <div class="field">
                            <label class="label">Tipe</label>
                            <select class="input" bind:value={acara.tipe}>
                                <option value="akad">Akad Nikah</option>
                                <option value="resepsi">Resepsi</option>
                            </select>
                        </div>
                        <div class="field">
                            <label class="label">Tanggal</label>
                            <input
                                type="date"
                                class="input"
                                bind:value={acara.tanggal}
                            />
                        </div>
                        <div class="field">
                            <label class="label">Jam</label>
                            <input
                                type="text"
                                class="input"
                                bind:value={acara.jam}
                                placeholder="08:00 – 10:00 WIB"
                            />
                        </div>
                        <div class="field">
                            <label class="label">Nama Tempat</label>
                            <input
                                type="text"
                                class="input"
                                bind:value={acara.lokasi}
                                placeholder="Nama gedung / tempat"
                            />
                        </div>
                        <div class="field field-span-2">
                            <label class="label">Alamat Lengkap</label>
                            <input
                                type="text"
                                class="input"
                                bind:value={acara.alamat}
                                placeholder="Jl. ..."
                            />
                        </div>
                        <div class="field field-span-3">
                            <label class="label">Link Google Maps</label>
                            <input
                                type="text"
                                class="input"
                                bind:value={acara.map_url}
                                placeholder="https://maps.google.com/..."
                            />
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <!-- SECTION: STORIES -->
    <!-- <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon">
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
                    <path
                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                    />
                </svg>
            </div>
            <div>
                <h2 class="form-card-title">Our Love Story</h2>
                <p class="form-card-sub">
                    Momen-momen perjalanan cinta yang ingin diceritakan.
                </p>
            </div>
            <button class="btn-add" onclick={addStory}>
                <svg
                    width="13"
                    height="13"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    ><line x1="12" y1="5" x2="12" y2="19" /><line
                        x1="5"
                        y1="12"
                        x2="19"
                        y2="12"
                    /></svg
                >
                Tambah Cerita
            </button>
        </div>
        <div class="form-body">
            {#each storiesList as story, i}
                <div class="repeater-item">
                    <div class="repeater-item-head">
                        <span class="repeater-num">Momen #{i + 1}</span>
                        <button
                            class="btn-remove"
                            onclick={() => removeStory(i)}
                        >
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                ><line x1="18" y1="6" x2="6" y2="18" /><line
                                    x1="6"
                                    y1="6"
                                    x2="18"
                                    y2="18"
                                /></svg
                            >
                            Hapus
                        </button>
                    </div>
                    <div class="form-grid form-grid-3">
                        <div class="field">
                            <label class="label">Tahun</label>
                            <input
                                type="text"
                                class="input"
                                bind:value={story.tahun}
                                placeholder="2020"
                            />
                        </div>
                        <div class="field field-span-2">
                            <label class="label">Judul</label>
                            <input
                                type="text"
                                class="input"
                                bind:value={story.judul}
                                placeholder="First Met"
                            />
                        </div>
                        <div class="field field-span-3">
                            <label class="label">Deskripsi</label>
                            <textarea
                                class="input textarea"
                                bind:value={story.deskripsi}
                                placeholder="Cerita singkat..."
                                rows="3"
                            ></textarea>
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    </div> -->

    <!-- SECTION: UCAPAN SYUKUR -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon">
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
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path
                        d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"
                    />
                </svg>
            </div>
            <div>
                <h2 class="form-card-title">Ucapan Syukur & Ayat</h2>
                <p class="form-card-sub">
                    Pesan ucapan syukur dan kutipan ayat suci Al-Qur'an.
                </p>
            </div>
            <button class="btn-add" onclick={addVerse}>
                <svg
                    width="13"
                    height="13"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    ><line x1="12" y1="5" x2="12" y2="19" /><line
                        x1="5"
                        y1="12"
                        x2="19"
                        y2="12"
                    /></svg
                >
                Tambah Ayat
            </button>
        </div>
        <div class="form-body">
            <div class="field" style="margin-bottom: 16px;">
                <label class="label">Kalimat Syukur</label>
                <textarea
                    class="input textarea"
                    bind:value={gratitudeMessage}
                    placeholder="Dengan mengucap syukur kepada Allah SWT, kami memohon doa restu..."
                    rows="3"
                ></textarea>
            </div>

            {#each versesList as verse, i}
                <div class="repeater-item">
                    <div class="repeater-item-head">
                        <span class="repeater-num">Ayat #{i + 1}</span>
                        <button
                            class="btn-remove"
                            onclick={() => removeVerse(i)}
                        >
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                ><line x1="18" y1="6" x2="6" y2="18" /><line
                                    x1="6"
                                    y1="6"
                                    x2="18"
                                    y2="18"
                                /></svg
                            >
                            Hapus
                        </button>
                    </div>
                    <div class="form-grid" style="gap: 12px;">
                        <div class="field">
                            <label class="label">Teks Arab</label>
                            <textarea
                                bind:value={verse.arabic}
                                placeholder="Teks Arab (contoh: وَمِنْ آيَاتِهِ...)"
                                rows="2"
                                class="input textarea arabic-textarea"
                                dir="rtl"
                            ></textarea>
                        </div>
                        <div class="field">
                            <label class="label">Terjemahan</label>
                            <textarea
                                bind:value={verse.translation}
                                placeholder="Terjemahan bahasa Indonesia"
                                rows="2"
                                class="input textarea"
                            ></textarea>
                        </div>
                        <div class="field">
                            <label class="label">Referensi (QS / Sumber)</label>
                            <input
                                type="text"
                                class="input"
                                bind:value={verse.reference}
                                placeholder="Referensi (contoh: QS. Ar-Rum : 21)"
                            />
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <!-- SECTION: MOODBOARD - COLOR PALETTE -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon">
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
                    <circle cx="12" cy="12" r="1" /><circle
                        cx="19"
                        cy="12"
                        r="1"
                    /><circle cx="5" cy="12" r="1" /><circle
                        cx="12"
                        cy="5"
                        r="1"
                    /><circle cx="12" cy="19" r="1" /><circle
                        cx="19"
                        cy="5"
                        r="1"
                    /><circle cx="5" cy="19" r="1" /><circle
                        cx="19"
                        cy="19"
                        r="1"
                    /><circle cx="5" cy="5" r="1" />
                </svg>
            </div>
            <div>
                <h2 class="form-card-title">Color Palette</h2>
                <p class="form-card-sub">
                    Palet warna yang digunakan dalam tema undangan.
                </p>
            </div>
            <button class="btn-add" onclick={addColor}>
                <svg
                    width="13"
                    height="13"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    ><line x1="12" y1="5" x2="12" y2="19" /><line
                        x1="5"
                        y1="12"
                        x2="19"
                        y2="12"
                    /></svg
                >
                Tambah Warna
            </button>
        </div>
        <div class="form-body">
            {#each colorsList as color, i}
                <div class="repeater-item">
                    <div class="repeater-item-head">
                        <span class="repeater-num">Warna #{i + 1}</span>
                        <button
                            class="btn-remove"
                            onclick={() => removeColor(i)}
                        >
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                ><line x1="18" y1="6" x2="6" y2="18" /><line
                                    x1="6"
                                    y1="6"
                                    x2="18"
                                    y2="18"
                                /></svg
                            >
                            Hapus
                        </button>
                    </div>
                    <div class="form-grid form-grid-2">
                        <div class="field">
                            <label class="label">Kode Warna (HEX)</label>
                            <div class="color-input-wrap">
                                <input
                                    type="color"
                                    bind:value={color.hex}
                                    class="color-picker"
                                />
                                <input
                                    type="text"
                                    bind:value={color.hex}
                                    placeholder="#D4AF37"
                                    class="input"
                                />
                            </div>
                        </div>
                        <div class="field">
                            <label class="label">Nama Warna</label>
                            <input
                                type="text"
                                bind:value={color.name}
                                placeholder="Nama warna (misal: Royal Gold)"
                                class="input"
                            />
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <!-- SECTION: MOODBOARD - ANNOUNCEMENTS -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-icon">
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
                    <rect x="3" y="5" width="18" height="14" rx="2" /><path
                        d="M7 15h4M15 15h2M7 11h2M13 11h4"
                    />
                </svg>
            </div>
            <div>
                <h2 class="form-card-title">Pengumuman Acara</h2>
                <p class="form-card-sub">
                    Informasi penting yang ingin ditampilkan kepada tamu.
                </p>
            </div>
            <button class="btn-add" onclick={addAnnouncement}>
                <svg
                    width="13"
                    height="13"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    ><line x1="12" y1="5" x2="12" y2="19" /><line
                        x1="5"
                        y1="12"
                        x2="19"
                        y2="12"
                    /></svg
                >
                Tambah Pengumuman
            </button>
        </div>
        <div class="form-body">
            {#each announcementsList as item, i}
                <div class="repeater-item">
                    <div class="repeater-item-head">
                        <span class="repeater-num">Pengumuman #{i + 1}</span>
                        <button
                            class="btn-remove"
                            onclick={() => removeAnnouncement(i)}
                        >
                            <svg
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                ><line x1="18" y1="6" x2="6" y2="18" /><line
                                    x1="6"
                                    y1="6"
                                    x2="18"
                                    y2="18"
                                /></svg
                            >
                            Hapus
                        </button>
                    </div>
                    <div class="form-grid form-grid-3">
                        <div class="field">
                            <label class="label">Icon</label>
                            <select class="input" bind:value={item.icon}>
                                <option value="invitation">Undangan</option>
                                <option value="qrcode">QR Code</option>
                            </select>
                        </div>
                        <div class="field field-span-2">
                            <label class="label">Judul</label>
                            <input
                                type="text"
                                bind:value={item.title}
                                placeholder="Judul pengumuman"
                                class="input"
                            />
                        </div>
                        <div class="field field-span-3">
                            <label class="label">Deskripsi</label>
                            <textarea
                                bind:value={item.description}
                                placeholder="Isi pengumuman..."
                                class="input textarea"
                                rows="3"
                            ></textarea>
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <!-- BRANDING, TIPS, INVITE MESSAGE & GIFT -->
    <div class="form-card">
        <div class="form-card-header">
            <div>
                <h2 class="form-card-title">Branding &amp; Pesan Undangan</h2>
                <p class="form-card-sub">
                    Logo, tips loading, template pesan WhatsApp, dan rekening
                    hadiah. Kosongkan untuk menyembunyikan bagian tersebut.
                </p>
            </div>
        </div>
        <div class="form-body">
            <div class="form-grid form-grid-2">
                <div class="field">
                    <label class="label">Hashtag / label pembuka</label>
                    <input
                        type="text"
                        class="input"
                        bind:value={hashtag}
                        placeholder="#NamaPasangan"
                    />
                </div>
                <div class="field">
                    <label class="label">Logo undangan</label>
                    {#if currentLogo}
                        <img src={currentLogo} alt="Logo" class="media-preview" />
                    {/if}
                    <input
                        type="file"
                        accept="image/*"
                        class="input"
                        onchange={(e) => (logoFile = e.currentTarget.files[0])}
                    />
                </div>
            </div>

            <div class="form-grid form-grid-2">
                <div class="field">
                    <label class="label">Tips saat loading (satu per baris)</label>
                    <textarea
                        class="input textarea"
                        rows="4"
                        bind:value={loadingTips}
                        placeholder="Love is patient...&#10;Two souls, one heart..."
                    ></textarea>
                </div>
                <div class="field">
                    <label class="label">Gambar QRIS</label>
                    {#if currentQris}
                        <img src={currentQris} alt="QRIS" class="media-preview" />
                    {/if}
                    <input
                        type="file"
                        accept="image/*"
                        class="input"
                        onchange={(e) => (qrisFile = e.currentTarget.files[0])}
                    />
                </div>
            </div>

            <div class="field">
                <label class="label">
                    Template pesan undangan (placeholder: {`{name}`}
                    {`{link}`} {`{quota}`} {`{couple}`})
                </label>
                <textarea
                    class="input textarea"
                    rows="8"
                    bind:value={inviteTemplate}
                    placeholder="Kosongkan untuk memakai template bawaan."
                ></textarea>
                {#if inviteTemplate}
                    <p class="label" style="margin-top: 8px;">Pratinjau:</p>
                    <pre class="message-preview">{previewMessage()}</pre>
                {/if}
            </div>

            <div class="field">
                <label class="label">Rekening hadiah</label>
                {#each giftAccounts as account, i}
                    <div class="form-grid form-grid-3" style="margin-bottom: 8px;">
                        <input
                            type="text"
                            class="input"
                            placeholder="Bank"
                            bind:value={account.bank}
                        />
                        <input
                            type="text"
                            class="input"
                            placeholder="Nomor"
                            bind:value={account.number}
                        />
                        <div style="display: flex; gap: 8px;">
                            <input
                                type="text"
                                class="input"
                                placeholder="Atas nama"
                                bind:value={account.holder}
                            />
                            <button
                                type="button"
                                class="btn-mini"
                                onclick={() => removeGiftAccount(i)}
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                {/each}
                <button type="button" class="btn-mini" onclick={addGiftAccount}>
                    + Tambah rekening
                </button>
            </div>
        </div>
    </div>

    <!-- BOTTOM SAVE BUTTON -->
    <button class="btn-save-bottom" onclick={handleSubmit} disabled={isSaving}>
        {isSaving ? "Menyimpan..." : "Simpan Semua Perubahan"}
    </button>
</AdminLayout>

<style>
    .message-preview {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 12.5px;
        line-height: 1.5;
        white-space: pre-wrap;
        font-family: inherit;
        color: var(--text-secondary);
        margin: 4px 0 0;
    }

    .btn-mini {
        height: 34px;
        padding: 0 10px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-secondary);
        font-size: 12px;
        font-family: inherit;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-mini:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    /* SAVE BAR */
    .save-bar {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 16px 20px;
        margin-bottom: 24px;
    }

    .save-bar-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .page-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 2px;
    }

    .page-sub {
        font-size: 12.5px;
        color: var(--text-secondary);
        margin: 0;
    }

    .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 0 18px;
        height: 38px;
        border-radius: var(--radius-sm);
        background: var(--primary);
        color: white;
        font-size: 13.5px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.15s;
        font-family: inherit;
    }

    .btn-save:hover:not(:disabled) {
        background: var(--primary-hover);
    }
    .btn-save:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .spinner-sm {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* FORM CARDS */
    .form-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .form-card-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
        background: var(--bg);
    }

    .form-card-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .form-card-title {
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 2px;
    }

    .form-card-sub {
        font-size: 12.5px;
        color: var(--text-secondary);
        margin: 0;
    }

    /* Push the add button to the right */
    .form-card-header .btn-add {
        margin-left: auto;
        flex-shrink: 0;
    }

    .form-body {
        padding: 20px;
    }

    /* GRIDS */
    .form-grid {
        display: grid;
        gap: 14px;
        margin-bottom: 0;
    }

    .form-grid-2 {
        grid-template-columns: 1fr 1fr;
    }
    .form-grid-3 {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .field-span-2 {
        grid-column: span 2;
    }
    .field-span-3 {
        grid-column: span 3;
    }

    .label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .input {
        padding: 9px 11px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        background: var(--card);
        color: var(--text-primary);
        font-size: 13.5px;
        font-family: inherit;
        transition: border-color 0.15s;
        width: 100%;
        box-sizing: border-box;
    }

    .input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.07);
    }

    .textarea {
        resize: vertical;
        min-height: 80px;
    }

    /* FILE INPUT */
    .file-input-wrap {
        margin-top: 6px;
    }

    .file-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border: 1px dashed var(--border);
        border-radius: var(--radius-sm);
        color: var(--text-secondary);
        font-size: 12.5px;
        cursor: pointer;
        transition: all 0.15s;
        font-family: inherit;
        background: var(--bg);
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .file-label:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: var(--primary-light);
    }

    .media-preview {
        max-width: 180px;
        max-height: 100px;
        border-radius: 8px;
        object-fit: cover;
        display: block;
        margin-bottom: 8px;
        border: 1px solid var(--border);
    }

    .audio-preview {
        width: 100%;
        display: block;
        margin-bottom: 8px;
    }

    /* MEMPELAI */
    .mempelai-block {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .mempelai-heading {
        font-size: 13px;
        font-weight: 600;
        color: var(--primary);
        margin: 0;
    }

    .avatar-preview {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border);
    }

    /* REPEATER */
    .repeater-item {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 16px;
        margin-bottom: 12px;
    }

    .repeater-item:last-child {
        margin-bottom: 0;
    }

    .repeater-item-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }

    .repeater-num {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .color-input-wrap {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .color-picker {
        width: 44px;
        height: 38px;
        padding: 2px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        cursor: pointer;
        flex-shrink: 0;
    }

    .btn-add {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: var(--radius-sm);
        background: var(--primary-light);
        color: var(--primary);
        border: 1px solid #bfdbfe;
        font-size: 12.5px;
        font-weight: 500;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.15s;
    }

    .btn-add:hover {
        background: #dbeafe;
    }

    .btn-remove {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        border-radius: 6px;
        background: var(--danger-light);
        color: var(--danger);
        border: 1px solid #fecaca;
        font-size: 12px;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.15s;
    }

    .btn-remove:hover {
        background: #fee2e2;
    }

    .btn-save-bottom {
        width: 100%;
        height: 46px;
        border-radius: var(--radius);
        background: var(--primary);
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        margin-top: 4px;
        transition: background 0.15s;
        font-family: inherit;
    }

    .btn-save-bottom:hover:not(:disabled) {
        background: var(--primary-hover);
    }
    .btn-save-bottom:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .form-grid-2 {
            grid-template-columns: 1fr;
        }
        .form-grid-3 {
            grid-template-columns: 1fr;
        }
        .field-span-2,
        .field-span-3 {
            grid-column: span 1;
        }
        .save-bar-inner {
            flex-direction: column;
            align-items: stretch;
        }
        .btn-save {
            width: 100%;
            justify-content: center;
        }
    }

    .arabic-textarea {
        font-family: "Amiri", serif;
        font-size: 16px;
        text-align: right;
    }
</style>
