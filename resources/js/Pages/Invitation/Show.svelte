<script>
    // Theme loader (AGENTS.md 5.8). The server sends the theme key; the theme
    // bundle is imported lazily so the public page never ships admin code.
    let {
        invitation = null,
        theme = null,
        content = {},
        sections = [],
        features = {},
        guestData = null,
        galleries = [],
        meta = {},
    } = $props();

    const FALLBACK_THEME = "heritage-gold";

    let ThemeComponent = $state(null);
    let resolved = $state(false);
    let failed = $state(false);

    $effect(() => {
        const modules = import.meta.glob("../../Themes/*/Index.svelte");
        const requested = theme?.key;
        const path =
            requested && modules[`../../Themes/${requested}/Index.svelte`]
                ? `../../Themes/${requested}/Index.svelte`
                : `../../Themes/${FALLBACK_THEME}/Index.svelte`;

        const loader = modules[path];
        let cancelled = false;

        if (!loader) {
            failed = true;
            resolved = true;
            return;
        }

        loader().then(
            (module) => {
                if (cancelled) return;
                ThemeComponent = module.default;
                resolved = true;
            },
            () => {
                if (cancelled) return;
                failed = true;
                resolved = true;
            },
        );

        return () => {
            cancelled = true;
        };
    });
</script>

{#if ThemeComponent}
    <ThemeComponent
        {invitation}
        {content}
        {sections}
        {features}
        {guestData}
        {galleries}
        {meta}
    />
{:else if failed}
    <div class="theme-state">
        <p>Tema undangan tidak dapat dimuat.</p>
    </div>
{:else if resolved}
    <div class="theme-state"><p>Memuat undangan…</p></div>
{/if}

<style>
    .theme-state {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0a0a0a;
        color: #9ca3af;
        font-family: "Inter", sans-serif;
        font-size: 14px;
    }
</style>
