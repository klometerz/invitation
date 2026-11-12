<script>
    import { page, router } from "@inertiajs/svelte";
    import Toast from "./Toast.svelte";
    import { toast } from "./toast.js";

    let { title = "Dashboard", breadcrumbs = [], children } = $props();

    let sidebarOpen = $state(true);
    let mobileSidebarOpen = $state(false);

    // `roles` limits an entry to specific roles; `feature` hides it when the
    // selected package disables that feature. Role checks are ALSO enforced
    // server-side - this only keeps the UI honest.
    const navItems = [
        {
            label: "Dashboard",
            href: "/admin",
            exact: true,
            roles: ["admin"],
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>`,
        },
        {
            label: "Invitations",
            href: "/admin/invitations",
            roles: ["admin"],
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`,
        },
        {
            label: "Guests",
            href: "/admin/guests",
            roles: ["admin"],
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
        },
        {
            label: "Gallery",
            href: "/admin/gallery",
            roles: ["admin"],
            feature: "gallery",
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>`,
        },
        {
            label: "Invitation",
            href: "/admin/invitation",
            roles: ["admin"],
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`,
        },
        {
            label: "Scanner",
            href: "/admin/scanner",
            roles: ["admin", "staff"],
            feature: "guestbook",
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>`,
        },
        {
            label: "Photobooth",
            href: "/admin/photobooth",
            roles: ["admin"],
            feature: "photobooth",
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>`,
        },
        {
            label: "Users",
            href: "/admin/users",
            roles: ["admin"],
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
        },
        {
            label: "Packages",
            href: "/admin/packages",
            roles: ["admin"],
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>`,
        },
        {
            label: "Settings",
            href: "/admin/settings",
            roles: ["admin"],
            icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>`,
        },
    ];

    const user = $derived($page.props.auth?.user ?? null);
    const tenant = $derived($page.props.tenant ?? null);
    const features = $derived(tenant?.features ?? {});
    const invitations = $derived($page.props.invitations ?? []);
    const isAdmin = $derived(user?.role === "admin");

    const visibleNav = $derived(
        navItems.filter((item) => {
            if (!user) return false;
            if (item.roles && !item.roles.includes(user.role)) return false;
            if (item.feature && features[item.feature] === false) return false;
            return true;
        }),
    );

    // Flash -> toast, once per message.
    let lastSuccess = "";
    let lastError = "";
    $effect(() => {
        const success = $page.props.flash?.success;
        if (success && success !== lastSuccess) {
            lastSuccess = success;
            toast(success, "success");
        }
        const error = $page.props.flash?.error;
        if (error && error !== lastError) {
            lastError = error;
            toast(error, "error");
        }
    });

    function isActive(item) {
        if (typeof window === "undefined") return false;
        const path = window.location.pathname.replace(/\/$/, "") || "/";

        if (item.exact) return path === item.href;

        return path.startsWith(item.href);
    }

    function switchEvent(value) {
        if (!value) return;
        router.post(
            "/admin/tenant/switch",
            { invitation_id: Number(value) },
            { preserveScroll: true },
        );
    }

    function handleLogout() {
        if (confirm("Yakin ingin logout?")) {
            router.post("/logout");
        }
    }
</script>

