<template>
    <form class="login-form" @submit.prevent="login">
        <div class="form-group">
            <label for="username">Username</label>
            <input
                id="username"
                v-model="username"
                type="text"
                placeholder="Username"
                :class="{ 'input-error': errors.username }"
            />
            <p v-if="errors.username" class="error-text">The username field is required.</p>
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
                    v-model="hasAgreedToPrivacyPolicy"
                    type="checkbox"
                    :class="{ 'checkbox-error': errors.privacyPolicy }"
                />
                I have read and agree to the <a href="#">Privacy Policy</a>
            </label>
            <p v-if="errors.privacyPolicy" class="error-text">You must agree to the Privacy Policy.</p>
        </div>

        <p v-if="signInError" class="error-text sign-in-error">{{ signInError }}</p>

        <button type="submit" :disabled="isSigningIn">Login</button>

        <p class="help-text">
            Need help? Send a ticket at
            <a href="#">https://ticketport.dswdfo11.ph</a>
        </p>
    </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { signIn } from '../auth/adminAuth.js';

const emit = defineEmits(['authenticated']);
const username = ref('');
const password = ref('');
const hasAgreedToPrivacyPolicy = ref(false);
const errors = reactive({ username: false, password: false, privacyPolicy: false });
const signInError = ref('');
const isSigningIn = ref(false);

const login = async () => {
    errors.username = !username.value;
    errors.password = !password.value;
    errors.privacyPolicy = !hasAgreedToPrivacyPolicy.value;
    signInError.value = '';

    if (errors.username || errors.password || errors.privacyPolicy) {
        return;
    }

    isSigningIn.value = true;

    const result = await signIn(username.value, password.value);

    isSigningIn.value = false;

    if (result.ok) {
        emit('authenticated');
        return;
    }

    signInError.value = result.message;
    password.value = '';
};
</script>

<style scoped>
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
    border-radius: 6px;
    background: #3730a3;
    color: #fff;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
}

button:hover {
    background: #2e2789;
}

button:disabled {
    opacity: 0.7;
    cursor: progress;
}

.sign-in-error {
    margin: -8px 0 0;
    text-align: center;
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
</style>
