<template>
    <footer :class="['app-footer', `app-footer--${variant}`]">
        <p>Ⓒ 2026 Department of Social Welfare and Development - Field Office XI. All Rights Reserved.</p>
    </footer>
</template>

<script setup>
defineProps({
    variant: { type: String, default: 'workspace' },
});
</script>

<style scoped>
.app-footer {
    flex: 0 0 auto;
    width: 100%;
    padding: 8px 18px;
    background: #000;
    color: #fff;
    font-size: 10px;
    line-height: 1.35;
    text-align: center;
}

.app-footer p { margin: 0; }

.app-footer--dashboard {
    width: calc(100% + 60px);
    margin: 36px -30px -36px;
}

.app-footer--workspace {
    width: calc(100% + 56px);
    margin: auto -28px -24px;
}

.app-footer--served-list {
    width: calc(100% + 60px);
    margin: 36px -30px -36px;
}

@media (max-width: 768px) {
    .app-footer--dashboard,
    .app-footer--served-list {
        width: calc(100% + 32px);
        margin-right: -16px;
        margin-left: -16px;
        padding: 8px 12px;
    }

    .app-footer--dashboard { margin-bottom: -24px; }
    .app-footer--served-list { margin-bottom: -24px; }
}

@media (max-width: 720px) {
    .app-footer--workspace {
        width: calc(100% + 24px);
        margin-right: -12px;
        margin-left: -12px;
        margin-bottom: -16px;
        padding: 8px 12px;
    }
}
</style>
