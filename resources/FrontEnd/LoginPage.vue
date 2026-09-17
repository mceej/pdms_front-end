<template>
    <main class="login-page">
        <section class="login-card" aria-labelledby="login-heading">
            <div class="left-panel">
                <div class="logo-row">
                    <img src="/logo/dswdlogo2.png" alt="DSWD Logo" class="logo-img" />
                    <div class="logo-divider"></div>
                    <img src="/logo/dswdlogo.png" alt="Field Office Logo" class="logo-img" />
                    <h1 id="login-heading" class="logo-title">DOTS</h1>
                </div>

                <div class="welcome-text">
                    <h1 id="login-heading" class="login-welcome">Welcome</h1>
                    <p>Please login to your account to continue</p>
                </div>

                <LoginForm @authenticated="emit('authenticated')" />
            </div>

            <div class="right-panel" aria-hidden="true">
                <div class="image-placeholder"></div>
                <div class="image-overlay"></div>
            </div>
        </section>
    </main>
</template>

<script setup>
import LoginForm from './components/LoginForm.vue';

const emit = defineEmits(['authenticated']);
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    min-height: 100dvh;
    background: #fff;
    font-family: Arial, sans-serif;
}

.login-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 24px;
    min-height: 100vh;
    min-height: 100dvh;
    padding: 24px;
    box-sizing: border-box;
}

.left-panel {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    padding: 32px 16px;
}

.logo-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 28px;
}

.logo-img {
    display: block;
    flex: 0 1 auto;
    min-width: 0;
    width: auto;
    height: auto;
    max-height: 90px;
}

.logo-divider {
    flex-shrink: 0;
    align-self: stretch;
    width: 2px;
    background: #9ca3af;
}

.welcome-text {
    margin-bottom: 28px;
    text-align: center;
}

.welcome-text h1 {
    margin: 0 0 8px;
    color: #1a1a2e;
    font-size: 26px;
}

.welcome-text p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.right-panel {
    position: sticky;
    top: 24px;
    align-self: start;
    height: calc(100vh - 48px);
    height: calc(100dvh - 48px);
    overflow: hidden;
    border-radius: 0px 16px 16px 0px;
}

.image-placeholder {
    width: 100%;
    height: 100%;
    background: url('/logo/dswdlogo3.png') center / cover no-repeat;
}

.image-overlay {
    position: absolute;
    inset: 0;
    border-radius: 16px;
    background: linear-gradient(135deg, rgb(55 48 163 / 15%), rgb(30 27 90 / 25%));
}

@media (max-width: 600px) {
    .login-card {
        grid-template-columns: minmax(0, 1fr);
        padding: 16px;
    }

    .left-panel {
        padding: 24px 8px;
    }

    .right-panel {
        display: none;
    }
}
.login-welcome {
    text-align: center;
    margin-bottom: 28px;
    color: #1a1a2e;
    font-size: 26px;
}
.logo-title {
    text-align: center;
    margin-bottom: 28px;
    color: #3730a3;
    font-size: 70px;
    font-weight: bold;
    padding: 30px 0px 0px 0px;
    font-family: 'Arial Black';
    letter-spacing: -1px;
    
}
</style>
