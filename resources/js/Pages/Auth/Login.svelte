<script>
    import { useForm } from "@inertiajs/svelte";

    const form = useForm({
        email: "",
        password: "",
        remember: false,
    });

    function handleSubmit(e) {
        e.preventDefault();
        $form.post("/login");
    }
</script>

<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="logo-mark">
                <img src="/storage/logo/logo.jpeg" alt="Logo" class="logo-img" />
            </div>
            <h1 class="login-title">Admin Panel</h1>
            <p class="login-subtitle">Wedding System</p>
        </div>

        <form onsubmit={handleSubmit} class="login-form">
            <div class="field-group">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    bind:value={$form.email}
                    placeholder="admin@example.com"
                    autocomplete="username"
                    class:input-error={$form.errors.email}
                />
                {#if $form.errors.email}
                    <span class="error-text">{$form.errors.email}</span>
                {/if}
            </div>

            <div class="field-group">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    bind:value={$form.password}
                    placeholder="••••••••"
                    autocomplete="current-password"
                    class:input-error={$form.errors.password}
                />
                {#if $form.errors.password}
                    <span class="error-text">{$form.errors.password}</span>
                {/if}
            </div>

            <div class="field-checkbox">
                <label>
                    <input type="checkbox" bind:checked={$form.remember} />
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn-login" disabled={$form.processing}>
                {$form.processing ? "Signing in..." : "Sign In"}
            </button>
        </form>
    </div>

    <p class="footer-text">Wedding Guest Management System</p>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :global(*) {
        box-sizing: border-box;
    }

    :global(body) {
        margin: 0;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .login-page {
        min-height: 100vh;
        background: #F8FAFC;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .login-card {
        width: 100%;
        max-width: 380px;
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 2.5rem 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    }

    .login-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 2rem;
    }

    .logo-mark {
        width: 44px;
        height: 44px;
        background: #2563EB;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        overflow: hidden;
    }

    .logo-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .login-title {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 2px;
    }

    .login-subtitle {
        font-size: 13px;
        color: #6B7280;
        margin: 0;
    }

    .login-form {
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .field-group label {
        font-size: 13px;
        font-weight: 500;
        color: #374151;
    }

    .field-group input[type="email"],
    .field-group input[type="password"] {
        height: 40px;
        padding: 0 12px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        font-size: 14px;
        color: #111827;
        font-family: inherit;
        transition: border-color 0.15s;
    }

    .field-group input::placeholder {
        color: #9CA3AF;
    }

    .field-group input:focus {
        outline: none;
        border-color: #2563EB;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .field-group input.input-error {
        border-color: #EF4444;
    }

    .error-text {
        font-size: 12px;
        color: #EF4444;
    }

    .field-checkbox label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #6B7280;
        cursor: pointer;
    }

    .field-checkbox input[type="checkbox"] {
        width: 15px;
        height: 15px;
        accent-color: #2563EB;
        cursor: pointer;
    }

    .btn-login {
        height: 40px;
        background: #2563EB;
        color: #FFFFFF;
        border: none;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 4px;
        transition: background 0.15s;
        font-family: inherit;
    }

    .btn-login:hover:not(:disabled) {
        background: #1D4ED8;
    }

    .btn-login:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .footer-text {
        margin-top: 24px;
        font-size: 12px;
        color: #9CA3AF;
    }
</style>