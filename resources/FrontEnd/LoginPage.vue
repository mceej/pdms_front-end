<template>
    <main class="login-page">
        <section class="login-card" aria-labelledby="login-heading">
            <div class="left-panel">
                <div class="logo-row">
                    <img src="/logo/dswdlogo2.png" alt="DSWD Logo" class="logo-img" />
                    <div class="logo-divider"></div>
                    <img src="/logo/dswdlogo.png" alt="Field Office Logo" class="logo-img" />
                </div>

                <div class="welcome-text">
                    <h1 id="login-heading">Welcome</h1>
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
    display: flex;
    min-height: 100vh;
    background: #fff;
    font-family: Arial, sans-serif;
}

.login-card {
    display: flex;
    flex-wrap: wrap;
    gap: clamp(16px, 2vw, 24px);
    width: 100%;
    min-height: 100vh;
    padding: clamp(16px, 2vw, 28px);
    box-sizing: border-box;
    overflow: hidden;
}

.left-panel {
    display: flex;
    flex: 1 1 400px;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    padding: clamp(32px, 5vw, 56px) clamp(24px, 7vw, 80px);
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
    width: auto;
    height: 90px;
    object-fit: contain;
}

.logo-divider {
    flex-shrink: 0;
    width: 2px;
    height: 90px;
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
    position: relative;
    display: flex;
    flex: 1 1 400px;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    overflow: hidden;
    border-radius: 0px 16px 16px 0px;
}

.image-placeholder {
    width: 100%;
    height: 100%;
    min-height: 500px;
    background: url('/logo/dswdlogo3.png') center / cover no-repeat;
}

.image-overlay {
    position: absolute;
    inset: 0;
    border-radius: 16px;
    background: linear-gradient(135deg, rgb(55 48 163 / 15%), rgb(30 27 90 / 25%));
}

@media (max-width: 768px) {
    .login-card {
        flex-direction: column;
    }

    .left-panel {
        padding: 32px 24px;
    }

    .right-panel {
        order: -1;
        min-height: 200px;
    }

    .image-placeholder {
        min-height: 260px;
    }
}
</style>
