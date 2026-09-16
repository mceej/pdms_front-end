<template>
    <form @submit.prevent="login">
        <div class="form-group">
            <label for="username">Username</label>
            <input
                id="username"
                type="text"
                v-model="username"
                placeholder="Username"
                :class="{ 'input-error': errors.username }"
            />
            <p v-if="errors.username" class="error-text">The username field is required.</p>
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
                    v-model="hasAgreedToPrivacyPolicy"
                    :class="{ 'checkbox-error': errors.privacyPolicy }"
                />
                I have read and agree to the
                <a href="#">Privacy Policy</a>
            </label>
            <p v-if="errors.privacyPolicy" class="error-text">You must agree to the Privacy Policy.</p>
        </div>

        <button type="submit">
            Login
        </button>

        <p class="help-text">
            Need help? Send a ticket at
            <a href="#">https://ticketport.dswdfo11.ph</a>
        </p>
    </form>
</template>

<script setup>
import { reactive, ref } from 'vue';

const username = ref('');
const password = ref('');
const hasAgreedToPrivacyPolicy = ref(false);

const errors = reactive({
    username: false,
    password: false,
    privacyPolicy: false,
});

const login = () => {
    errors.username = !username.value;
    errors.password = !password.value;
    errors.privacyPolicy = !hasAgreedToPrivacyPolicy.value;

    if (errors.username || errors.password || errors.privacyPolicy) {
        return;
    }

    console.log('Login attempt:', { username: username.value });
};
</script>

<style scoped>
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

.form-group input.input-error {
    border-color: #ef4444;
}

.form-group input.input-error::placeholder {
    color: #ef4444;
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

.error-text {
    color: #ef4444;
    font-size: 12px;
    margin-top: 6px;
    margin-bottom: 0;
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

* {
    box-sizing: border-box;
}
</style>