<!-- Mobile overlay -->
{#if mobileSidebarOpen}
    <div
        class="mobile-overlay"
        role="button"
        tabindex="0"
        aria-label="Close sidebar"
        onclick={() => (mobileSidebarOpen = false)}
        onkeydown={(e) => e.key === "Enter" && (mobileSidebarOpen = false)}
    ></div>
{/if}

<div class="admin-shell">
    <!-- SIDEBAR -->
    <aside
        class="sidebar"
        class:collapsed={!sidebarOpen}
        class:mobile-open={mobileSidebarOpen}
    >
        <div class="sidebar-logo">
            <div class="logo-mark">
                {#if $page.props.platform?.logo_url}
                    <img
                        src={$page.props.platform.logo_url}
                        alt={$page.props.platform?.name || "Logo"}
                        class="logo-img"
                    />
                {:else}
                    <span class="logo-initial">
                        {($page.props.platform?.name || "Admin")
                            .charAt(0)
                            .toUpperCase()}
                    </span>
                {/if}
            </div>
            {#if sidebarOpen}
                <div class="logo-text">
                    <span class="logo-title">Admin Panel</span>
                    <span class="logo-sub"
                        >{$page.props.platform?.name || "Undangan Digital"}</span
                    >
                </div>
            {/if}
        </div>

        <nav class="sidebar-nav">
            {#each visibleNav as item}
                <a
                    href={item.href}
                    class="nav-item"
                    class:active={isActive(item)}
                    title={!sidebarOpen ? item.label : ""}
                >
                    <span class="nav-icon">{@html item.icon}</span>
                    {#if sidebarOpen}
                        <span class="nav-label">{item.label}</span>
                    {/if}
                </a>
            {/each}
        </nav>

        <div class="sidebar-footer">
            <a
                href="/"
                class="nav-item"
                title={!sidebarOpen ? "View Site" : ""}
            >
                <span class="nav-icon">
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
                        <circle cx="12" cy="12" r="10" /><line
                            x1="2"
                            y1="12"
                            x2="22"
                            y2="12"
                        />
                        <path
                            d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                        />
                    </svg>
                </span>
                {#if sidebarOpen}<span class="nav-label">View Site</span>{/if}
            </a>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div
        class="main-area"
        style="margin-left: {sidebarOpen
            ? 'var(--sidebar-w)'
            : 'var(--sidebar-collapsed-w)'}"
    >
        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-left">
                <button
                    class="toggle-btn desktop-toggle"
                    onclick={() => (sidebarOpen = !sidebarOpen)}
                    aria-label="Toggle sidebar"
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
                        <line x1="3" y1="12" x2="21" y2="12" /><line
                            x1="3"
                            y1="6"
                            x2="21"
                            y2="6"
                        /><line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
                <button
                    class="toggle-btn mobile-toggle"
                    onclick={() => (mobileSidebarOpen = !mobileSidebarOpen)}
                    aria-label="Toggle sidebar"
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
                        <line x1="3" y1="12" x2="21" y2="12" /><line
                            x1="3"
                            y1="6"
                            x2="21"
                            y2="6"
                        /><line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>

                <div class="breadcrumb-wrap">
                    <span class="bc-item">
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
                            <path
                                d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                            /><polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                        Admin
                    </span>
                    {#each breadcrumbs as crumb}
                        <span class="bc-sep">›</span>
                        <span class="bc-item">{crumb}</span>
                    {/each}
                    <span class="bc-sep">›</span>
                    <span class="bc-item bc-current">{title}</span>
                </div>
            </div>

            <div class="header-right">
                {#if isAdmin && invitations.length > 0}
                    <select
                        class="event-switcher"
                        value={tenant?.id ?? ""}
                        onchange={(e) => switchEvent(e.currentTarget.value)}
                        title="Pilih acara"
                        aria-label="Pilih acara"
                    >
                        {#if !tenant}
                            <option value="" disabled>Pilih acara…</option>
                        {/if}
                        {#each invitations as invitation (invitation.id)}
                            <option value={invitation.id}>
                                {invitation.title}
                            </option>
                        {/each}
                    </select>
                {/if}
                <div class="user-info">
                    <div
                        class="header-avatar"
                        title={$page.props.auth?.user?.name || "Admin"}
                    >
                        {($page.props.auth?.user?.name || "Admin")
                            .charAt(0)
                            .toUpperCase()}
                    </div>
                    <span class="user-name"
                        >{$page.props.auth?.user?.name || "Admin"}</span
                    >
                </div>
                <button
                    onclick={handleLogout}
                    class="logout-btn"
                    title="Logout"
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    <span>Logout</span>
                </button>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="page-content">
            {@render children()}
        </main>
    </div>
</div>

<Toast />

<style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

    :global(*) {
        box-sizing: border-box;
    }

    :global(body) {
        margin: 0;
        font-family:
            "Inter",
            -apple-system,
            BlinkMacSystemFont,
            sans-serif;
        background: #f8fafc;
        color: #111827;
        overflow: auto !important;
    }

    :global(:root) {
        --bg: #f8fafc;
        --card: #ffffff;
        --primary: #2563eb;
        --primary-hover: #1d4ed8;
        --primary-light: #eff6ff;
        --success: #10b981;
        --success-light: #ecfdf5;
        --danger: #ef4444;
        --danger-light: #fef2f2;
        --warning: #f59e0b;
        --warning-light: #fffbeb;
        --border: #e5e7eb;
        --text-primary: #111827;
        --text-secondary: #6b7280;
        --text-muted: #9ca3af;
        --sidebar-bg: #111827;
        --sidebar-w: 240px;
        --sidebar-collapsed-w: 60px;
        --header-h: 56px;
        --radius: 12px;
        --radius-sm: 8px;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07),
            0 2px 4px -1px rgba(0, 0, 0, 0.05);
    }

    .admin-shell {
        display: flex;
        min-height: 100vh;
    }

    /* === SIDEBAR === */
    .sidebar {
        width: var(--sidebar-w);
        background: var(--sidebar-bg);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 50;
        transition: width 0.2s ease;
        overflow: hidden;
    }

    .sidebar.collapsed {
        width: var(--sidebar-collapsed-w);
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        min-height: var(--header-h);
    }

    .logo-mark {
        width: 44px;
        height: 44px;
        background: #f3f4f5;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .logo-initial {
        font-size: 18px;
        font-weight: 700;
        color: var(--sidebar-bg);
    }

    .logo-text {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .logo-title {
        font-size: 13px;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
    }

    .logo-sub {
        font-size: 11px;
        color: #6b7280;
        white-space: nowrap;
    }

    .sidebar-nav {
        flex: 1;
        padding: 8px 8px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        overflow-y: auto;
    }

    .sidebar-footer {
        padding: 8px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 8px;
        color: #9ca3af;
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 500;
        transition:
            background 0.15s,
            color 0.15s;
        white-space: nowrap;
        overflow: hidden;
    }

    .nav-item:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #ffffff;
    }

    .nav-item.active {
        background: rgba(37, 99, 235, 0.18);
        color: #60a5fa;
    }

    .nav-item.active .nav-icon {
        color: #60a5fa;
    }

    .nav-icon {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: inherit;
    }

    .nav-label {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* === TOP HEADER === */
    .main-area {
        margin-left: var(--sidebar-w);
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        transition: margin-left 0.2s ease;
    }

    .top-header {
        position: sticky;
        top: 0;
        z-index: 40;
        height: var(--header-h);
        background: var(--card);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        box-shadow: var(--shadow);
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .toggle-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-secondary);
        padding: 6px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        transition:
            background 0.15s,
            color 0.15s;
    }

    .toggle-btn:hover {
        background: var(--bg);
        color: var(--text-primary);
    }

    .mobile-toggle {
        display: none;
    }

    .breadcrumb-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: var(--text-muted);
    }

    .bc-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .bc-sep {
        color: var(--border);
    }

    .bc-current {
        color: var(--text-primary);
        font-weight: 500;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        user-select: none;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .event-switcher {
        height: 34px;
        max-width: 220px;
        padding: 0 10px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-primary);
        font-family: inherit;
        font-size: 12.5px;
        font-weight: 500;
        cursor: pointer;
    }

    .event-switcher:focus {
        outline: 2px solid var(--primary-light);
        border-color: var(--primary);
    }

    @media (max-width: 768px) {
        .event-switcher {
            max-width: 130px;
        }
    }

    .user-name {
        font-size: 13.5px;
        font-weight: 500;
        color: var(--text-primary);
    }

    @media (max-width: 768px) {
        .user-name {
            display: none;
        }
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        height: 34px;
        padding: 0 12px;
        border-radius: 8px;
        background: var(--card);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        font-size: 12.5px;
        font-weight: 500;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.15s;
    }

    .logout-btn:hover {
        background: var(--danger-light);
        border-color: #fecaca;
        color: var(--danger);
    }

    /* === PAGE CONTENT === */
    .page-content {
        flex: 1;
        padding: 28px 28px 40px;
        min-width: 0;
    }

    /* === MOBILE OVERLAY === */
    .mobile-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 49;
    }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            width: var(--sidebar-w) !important;
        }

        .sidebar.mobile-open {
            transform: translateX(0);
        }

        .main-area {
            margin-left: 0 !important;
        }

        .desktop-toggle {
            display: none;
        }
        .mobile-toggle {
            display: flex;
        }
        .mobile-overlay {
            display: block;
        }

        .page-content {
            padding: 16px 16px 40px;
        }

        .breadcrumb-wrap {
            display: none;
        }
    }
</style>
