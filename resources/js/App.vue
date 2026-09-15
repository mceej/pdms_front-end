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

                <form @submit.prevent="login">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input
                            id="username"
                            type="text"
                            v-model="email"
                            placeholder="Username"
                            :class="{ 'input-error': errors.email }"
                        />
                        <p v-if="errors.email" class="error-text">The username field is required.</p>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            v-model="password"
                            placeholder="Password"
                            :class="{ 'input-error': errors.password }"
                        />
                        <p v-if="errors.password" class="error-text">The password field is required.</p>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                v-model="rememberMe"
                                :class="{ 'checkbox-error': errors.rememberMe }"
                            />
                            I have read and agree to the
                            <a href="#">Privacy Policy</a>
                        </label>
                        <p v-if="errors.rememberMe" class="error-text">You must agree to the Privacy Policy.</p>
                    </div>

                    <button type="submit">
                        Login
                    </button>

                    <p class="help-text">
                        Need help? Send a ticket at
                        <a href="#">https://ticketport.dswdfo11.ph</a>
                    </p>
                </form>
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
import { reactive, ref } from 'vue';

const email = ref('');
const password = ref('');
const rememberMe = ref(false);

const errors = reactive({
    email: false,
    password: false,
    rememberMe: false,
});

const login = () => {
    errors.email = !email.value;
    errors.password = !password.value;
    errors.rememberMe = !rememberMe.value;

    if (errors.email || errors.password || errors.rememberMe) {
        return;
    }

    console.log('Login attempt:', {
        email: email.value,
        password: password.value,
        rememberMe: rememberMe.value,
    });
};
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

.logo-placeholder {
    width: 60px;
    height: 60px;
    border: 1px dashed #c3c7d1;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #9aa0ab;
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

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #1a1a2e;
}

.form-group input[type="text"],
.form-group input[type="password"] {
    width: 100%;
    padding: 12px;
    box-sizing: border-box;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-options {
    margin-bottom: 25px;
    font-size: 13px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: #4b5563;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
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
    border: none;
    border-radius: 6px;
    background: #3730a3;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
}

button:hover {
    background: #2e2789;
}

.help-text {
    text-align: center;
    margin-top: 16px;
    font-size: 12px;
    color: #9ca3af;
}

.help-text a {
    color: #6b7280;
}

.form-group input.input-error {
    border-color: #ef4444;
}

.form-group input.input-error::placeholder {
    color: #ef4444;
}

.error-text {
    color: #ef4444;
    font-size: 12px;
    margin-top: 6px;
    margin-bottom: 0;
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
fewofhweiuf hwelif

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