﻿<template>
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

                <form class="login-form" @submit.prevent="login">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input
                            id="username"
                            v-model="email"
                            type="text"
                            placeholder="Username"
                            :class="{ 'input-error': errors.email }"
                        />
                        <p v-if="errors.email" class="error-text">The username field is required.</p>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            v-model="password"
                            type="password"
                            placeholder="Password"
                            :class="{ 'input-error': errors.password }"
                        />
                        <p v-if="errors.password" class="error-text">The password field is required.</p>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input
                                v-model="rememberMe"
                                type="checkbox"
                                :class="{ 'checkbox-error': errors.rememberMe }"
                            />
                            I have read and agree to the <a href="#">Privacy Policy</a>
                        </label>
                        <p v-if="errors.rememberMe" class="error-text">You must agree to the Privacy Policy.</p>
                    </div>

                    <button type="submit">Login</button>

                    <p class="help-text">
                        Need help? Send a ticket at
                        <a href="#">https://ticketport.dswdfo11.ph</a>
                    </p>
                </form>
            </div>

            <div class="right-panel" aria-hidden="true">
                <div class="image-placeholder"></div>
                <div class="image-overlay"></div>
            </div>
        </section>
    </main>
</template>

<script setup>
import { reactive, ref } from 'vue';

const emit = defineEmits(['authenticated']);
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const errors = reactive({ email: false, password: false, rememberMe: false });

const login = () => {
    errors.email = !email.value;
    errors.password = !password.value;
    errors.rememberMe = !rememberMe.value;

    if (!errors.email && !errors.password && !errors.rememberMe) {
        emit('authenticated');
    }
};
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

.login-form {
    display: grid;
    gap: 20px;
    width: min(100%, 440px);
    margin: 0 auto;
}

.form-group,
.form-options {
    display: grid;
    gap: 8px;
}

.form-group label {
    color: #1a1a2e;
    font-size: 14px;
    font-weight: 600;
}

.form-group input {
    box-sizing: border-box;
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-options {
    color: #4b5563;
    font-size: 13px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    cursor: pointer;
}

.checkbox-label input {
    margin-top: 2px;
}

.checkbox-label a {
    color: #3730a3;
    font-weight: 600;
    text-decoration: none;
}

.checkbox-error {
    accent-color: #ef4444;
}

button {
    width: 100%;
    padding: 13px;
    border: 0;
    border-radius: 0px 16px 16px 0px;
    background: #3730a3;
    color: #fff;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
}

button:hover {
    background: #2e2789;
}

.help-text {
    margin: -4px 0 0;
    color: #9ca3af;
    font-size: 12px;
    line-height: 1.5;
    text-align: center;
}

.help-text a {
    color: #6b7280;
}

.input-error {
    border-color: #ef4444 !important;
}

.error-text {
    margin: 0;
    color: #ef4444;
    font-size: 12px;
}

.right-panel {
    position: relative;
    display: flex;
    flex: 1 1 400px;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    overflow: hidden;
    border-radius: 16px;
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
