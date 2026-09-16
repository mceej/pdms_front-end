<template>
    <div class="login-page">
        <div class="login-card">
            <div class="left-panel">
                <div class="logo-row">
                    <img src="/logo/dswdlogo2.png" alt="DSWD Logo" class="logo-img" />
                    <div class="logo-divider"></div>
                    <img src="/logo/dswdlogo.png" alt="Field Office Logo" class="logo-img" />
                </div>

                <div class="welcome-text">
                    <h1>Welcome</h1>
                    <p>Please login to your account to continue</p>
                </div>

                <LoginForm />
            </div>

            <div class="right-panel">
                <!-- swap this for your actual background photo -->
                <div class="image-placeholder"></div>
                <div class="image-overlay"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import LoginForm from '../components/LoginForm.vue';
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: flex;
    font-family: Arial, sans-serif;
    background: white;
}

.login-card {
    width: 100%;
    min-height: 100vh;
    background: white;
    display: flex;
    flex-wrap: wrap;
    overflow: hidden;
    padding: clamp(10px, 2vw, 20px);
    box-sizing: border-box;
    gap: clamp(10px, 2vw, 20px);
}

.left-panel {
    flex: 1 1 400px;
    padding: clamp(24px, 5vw, 48px) clamp(24px, 8vw, 80px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}

.logo-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-bottom: 32px;
}

.logo-img {
    height: 90px;
    width: auto;
    object-fit: contain;
    display: block;
}

.logo-divider {
    width: 2px;
    height: 90px;
    background-color: #9ca3af;
    flex-shrink: 0;
}

.welcome-text {
    text-align: center;
    margin-bottom: 32px;
}

.welcome-text h1 {
    font-size: 26px;
    margin-bottom: 6px;
    color: #1a1a2e;
}

.welcome-text p {
    color: #6b7280;
    font-size: 14px;
}

.right-panel {
    flex: 1 1 400px;
    min-height: 300px;
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 20px;
}

.image-placeholder {
    width: 100%;
    height: 100%;
    background: url('/logo/dswdlogo3.png') center/cover no-repeat;
}

.image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(55, 48, 163, 0.15), rgba(30, 27, 90, 0.25));
    border-radius: 16px;
}

@media (max-width: 768px) {
    .login-card {
        flex-direction: column;
    }

    .right-panel {
        min-height: 200px;
        order: -1;
    }
}

* {
    box-sizing: border-box;
}
</style>
